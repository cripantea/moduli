<?php

namespace App\Services;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\AuthenticationException;
use Anthropic\Core\Exceptions\RateLimitException;
use App\Services\FormLayout\PdfLayoutAnalyzer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Estrazione automatica dei campi da un modulo PDF.
 *
 * 1. PdfLayoutAnalyzer misura in modo deterministico tutti i posti compilabili
 *    (puntini, underscore, righe, caselle, celle, checkbox) con coordinate esatte.
 * 2. Claude riceve le pagine con gli slot numerati disegnati sopra (Set-of-Mark)
 *    più il testo e il contesto di ogni slot, e decide SOLO la semantica:
 *    quali slot sono campi, come si chiamano, di che tipo sono, quali vanno uniti.
 * 3. Le coordinate finali vengono calcolate qui dalla geometria misurata,
 *    non stimate dal modello.
 */
class AiFieldExtractorService
{
    private const MAX_PDF_BYTES = 25 * 1024 * 1024;
    private const MAX_PAGES     = 20;
    private const MAX_TOKENS    = 16000;
    private const MAX_TOKENS_DEEPSEEK = 64000; // DeepSeek ragiona a lungo prima di rispondere (output max 384K)

    private const MM       = 72 / 25.4;
    private const FONT_PT  = 10;                 // font di default dei template
    private const CAP_PT   = 0.72 * self::FONT_PT; // altezza maiuscole Helvetica
    private const LINE_GAP = 1.3 * self::FONT_PT;  // interlinea per i campi multiriga

    private const TYPES = ['text', 'textarea', 'date', 'number', 'checkbox'];

    private const SYSTEM_PROMPT = <<<'PROMPT'
You turn a paper-style PDF form (usually Italian) into a fillable template.

A deterministic layout analyzer has already measured every place on the page where something can be written. Each one is a numbered slot, drawn on the page images as a coloured rectangle with its number in the bottom-left corner. Slot kinds:
- dots / underscore: a run of "....." or "_____" inside a line of text (the page text shows it as [S12])
- line: a drawn horizontal rule to write on
- box: a rectangle or table cell
- checkbox: a small square box
- area: a blank area where a vision model saw a field to fill in (no line or box was measured there)
- signature: a signature space
Slots marked "rilevato" come from the vision model rather than exact measurement: double-check them on the image.

For each slot you also get its position and the text immediately to its left, right, above and inside it. Your job is the semantics only: decide which slots are fields the person filling in the form must complete, and name them. The positions are already exact — never try to correct them.

Rules:
- One field per piece of information. Use the label printed next to/above the slot ("Il sottoscritto" → full name of the declarant; "nato a" → birthplace; "Prov. (...)" → province).
- A date printed as "...../...../......" (three slots) becomes three fields: <name>_giorno, <name>_mese, <name>_anno, type "number". A single slot for a date is one field of type "date".
- Several stacked writing lines that belong to one answer (e.g. "Descrizione dell'accaduto" followed by 4 lines) are ONE field of type "textarea" listing all those slot ids top to bottom.
- Table: header cells (containing column titles) are not fields. Each empty cell is a field named <column>_<row number>, e.g. "importo_danno_1", "importo_danno_2".
- Checkbox: one field per box, type "checkbox", label "<question>: <option>" (e.g. "Testimoni presenti: SI").
- A row of adjacent small boxes meant for one character each (targa, codice fiscale, CAP, IBAN written letter by letter) is NOT a set of checkboxes: it is ONE field (type "text" or "number") listing all those slot ids left to right.
- Skip slots that are not filled by the customer: signature lines ("Firma", "Timbro e firma"), boxes reserved to the office ("Riservato all'ufficio", "Spazio per la compagnia"), decorative rules, slots containing pre-printed text only.
- type: "date" for single-slot dates; "number" for amounts, quantities, numeric parts of dates, CAP; "checkbox" for checkboxes; "textarea" for multi-line answers; otherwise "text" (codice fiscale, telefono, targa, numero polizza are "text").
- required: true only if the form marks it as mandatory (asterisk, "obbligatorio") or it is obviously essential identity data of the declarant (name, codice fiscale).
- name: unique strict snake_case ASCII (lowercase, digits, underscores), descriptive in Italian, e.g. "cognome_nome_dichiarante", "data_sinistro", "luogo_sinistro". Never reuse a name.
- label: short human-readable Italian label, capitalised, as the person filling in the form would read it ("Data di nascita – giorno").
- A slot can belong to at most one field. Keep fields in reading order.
- If something clearly must be filled in but has NO slot (blank space with no line or box), add it with slot_ids [] and give page, x, y, w as percentages of page width/height: x = where writing starts, y = text baseline, w = available width. Otherwise set page, x, y, w to null.
PROMPT;

    public function __construct(private readonly PdfLayoutAnalyzer $analyzer)
    {
    }

    /**
     * Scarica il PDF da S3, lo analizza ed estrae lo schema dei campi con coordinate.
     *
     * @return array<int, array<string, mixed>>
     * @throws RuntimeException
     */
    public function extractFromPdf(string $s3Key): array
    {
        $provider = $this->provider();
        if (empty($provider['key'])) {
            throw new RuntimeException("Chiave API {$provider['label']} non configurata. Aggiungi {$provider['env']} al file .env.");
        }

        try {
            $pdfContent = Storage::disk('s3')->get($s3Key);
        } catch (\Throwable $e) {
            Log::error('AiFieldExtractorService: S3 download failed', ['key' => $s3Key, 'error' => $e->getMessage()]);
            throw new RuntimeException("Impossibile scaricare il PDF da S3 (chiave: {$s3Key}): {$e->getMessage()}");
        }

        if ($pdfContent === null || $pdfContent === false) {
            throw new RuntimeException("Il file PDF non esiste su S3 (chiave: {$s3Key}).");
        }

        if (strlen($pdfContent) > self::MAX_PDF_BYTES) {
            $mb = round(strlen($pdfContent) / 1024 / 1024, 1);
            throw new RuntimeException("Il PDF è troppo grande ({$mb} MB). Limite massimo: 25 MB.");
        }

        $tmp = tempnam(sys_get_temp_dir(), 'extract_') . '.pdf';
        file_put_contents($tmp, $pdfContent);

        try {
            return $this->extractFromFile($tmp);
        } finally {
            @unlink($tmp);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     * @throws RuntimeException
     */
    public function extractFromFile(string $pdfPath): array
    {
        @set_time_limit(300);

        $pages = $this->analyzer->analyze($pdfPath, self::MAX_PAGES);

        $slotsById = [];
        $pageSizes = [];
        foreach ($pages as $page) {
            $pageSizes[$page['page']] = [$page['width'], $page['height']];
            foreach ($page['slots'] as $slot) {
                $slotsById[$slot['id']] = $slot;
            }
        }

        Log::info('AiFieldExtractorService: layout analizzato', [
            'pages' => count($pages),
            'slots' => count($slotsById),
        ]);

        $raw = $this->askModel($pages);

        return $this->buildFields($raw, $slotsById, $pageSizes);
    }

    // ─── Modello AI ─────────────────────────────────────────────────────

    /**
     * Provider configurato con AI_PROVIDER. DeepSeek espone un endpoint compatibile con
     * l'API Anthropic, quindi si usa lo stesso SDK cambiando solo base URL, chiave e modello.
     *
     * @return array{name: string, label: string, env: string, key: ?string, model: string, base_url: ?string}
     */
    private function provider(): array
    {
        if (config('services.ai.provider') === 'deepseek') {
            return [
                'name'     => 'deepseek',
                'label'    => 'DeepSeek',
                'env'      => 'DEEPSEEK_API_KEY',
                'key'      => config('services.deepseek.key'),
                'model'    => config('services.deepseek.model', 'deepseek-flash'),
                'base_url' => config('services.deepseek.base_url', 'https://api.deepseek.com/anthropic'),
            ];
        }

        return [
            'name'     => 'anthropic',
            'label'    => 'Anthropic',
            'env'      => 'ANTHROPIC_API_KEY',
            'key'      => config('services.anthropic.key'),
            'model'    => config('services.anthropic.model', 'claude-opus-5-5'),
            'base_url' => config('services.anthropic.base_url') ?: null,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function askModel(array $pages): array
    {
        $provider = $this->provider();
        $client = new Client(
            apiKey: $provider['key'],
            baseUrl: $provider['base_url'],
            requestOptions: ['timeout' => 280, 'maxRetries' => 2],
        );
        $model = $provider['model'];
        $label = $provider['label'];

        $content = [];
        foreach ($pages as $page) {
            $content[] = ['type' => 'text', 'text' => "=== PAGINA {$page['page']} ==="];
            $content[] = [
                'type'   => 'image',
                'source' => ['type' => 'base64', 'mediaType' => 'image/png', 'data' => base64_encode($page['image'])],
            ];
            $content[] = ['type' => 'text', 'text' => $this->describePage($page)];
        }
        $content[] = [
            'type' => 'text',
            'text' => 'Identifica tutti i campi da compilare di questo modulo usando gli slot numerati.',
        ];

        try {
            if ($provider['name'] === 'anthropic') {
                // Output strutturato: il JSON rispetta sempre lo schema.
                $message = $client->beta->messages->create(
                    model: $model,
                    maxTokens: self::MAX_TOKENS,
                    system: self::SYSTEM_PROMPT,
                    messages: [['role' => 'user', 'content' => $content]],
                    outputConfig: [
                        'effort' => config('services.anthropic.effort', 'medium'),
                        'format' => ['type' => 'json_schema', 'schema' => $this->outputSchema()],
                    ],
                    betas: ['server-side-fallback-2026-07-01'],
                    fallbacks: 'default',
                );
            } else {
                // DeepSeek non supporta l'output strutturato: lo schema va nel prompt.
                $message = $client->messages->create(
                    model: $model,
                    maxTokens: self::MAX_TOKENS_DEEPSEEK,
                    system: self::SYSTEM_PROMPT . "\n\nRespond with ONLY a JSON object (no markdown, no other text) that validates against this JSON Schema:\n"
                        . json_encode($this->outputSchema(), JSON_UNESCAPED_SLASHES),
                    messages: [['role' => 'user', 'content' => $content]],
                );
            }
        } catch (AuthenticationException) {
            throw new RuntimeException("Chiave API {$label} non valida: controlla {$provider['env']}.");
        } catch (RateLimitException) {
            throw new RuntimeException("Limite di richieste {$label} raggiunto. Riprova tra qualche minuto.");
        } catch (APIStatusException $e) {
            Log::error('AiFieldExtractorService: API error', ['provider' => $label, 'status' => $e->status ?? null, 'error' => $e->getMessage()]);
            throw new RuntimeException("Errore dall'API {$label}: {$e->getMessage()}");
        } catch (APIConnectionException $e) {
            Log::error('AiFieldExtractorService: connessione fallita', ['provider' => $label, 'error' => $e->getMessage()]);
            throw new RuntimeException("Impossibile contattare l'API {$label}: {$e->getMessage()}");
        }

        if ($message->stopReason === 'refusal') {
            throw new RuntimeException("Il modello {$label} ha rifiutato di analizzare questo documento.");
        }
        if ($message->stopReason === 'max_tokens') {
            throw new RuntimeException('Il modulo è troppo lungo per essere analizzato in una sola volta: prova a dividerlo.');
        }

        $text = '';
        $thinkingChars = 0;
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $text .= $block->text;
            } elseif ($block->type === 'thinking') {
                $thinkingChars += strlen($block->thinking ?? '');
            }
        }

        Log::info('AiFieldExtractorService: risposta ricevuta', [
            'provider'      => $label,
            'model'         => $message->model,
            'input_tokens'  => $message->usage->inputTokens ?? null,
            'output_tokens' => $message->usage->outputTokens ?? null,
            'thinking_chars' => $thinkingChars,
            'text_chars'     => strlen($text),
        ]);

        $decoded = $this->decodeJson($text);
        if (! is_array($decoded) || ! is_array($decoded['fields'] ?? null)) {
            Log::warning('AiFieldExtractorService: JSON non valido', ['raw_excerpt' => substr($text, 0, 500)]);
            throw new RuntimeException("La risposta di {$label} non contiene un JSON valido. Riprova.");
        }

        return $decoded['fields'];
    }

    /** JSON della risposta, tollerando ```json ... ``` o testo attorno all'oggetto. */
    private function decodeJson(string $text): ?array
    {
        $text = trim($text);
        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        $start = strpos($text, '{');
        $end   = strrpos($text, '}');
        if ($start === false || $end === false || $end <= $start) {
            return null;
        }
        $decoded = json_decode(substr($text, $start, $end - $start + 1), true);

        return is_array($decoded) ? $decoded : null;
    }

    private function describePage(array $page): string
    {
        $w = $page['width'];
        $h = $page['height'];
        $pct = fn (float $v, float $total) => number_format($v / $total * 100, 1, '.', '');

        $out = "Testo della pagina {$page['page']} (y = % altezza pagina, [S#] = slot di puntini/underscore):\n";
        foreach ($page['text_lines'] as $line) {
            $out .= "y{$line['y']}: {$line['text']}\n";
        }

        $out .= "\nSlot della pagina {$page['page']}:\n";
        if ($page['slots'] === []) {
            $out .= "(nessuno slot rilevato: il modulo potrebbe avere solo spazi bianchi)\n";
        }
        foreach ($page['slots'] as $s) {
            $parts = [
                "S{$s['id']}",
                $s['kind'] . (isset($s['detected']) ? " (rilevato, conf {$s['detected']})" : ''),
                'x ' . $pct($s['x0'], $w) . '–' . $pct($s['x1'], $w) . '%',
                'y ' . ($s['top'] !== null ? $pct($s['top'], $h) . '–' : '') . $pct($s['y'], $h) . '%',
            ];
            foreach (['left' => 'sinistra', 'right' => 'destra', 'above' => 'sopra', 'inside' => 'dentro'] as $k => $label) {
                if (($s[$k] ?? '') !== '') {
                    $parts[] = "{$label}: \"{$s[$k]}\"";
                }
            }
            $out .= implode(' | ', $parts) . "\n";
        }

        return $out;
    }

    private function outputSchema(): array
    {
        $nullableNumber = ['anyOf' => [['type' => 'number'], ['type' => 'null']]];

        return [
            'type'       => 'object',
            'properties' => [
                'fields' => [
                    'type'  => 'array',
                    'items' => [
                        'type'       => 'object',
                        'properties' => [
                            'name'     => ['type' => 'string'],
                            'label'    => ['type' => 'string'],
                            'type'     => ['type' => 'string', 'enum' => self::TYPES],
                            'required' => ['type' => 'boolean'],
                            'slot_ids' => ['type' => 'array', 'items' => ['type' => 'integer']],
                            'page'     => ['anyOf' => [['type' => 'integer'], ['type' => 'null']]],
                            'x'        => $nullableNumber,
                            'y'        => $nullableNumber,
                            'w'        => $nullableNumber,
                        ],
                        'required'             => ['name', 'label', 'type', 'required', 'slot_ids', 'page', 'x', 'y', 'w'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required'             => ['fields'],
            'additionalProperties' => false,
        ];
    }

    // ─── Geometria → schema campi ──────────────────────────────────────

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildFields(array $raw, array $slotsById, array $pageSizes): array
    {
        $usedSlots = [];
        $usedNames = [];
        $fields    = [];

        foreach ($raw as $item) {
            if (! is_array($item)) {
                continue;
            }

            $name = $this->toSnakeCase((string) ($item['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $base = $name;
            for ($n = 2; isset($usedNames[$name]); $n++) {
                $name = "{$base}_{$n}";
            }

            $type = in_array($item['type'] ?? '', self::TYPES, true) ? $item['type'] : 'text';

            $slots = [];
            foreach ((array) ($item['slot_ids'] ?? []) as $id) {
                $id = (int) $id;
                if (isset($slotsById[$id]) && ! isset($usedSlots[$id])) {
                    $slots[] = $slotsById[$id];
                    $usedSlots[$id] = true;
                }
            }

            $geometry = $slots !== []
                ? $this->placeOnSlots($slots, $type, $pageSizes)
                : $this->placeFree($item, $pageSizes);

            if ($geometry === null) {
                continue;
            }
            if (($geometry['lines'] ?? 1) > 1) {
                $type = 'textarea';
            }

            $usedNames[$name] = true;
            $fields[] = [
                'name'     => $name,
                'label'    => trim((string) ($item['label'] ?? '')) ?: $name,
                'type'     => $type,
                'required' => (bool) ($item['required'] ?? false),
            ] + $geometry;
        }

        if ($fields === []) {
            throw new RuntimeException('Nessun campo compilabile individuato nel PDF.');
        }

        return $fields;
    }

    /**
     * Calcola x/y/w (in % della pagina) dalla geometria misurata degli slot.
     * y è la baseline del testo, come si aspetta PdfFormFillerService.
     */
    private function placeOnSlots(array $slots, string $type, array $pageSizes): array
    {
        usort($slots, fn ($a, $b) => [$a['page'], round($a['y'] / (2 * self::MM)), $a['x0']] <=> [$b['page'], round($b['y'] / (2 * self::MM)), $b['x0']]);

        $first = $slots[0];
        $page  = $first['page'];
        $slots = array_values(array_filter($slots, fn ($s) => $s['page'] === $page));
        [$pw, $ph] = $pageSizes[$page];

        $boxes = array_map(fn ($s) => $this->writingBox($s, $type), $slots);

        $sameRow = count($boxes) === 1
            || (max(array_column($boxes, 'y')) - min(array_column($boxes, 'y'))) < 2 * self::MM;

        if ($sameRow && $this->isComb($slots, $type)) {
            // Una lettera per casella: il compilatore distribuisce i caratteri sulle celle.
            $out = [
                'page' => $page,
                'x'    => $this->pct($slots[0]['x0'], $pw),
                'y'    => $this->pct($boxes[0]['y'], $ph),
                'w'    => $this->pct(end($slots)['x1'] - $slots[0]['x0'], $pw),
                'comb' => count($slots),
            ];

            return $out;
        }

        if ($sameRow) {
            $x0 = min(array_column($boxes, 'x0'));
            $x1 = max(array_column($boxes, 'x1'));
            $geometry = ['page' => $page, 'x' => $x0, 'y' => $boxes[0]['y'], 'w' => $x1 - $x0, 'lines' => $boxes[0]['lines'], 'gap' => $boxes[0]['gap']];
        } else {
            // Righe impilate → campo multiriga ancorato alla prima riga.
            $ys = array_column($boxes, 'y');
            $gap = (end($ys) - $ys[0]) / (count($ys) - 1);
            // Il compilatore usa la stessa x per tutte le righe: parte dalla x più a destra,
            // così la prima riga non copre un'etichetta tipo "Annotazioni: ......".
            $x0 = max(array_column($boxes, 'x0'));
            $geometry = [
                'page'  => $page,
                'x'     => $x0,
                'y'     => $ys[0],
                'w'     => min(array_column($boxes, 'x1')) - $x0,
                'lines' => count($boxes),
                'gap'   => $gap,
            ];
        }

        $out = [
            'page' => $geometry['page'],
            'x'    => $this->pct($geometry['x'], $pw),
            'y'    => $this->pct($geometry['y'], $ph),
            'w'    => $this->pct(max(0.0, $geometry['w']), $pw),
        ];
        if ($geometry['lines'] > 1) {
            $out['lines']    = $geometry['lines'];
            $out['line_gap'] = round($geometry['gap'] / $ph * 100, 2);
        }

        return $out;
    }

    /** Più caselle piccole affiancate della stessa larghezza = campo "a pettine". */
    private function isComb(array $slots, string $type): bool
    {
        if (count($slots) < 2 || in_array($type, ['checkbox', 'textarea'], true)) {
            return false;
        }
        $widths = [];
        foreach ($slots as $s) {
            if (! in_array($s['kind'], ['checkbox', 'box'], true) || ($s['x1'] - $s['x0']) > 10 * self::MM) {
                return false;
            }
            $widths[] = $s['x1'] - $s['x0'];
        }

        return max($widths) - min($widths) < 1.5 * self::MM;
    }

    /**
     * Area di scrittura dentro uno slot, in punti: x0, x1, baseline y, righe disponibili.
     *
     * @return array{x0: float, x1: float, y: float, lines: int, gap: float}
     */
    private function writingBox(array $s, string $type): array
    {
        $single = fn (float $x0, float $x1, float $y) => ['x0' => $x0, 'x1' => $x1, 'y' => $y, 'lines' => 1, 'gap' => self::LINE_GAP];

        switch ($s['kind']) {
            case 'dots':
                // Leggermente sopra i puntini, così restano leggibili entrambi.
                return $single($s['x0'] + 0.5, $s['x1'], $s['y'] - 1.8);

            case 'underscore':
                return $single($s['x0'] + 0.5, $s['x1'], $s['y'] - 0.8);

            case 'line':
                return $single($s['x0'] + 1 * self::MM, $s['x1'] - 1 * self::MM, $s['y'] - 0.8 * self::MM);

            case 'signature':
                return $single($s['x0'] + 1 * self::MM, $s['x1'] - 1 * self::MM, $s['y'] - 1.5 * self::MM);

            case 'checkbox':
                // "X" centrata nella casella (larghezza X Helvetica ≈ 0.667 em).
                $cx = ($s['x0'] + $s['x1']) / 2;
                $cy = ($s['top'] + $s['y']) / 2;
                $xw = 0.667 * self::FONT_PT;

                return $single($cx - $xw / 2, $s['x1'], $cy + self::CAP_PT / 2);
        }

        // box / cella / area rilevata
        $pad    = 1.2 * self::MM;
        $x0     = $s['x0'] + $pad;
        $x1     = $s['x1'] - $pad;
        $top    = $s['top'];
        $bottom = $s['y'];

        // Etichetta stampata dentro la casella: scrivi sotto, oppure alla sua destra.
        $inside = $s['inside_words'] ?? [];
        if ($inside !== []) {
            $labelBottom = max(array_column($inside, 'bottom'));
            if ($bottom - $labelBottom >= self::CAP_PT + 1.5 * self::MM) {
                $top = $labelBottom;
            } else {
                $x0 = max($x0, max(array_column($inside, 'x1')) + 1.5 * self::MM);
            }
        }

        $height = $bottom - $top;
        if ($type === 'textarea' && $height >= 2 * self::LINE_GAP) {
            $y     = $top + self::CAP_PT + 1.5 * self::MM;
            $lines = max(1, (int) floor(($bottom - 1.2 * self::MM - $y) / self::LINE_GAP) + 1);

            return ['x0' => $x0, 'x1' => $x1, 'y' => $y, 'lines' => $lines, 'gap' => self::LINE_GAP];
        }

        if ($height > 14 * self::MM) {
            return $single($x0, $x1, $top + self::CAP_PT + 2 * self::MM);
        }

        return $single($x0, $x1, $bottom - ($height - self::CAP_PT) / 2);
    }

    /** Campo senza slot: usa la posizione stimata da Claude (solo spazi bianchi senza linee). */
    private function placeFree(array $item, array $pageSizes): ?array
    {
        $page = (int) ($item['page'] ?? 0);
        if (! isset($pageSizes[$page]) || ! is_numeric($item['x'] ?? null) || ! is_numeric($item['y'] ?? null)) {
            return null;
        }

        return [
            'page' => $page,
            'x'    => $this->clamp((float) $item['x']),
            'y'    => $this->clamp((float) $item['y']),
            'w'    => $this->clamp(is_numeric($item['w'] ?? null) ? (float) $item['w'] : 25.0),
        ];
    }

    private function pct(float $pt, float $total): float
    {
        return $this->clamp($pt / $total * 100);
    }

    private function clamp(float $value): float
    {
        return round(max(0.0, min(100.0, $value)), 2);
    }

    private function toSnakeCase(string $input): string
    {
        $out = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $input);
        $out = ($out !== false && $out !== '') ? $out : $input;
        $out = strtolower($out);
        $out = preg_replace('/[^a-z0-9]+/', '_', $out) ?? $out;

        return trim($out, '_');
    }
}
