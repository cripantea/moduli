<?php

namespace App\Services\FormLayout;

use App\Support\Ghostscript;
use GdImage;
use RuntimeException;

/**
 * Analisi geometrica deterministica di un modulo PDF.
 *
 * Individua tutti i "posti" in cui si può scrivere (slot) senza AI:
 *  - puntini / underscore nel testo ("Nome ..........", "Cognome ______")
 *  - linee orizzontali disegnate (righe per scrivere, firme)
 *  - caselle e celle di tabella (rettangoli formati da linee orizzontali + verticali)
 *  - checkbox (caselle quadrate piccole)
 *
 * Il testo con coordinate viene da Ghostscript (txtwrite), le linee dalla
 * rasterizzazione della pagina. Tutte le coordinate interne sono in punti PDF
 * (1/72") con origine in alto a sinistra; y delle parole = baseline.
 *
 * Se installato, il rilevatore neurale FFDetr (FormFieldDetector) aggiunge i campi che
 * la geometria non vede: spazi bianchi senza linee e puntini nei PDF scansionati.
 *
 * Il risultato viene poi passato a Claude, che deve solo dire QUALE slot
 * corrisponde a quale campo: le coordinate restano quelle misurate qui.
 */
class PdfLayoutAnalyzer
{
    private const DPI         = 130;      // A4 → 1075×1520 px: abbastanza preciso e sotto il limite immagini di Claude
    private const DARK        = 170;      // soglia grigio (0-255) per considerare un pixel "inchiostro"
    private const MM          = 72 / 25.4; // punti per millimetro

    private const MIN_LINE_MM     = 8.0;  // lunghezza minima di una riga di scrittura
    private const MIN_BOX_SIDE_MM = 2.5;  // lato minimo di una checkbox
    private const MAX_CHECKBOX_MM = 7.0;  // oltre questo lato non è una checkbox
    private const MAX_THICK_MM    = 1.0;  // oltre questo spessore è una barra piena, non una linea
    private const MAX_CELL_H_MM   = 80.0; // distanza massima tra bordo superiore e inferiore di una cella
    private const MIN_DOT_RUN     = 3;    // "..." minimo per considerarlo uno spazio da compilare

    private float $pxToPt;

    /** @var array<int, string> righe della pagina corrente come stringhe 0/1 (1 = inchiostro) */
    private array $dark = [];

    public function __construct(private readonly ?FormFieldDetector $detector = null)
    {
        $this->pxToPt = 72 / self::DPI;
    }

    /**
     * @return array<int, array{
     *   page: int, width: float, height: float, image: string,
     *   text_lines: array<int, array{y: float, text: string}>,
     *   slots: array<int, array<string, mixed>>
     * }>
     */
    public function analyze(string $pdfPath, int $maxPages): array
    {
        if (! function_exists('imagecreatefrompng')) {
            throw new RuntimeException('Estensione PHP GD mancante sul server (es. apt install php8.3-gd).');
        }

        $tmpDir = sys_get_temp_dir() . '/layout_' . bin2hex(random_bytes(6));
        if (! mkdir($tmpDir) && ! is_dir($tmpDir)) {
            throw new RuntimeException('Impossibile creare la cartella temporanea per l\'analisi del PDF.');
        }

        try {
            Ghostscript::run([
                '-q', '-dBATCH', '-dNOPAUSE', '-dSAFER',
                '-sDEVICE=pnggray', '-r' . self::DPI,
                '-dFirstPage=1', '-dLastPage=' . $maxPages,
                '-sOutputFile=' . $tmpDir . '/p%03d.png',
                $pdfPath,
            ]);

            $xml = Ghostscript::run([
                '-q', '-dBATCH', '-dNOPAUSE', '-dSAFER',
                '-sDEVICE=txtwrite', '-dTextFormat=0',
                '-dFirstPage=1', '-dLastPage=' . $maxPages,
                '-sOutputFile=-',
                $pdfPath,
            ]);

            $textPages = $this->parseTxtwrite($xml);
            $images    = glob($tmpDir . '/p*.png') ?: [];
            sort($images);

            if ($images === []) {
                throw new RuntimeException('Impossibile rasterizzare il PDF: il file potrebbe essere danneggiato o protetto.');
            }

            $detections = $this->detector?->detect($images);

            $pages  = [];
            $nextId = 1;

            foreach ($images as $i => $pngPath) {
                $pages[] = $this->analyzePage($i + 1, $pngPath, $textPages[$i] ?? [], $detections[$i] ?? [], $nextId);
            }

            return $pages;
        } finally {
            foreach (glob($tmpDir . '/*') ?: [] as $f) {
                @unlink($f);
            }
            @rmdir($tmpDir);
        }
    }

    // ─── Pagina ──────────────────────────────────────────────────────────

    /**
     * @param  array<int, array<string, mixed>>  $spans
     */
    private function analyzePage(int $pageNo, string $pngPath, array $spans, array $detections, int &$nextId): array
    {
        $img = @imagecreatefrompng($pngPath);
        if (! $img instanceof GdImage) {
            throw new RuntimeException("Impossibile leggere l'immagine della pagina {$pageNo}.");
        }
        imagepalettetotruecolor($img);

        $wPx = imagesx($img);
        $hPx = imagesy($img);
        $width  = $wPx * $this->pxToPt;
        $height = $hPx * $this->pxToPt;

        $words     = $this->buildWords($spans);
        $textSlots = $this->findTextSlots($spans);

        [$hSegs, $vSegs] = $this->findSegments($img, $wPx, $hPx);
        $hSegs = $this->dropSegmentsOverText($hSegs, $words);

        $geomSlots = $this->buildGeometricSlots($hSegs, $vSegs);
        $slots     = $this->dedupe($textSlots, $geomSlots);
        $slots     = $this->mergeDetections($slots, $detections, $width, $height, $words);

        // Ordine di lettura: dall'alto in basso, da sinistra a destra.
        usort($slots, fn ($a, $b) => [round($a['y'] / 4), $a['x0']] <=> [round($b['y'] / 4), $b['x0']]);

        foreach ($slots as &$slot) {
            $slot['id']   = $nextId++;
            $slot['page'] = $pageNo;
            $this->attachContext($slot, $words);
        }
        unset($slot);

        $textLines = $this->buildTextLines($spans, $slots, $height);
        $image     = $this->renderAnnotated($img, $slots);
        imagedestroy($img);

        return [
            'page'       => $pageNo,
            'width'      => $width,
            'height'     => $height,
            'image'      => $image,
            'text_lines' => $textLines,
            'slots'      => $slots,
        ];
    }

    // ─── Testo (Ghostscript txtwrite) ───────────────────────────────────

    /**
     * @return array<int, array<int, array{x0: float, x1: float, y: float, size: float, chars: array<int, array{c: string, x0: float, x1: float}>}>>
     */
    private function parseTxtwrite(string $xml): array
    {
        $pages = [];
        foreach (preg_split('/<page>/', $xml) ?: [] as $chunk) {
            if (! str_contains($chunk, '<span')) {
                if (str_contains($chunk, '</page>')) {
                    $pages[] = [];
                }
                continue;
            }

            $spans = [];
            preg_match_all('/<span bbox="([\d.\-]+) ([\d.\-]+) ([\d.\-]+) ([\d.\-]+)"[^>]*size="([\d.]+)">(.*?)<\/span>/s', $chunk, $sm, PREG_SET_ORDER);
            foreach ($sm as $s) {
                preg_match_all('/<char bbox="([\d.\-]+) [\d.\-]+ ([\d.\-]+) [\d.\-]+" c="(.*?)"\/>/s', $s[6], $cm, PREG_SET_ORDER);
                $chars = [];
                foreach ($cm as $c) {
                    $chars[] = [
                        'c'  => html_entity_decode($c[3], ENT_QUOTES | ENT_XML1, 'UTF-8'),
                        'x0' => (float) $c[1],
                        'x1' => (float) $c[2],
                    ];
                }
                if ($chars === []) {
                    continue;
                }
                $spans[] = [
                    'x0'    => (float) $s[1],
                    'x1'    => (float) $s[3],
                    'y'     => (float) $s[2],
                    'size'  => max(4.0, (float) $s[5]),
                    'chars' => $chars,
                ];
            }
            $pages[] = $spans;
        }

        return $pages;
    }

    /**
     * Parole "vere" (senza i riempitivi .... e ____) con il loro riquadro.
     *
     * @return array<int, array{text: string, x0: float, x1: float, y: float, top: float, bottom: float}>
     */
    private function buildWords(array $spans): array
    {
        $words = [];
        foreach ($spans as $span) {
            $buf = '';
            $x0  = null;
            $x1  = 0.0;
            $flush = function () use (&$buf, &$x0, &$x1, &$words, $span) {
                $clean = trim($buf);
                if ($clean !== '' && $x0 !== null) {
                    $words[] = [
                        'text'   => $clean,
                        'x0'     => $x0,
                        'x1'     => $x1,
                        'y'      => $span['y'],
                        'top'    => $span['y'] - 0.75 * $span['size'],
                        'bottom' => $span['y'] + 0.22 * $span['size'],
                    ];
                }
                $buf = '';
                $x0  = null;
            };

            $filler = $this->fillerMask($span['chars']);
            foreach ($span['chars'] as $i => $ch) {
                if (trim($ch['c']) === '' || $filler[$i]) {
                    $flush();
                    continue;
                }
                $x0 ??= $ch['x0'];
                $x1 = $ch['x1'];
                $buf .= $ch['c'];
            }
            $flush();
        }

        return $words;
    }

    /**
     * Per ogni carattere: true se fa parte di una sequenza di riempimento ("....", "____").
     *
     * @return array<int, bool>
     */
    private function fillerMask(array $chars): array
    {
        $n    = count($chars);
        $mask = array_fill(0, $n, false);
        $i    = 0;
        while ($i < $n) {
            if (! preg_match('/^[._…]$/u', $chars[$i]['c'])) {
                $i++;
                continue;
            }
            $j = $i;
            while ($j + 1 < $n && preg_match('/^[._…]$/u', $chars[$j + 1]['c'])) {
                $j++;
            }
            if ($j - $i + 1 >= self::MIN_DOT_RUN) {
                for ($k = $i; $k <= $j; $k++) {
                    $mask[$k] = true;
                }
            }
            $i = $j + 1;
        }

        return $mask;
    }

    /**
     * Slot dai riempitivi testuali: "....", "____", "…".
     */
    private function findTextSlots(array $spans): array
    {
        $slots = [];
        foreach ($spans as $span) {
            $chars = $span['chars'];
            $n     = count($chars);
            $i     = 0;
            while ($i < $n) {
                if (! preg_match('/^[._…]$/u', $chars[$i]['c'])) {
                    $i++;
                    continue;
                }
                $j = $i;
                while ($j + 1 < $n && preg_match('/^[._…]$/u', $chars[$j + 1]['c'])) {
                    $j++;
                }
                $runLen = $j - $i + 1;
                $width  = $chars[$j]['x1'] - $chars[$i]['x0'];
                if ($runLen >= self::MIN_DOT_RUN && $width >= 2.5 * self::MM) {
                    $isUnderscore = $chars[$i]['c'] === '_';
                    $slots[] = [
                        'kind' => $isUnderscore ? 'underscore' : 'dots',
                        'x0'   => $chars[$i]['x0'],
                        'x1'   => $chars[$j]['x1'],
                        'y'    => $span['y'],
                        'top'  => null,
                        'size' => $span['size'],
                    ];
                }
                $i = $j + 1;
            }
        }

        return $slots;
    }

    // ─── Linee (raster) ─────────────────────────────────────────────────

    /**
     * Trova segmenti orizzontali e verticali sottili nell'immagine della pagina.
     *
     * @return array{0: array<int, array{x0: float, x1: float, y: float}>, 1: array<int, array{x: float, y0: float, y1: float}>}
     */
    private function findSegments(GdImage $img, int $w, int $h): array
    {
        $rows = array_fill(0, $h, '');
        $cols = array_fill(0, $w, '');

        for ($y = 0; $y < $h; $y++) {
            $row = '';
            for ($x = 0; $x < $w; $x++) {
                $row .= ((imagecolorat($img, $x, $y) & 0xFF) < self::DARK) ? '1' : '0';
            }
            $rows[$y] = $row;
        }
        $this->dark = $rows;
        for ($x = 0; $x < $w; $x++) {
            $col = '';
            for ($y = 0; $y < $h; $y++) {
                $col .= $rows[$y][$x];
            }
            $cols[$x] = $col;
        }

        $mmPx     = self::DPI / 25.4;
        $minRun   = (int) floor(self::MIN_BOX_SIDE_MM * $mmPx);
        $maxThick = max(2, (int) ceil(self::MAX_THICK_MM * $mmPx));

        $h = [];
        foreach ($this->mergeRuns($rows, $minRun, $maxThick) as $s) {
            $h[] = ['x0' => $s['a'] * $this->pxToPt, 'x1' => ($s['b'] + 1) * $this->pxToPt, 'y' => $s['c'] * $this->pxToPt];
        }
        $v = [];
        foreach ($this->mergeRuns($cols, $minRun, $maxThick) as $s) {
            $v[] = ['x' => $s['c'] * $this->pxToPt, 'y0' => $s['a'] * $this->pxToPt, 'y1' => ($s['b'] + 1) * $this->pxToPt];
        }

        return [$h, $v];
    }

    /**
     * Raggruppa run di pixel scuri su linee consecutive in segmenti con spessore.
     *
     * @param  array<int, string>  $lines  righe (o colonne) come stringhe di 0/1
     * @return array<int, array{a: int, b: int, c: float}>  a..b = estensione, c = posizione centrale
     */
    private function mergeRuns(array $lines, int $minRun, int $maxThick): array
    {
        $done   = [];
        $active = [];

        foreach ($lines as $pos => $line) {
            preg_match_all('/1(?:0?1)*/', $line, $m, PREG_OFFSET_CAPTURE);
            $next = [];
            foreach ($m[0] as [$run, $start]) {
                $len = strlen($run);
                if ($len < $minRun) {
                    continue;
                }
                $a = $start;
                $b = $start + $len - 1;

                $matched = false;
                foreach ($active as $k => $seg) {
                    $ov = min($b, $seg['b']) - max($a, $seg['a']);
                    if ($ov >= 0.7 * min($b - $a, $seg['b'] - $seg['a'])) {
                        $seg['a'] = min($a, $seg['a']);
                        $seg['b'] = max($b, $seg['b']);
                        $seg['last'] = $pos;
                        $seg['n']++;
                        $next[] = $seg;
                        unset($active[$k]);
                        $matched = true;
                        break;
                    }
                }
                if (! $matched) {
                    $next[] = ['a' => $a, 'b' => $b, 'first' => $pos, 'last' => $pos, 'n' => 1];
                }
            }
            foreach ($active as $seg) {
                $done[] = $seg;
            }
            $active = $next;
        }
        foreach ($active as $seg) {
            $done[] = $seg;
        }

        $out = [];
        foreach ($done as $seg) {
            if ($seg['n'] <= $maxThick) {
                $out[] = ['a' => $seg['a'], 'b' => $seg['b'], 'c' => ($seg['first'] + $seg['last']) / 2 + 0.5];
            }
        }

        return $out;
    }

    /** Elimina i "segmenti" che in realtà sono tratti di lettere. */
    private function dropSegmentsOverText(array $hSegs, array $words): array
    {
        return array_values(array_filter($hSegs, function ($s) use ($words) {
            $len = $s['x1'] - $s['x0'];
            $covered = 0.0;
            foreach ($words as $w) {
                if ($s['y'] > $w['top'] && $s['y'] < $w['bottom']) {
                    $covered += max(0, min($s['x1'], $w['x1']) - max($s['x0'], $w['x0']));
                }
            }

            return $covered < 0.3 * $len;
        }));
    }

    /**
     * Combina linee orizzontali e verticali in righe di scrittura, caselle e checkbox.
     */
    private function buildGeometricSlots(array $hSegs, array $vSegs): array
    {
        $tol   = 1.2 * self::MM;
        $slots = [];

        foreach ($hSegs as $hs) {
            // Verticali che terminano sulla linea o la attraversano (righe interne di tabelle).
            $ending = array_values(array_filter($vSegs, fn ($v) =>
                $v['y1'] >= $hs['y'] - 0.7 * self::MM
                && $v['y0'] <= $hs['y'] - self::MIN_BOX_SIDE_MM * self::MM + $tol
                && $v['x'] >= $hs['x0'] - $tol && $v['x'] <= $hs['x1'] + $tol
            ));
            $starting = array_filter($vSegs, fn ($v) =>
                abs($v['y0'] - $hs['y']) <= $tol
                && $v['x'] >= $hs['x0'] - $tol && $v['x'] <= $hs['x1'] + $tol
            );

            if ($ending === []) {
                // Bordo superiore di un riquadro/tabella: non ci si scrive sopra.
                if ($starting !== [] || ($hs['x1'] - $hs['x0']) < self::MIN_LINE_MM * self::MM) {
                    continue;
                }
                $slots[] = ['kind' => 'line', 'x0' => $hs['x0'], 'x1' => $hs['x1'], 'y' => $hs['y'], 'top' => null];
                continue;
            }

            // Spezza la linea in corrispondenza delle verticali che vi terminano (celle).
            $xs = array_map(fn ($v) => $v['x'], $ending);
            sort($xs);
            $cuts = [];
            foreach ($xs as $x) {
                if ($cuts === [] || $x - end($cuts) > $tol) {
                    $cuts[] = $x;
                }
            }

            for ($i = 0; $i + 1 < count($cuts); $i++) {
                $a = $cuts[$i];
                $b = $cuts[$i + 1];
                if ($b - $a < self::MIN_BOX_SIDE_MM * self::MM) {
                    continue;
                }

                $top = $this->findCellTop($a, $b, $hs['y'], $hSegs, $vSegs, $tol);
                if ($top === null) {
                    if ($b - $a >= self::MIN_LINE_MM * self::MM) {
                        $slots[] = ['kind' => 'line', 'x0' => $a, 'x1' => $b, 'y' => $hs['y'], 'top' => null];
                    }
                    continue;
                }

                $wMm = ($b - $a) / self::MM;
                $hMm = ($hs['y'] - $top) / self::MM;
                $kind = ($wMm <= self::MAX_CHECKBOX_MM && $hMm <= self::MAX_CHECKBOX_MM && $wMm / max($hMm, 0.1) < 1.8 && $hMm / max($wMm, 0.1) < 1.8)
                    ? 'checkbox'
                    : 'box';

                // Scarta forme che non sono rettangoli veri (es. lettere in grassetto nei PDF scansionati).
                if (! $this->looksLikeRectangle($a, $b, $top, $hs['y'], $kind === 'checkbox')) {
                    continue;
                }

                if ($kind === 'box' && $wMm < self::MIN_LINE_MM * 0.6) {
                    continue;
                }

                $slots[] = ['kind' => $kind, 'x0' => $a, 'x1' => $b, 'y' => $hs['y'], 'top' => $top];
            }
        }

        return $this->dedupeCells($slots);
    }

    /**
     * Due rettangoli adiacenti hanno bordi a ~1 mm di distanza: la stessa cella può
     * essere trovata due volte. Tiene quella con il bordo inferiore più alto.
     */
    private function dedupeCells(array $slots): array
    {
        $near = 2 * self::MM;
        $out  = [];
        foreach ($slots as $s) {
            foreach ($out as $k => $o) {
                if ($s['top'] !== null && $o['top'] !== null
                    && abs($s['x0'] - $o['x0']) < $near && abs($s['x1'] - $o['x1']) < $near
                    && abs($s['top'] - $o['top']) < $near && abs($s['y'] - $o['y']) < $near) {
                    $out[$k]['y'] = min($o['y'], $s['y']);
                    continue 2;
                }
            }
            $out[] = $s;
        }

        return $out;
    }

    /** Verifica sui pixel: 4 lati quasi continui e, per le checkbox, interno vuoto. */
    private function looksLikeRectangle(float $x0, float $x1, float $top, float $bottom, bool $checkbox): bool
    {
        $px = fn (float $pt) => (int) round($pt / $this->pxToPt);
        $a = $px($x0);
        $b = $px($x1);
        $t = $px($top);
        $u = $px($bottom);
        if ($b - $a < 3 || $u - $t < 3) {
            return false;
        }

        $rowCoverage = function (int $y) use ($a, $b): float {
            $best = 0.0;
            for ($dy = -2; $dy <= 2; $dy++) {
                $row = $this->dark[$y + $dy] ?? '';
                if ($row === '') {
                    continue;
                }
                $best = max($best, substr_count(substr($row, $a, $b - $a + 1), '1') / ($b - $a + 1));
            }

            return $best;
        };
        $colCoverage = function (int $x) use ($t, $u): float {
            $best = 0.0;
            for ($dx = -2; $dx <= 2; $dx++) {
                $hits = 0;
                for ($y = $t; $y <= $u; $y++) {
                    $hits += (($this->dark[$y][$x + $dx] ?? '0') === '1') ? 1 : 0;
                }
                $best = max($best, $hits / ($u - $t + 1));
            }

            return $best;
        };

        if ($rowCoverage($t) < 0.8 || $rowCoverage($u) < 0.8 || $colCoverage($a) < 0.8 || $colCoverage($b) < 0.8) {
            return false;
        }

        if ($checkbox) {
            $ia = $a + (int) ceil(($b - $a) * 0.25);
            $ib = $b - (int) ceil(($b - $a) * 0.25);
            $dark = $total = 0;
            for ($y = $t + (int) ceil(($u - $t) * 0.25); $y <= $u - (int) ceil(($u - $t) * 0.25); $y++) {
                $chunk = substr($this->dark[$y] ?? '', $ia, $ib - $ia + 1);
                $dark  += substr_count($chunk, '1');
                $total += strlen($chunk);
            }
            if ($total > 0 && $dark / $total > 0.15) {
                return false;
            }
        }

        return true;
    }

    private function findCellTop(float $a, float $b, float $bottom, array $hSegs, array $vSegs, float $tol): ?float
    {
        $best = null;
        foreach ($hSegs as $t) {
            $gap = $bottom - $t['y'];
            if ($gap < self::MIN_BOX_SIDE_MM * self::MM - $tol || $gap > self::MAX_CELL_H_MM * self::MM) {
                continue;
            }
            if ($t['x0'] > $a + $tol || $t['x1'] < $b - $tol) {
                continue;
            }
            if ($best === null || $t['y'] > $best) {
                $best = $t['y'];
            }
        }
        if ($best === null) {
            return null;
        }

        // Entrambi i lati devono salire fino al bordo superiore.
        foreach ([$a, $b] as $x) {
            $ok = false;
            foreach ($vSegs as $v) {
                if (abs($v['x'] - $x) <= $tol && $v['y0'] <= $best + $tol && $v['y1'] >= $bottom - $tol) {
                    $ok = true;
                    break;
                }
            }
            if (! $ok) {
                return null;
            }
        }

        return $best;
    }

    /** Gli underscore testuali vengono visti anche come linee raster: tieni la versione testuale. */
    private function dedupe(array $textSlots, array $geomSlots): array
    {
        $out = $textSlots;
        foreach ($geomSlots as $g) {
            $dup = false;
            foreach ($out as $t) {
                $ov = min($g['x1'], $t['x1']) - max($g['x0'], $t['x0']);
                if ($g['kind'] === 'line' && abs($g['y'] - $t['y']) <= 3 * self::MM
                    && $ov >= 0.5 * min($g['x1'] - $g['x0'], $t['x1'] - $t['x0'])) {
                    $dup = true;
                    break;
                }
                if ($g['kind'] !== 'line' && $t['kind'] === $g['kind']
                    && abs($g['y'] - $t['y']) < 1 && abs($g['x0'] - $t['x0']) < 1) {
                    $dup = true;
                    break;
                }
            }
            if (! $dup) {
                $out[] = $g;
            }
        }

        return $out;
    }

    // ─── Rilevatore neurale (FFDetr) ────────────────────────────────────

    /**
     * Aggiunge i campi trovati dal modello che non coincidono con slot già misurati.
     * La geometria resta la fonte principale perché è più precisa; il modello copre
     * spazi bianchi e PDF scansionati.
     */
    private function mergeDetections(array $slots, array $detections, float $width, float $height, array $words): array
    {
        foreach ($detections as $det) {
            $x0 = $det['x0'] * $width;
            $x1 = $det['x1'] * $width;
            $y0 = $det['y0'] * $height;
            $y1 = $det['y1'] * $height;
            $area = max(1.0, ($x1 - $x0) * ($y1 - $y0));
            if ($x1 - $x0 < self::MIN_BOX_SIDE_MM * self::MM || $y1 - $y0 < 1.5 * self::MM) {
                continue;
            }

            $covered = false;
            foreach ($slots as $s) {
                $sTop = $s['top'] ?? $s['y'] - 4 * self::MM;
                $sBot = $s['y'] + 1 * self::MM;
                $ix = max(0, min($x1, $s['x1']) - max($x0, $s['x0']));
                $iy = max(0, min($y1, $sBot) - max($y0, $sTop));
                if ($ix * $iy >= 0.3 * $area || $ix * $iy >= 0.5 * ($s['x1'] - $s['x0']) * ($sBot - $sTop)) {
                    $covered = true;
                    break;
                }
            }
            if ($covered) {
                continue;
            }

            // Riquadri che contengono soprattutto testo stampato non sono spazi da compilare.
            $textArea = 0.0;
            foreach ($words as $w) {
                $textArea += max(0, min($x1, $w['x1']) - max($x0, $w['x0'])) * max(0, min($y1, $w['bottom']) - max($y0, $w['top']));
            }
            if ($textArea > 0.25 * $area) {
                continue;
            }

            $base = ['x0' => $x0, 'x1' => $x1, 'detected' => round((float) $det['conf'], 2)];

            if ($det['cls'] === 'choice') {
                $slots[] = $base + ['kind' => 'checkbox', 'y' => $y1, 'top' => $y0];
                continue;
            }
            if ($det['cls'] === 'signature') {
                $slots[] = $base + ['kind' => 'signature', 'y' => $y1, 'top' => $y0];
                continue;
            }

            // Campo di testo: c'è una riga (anche a puntini) su cui scrivere vicino al fondo?
            $lineY = $this->darkestRow($x0, $x1, $y0 + 0.55 * ($y1 - $y0), $y1 + 1 * self::MM);
            $topY  = $this->darkestRow($x0, $x1, $y0 - 1 * self::MM, $y0 + 0.3 * ($y1 - $y0));

            if ($lineY !== null && $topY === null) {
                $slots[] = $base + ['kind' => 'line', 'y' => $lineY, 'top' => null];
            } else {
                $slots[] = $base + ['kind' => 'area', 'y' => $lineY ?? $y1, 'top' => $topY ?? $y0];
            }
        }

        return $slots;
    }

    /** Riga di pixel più scura tra y0 e y1 sull'intervallo x0..x1, se abbastanza piena (≥ 25%). */
    private function darkestRow(float $x0, float $x1, float $y0, float $y1): ?float
    {
        $a = (int) round($x0 / $this->pxToPt);
        $b = (int) round($x1 / $this->pxToPt);
        $best = 0.0;
        $bestY = null;
        for ($y = (int) round($y0 / $this->pxToPt); $y <= (int) round($y1 / $this->pxToPt); $y++) {
            $row = $this->dark[$y] ?? '';
            if ($row === '' || $b <= $a) {
                continue;
            }
            $f = substr_count(substr($row, $a, $b - $a + 1), '1') / ($b - $a + 1);
            if ($f > $best) {
                $best = $f;
                $bestY = $y;
            }
        }

        return $best >= 0.25 ? ($bestY + 0.5) * $this->pxToPt : null;
    }

    // ─── Contesto testuale degli slot ───────────────────────────────────

    private function attachContext(array &$slot, array $words): void
    {
        $isCell = $slot['top'] !== null;
        $bandTop    = $isCell ? $slot['top'] : $slot['y'] - 4 * self::MM;
        $bandBottom = $slot['y'] + ($isCell ? 0 : 1.5 * self::MM);

        $left = $right = $above = $inside = [];

        foreach ($words as $w) {
            $midY = ($w['top'] + $w['bottom']) / 2;
            $sameRow = $midY >= $bandTop && $midY <= $bandBottom;
            $insideX = $w['x0'] >= $slot['x0'] - 1 && $w['x1'] <= $slot['x1'] + 1;

            if ($isCell && $sameRow && $insideX) {
                $inside[] = $w;
            } elseif ($sameRow && $w['x1'] <= $slot['x0'] + 2) {
                $left[] = $w;
            } elseif ($sameRow && $w['x0'] >= $slot['x1'] - 2) {
                $right[] = $w;
            } elseif ($w['y'] < $bandTop + ($isCell ? 0 : 4 * self::MM)
                && $w['y'] >= $bandTop - 9 * self::MM
                && min($w['x1'], $slot['x1']) - max($w['x0'], $slot['x0']) > -3 * self::MM) {
                $above[] = $w;
            }
        }

        usort($left, fn ($p, $q) => $p['x0'] <=> $q['x0']);
        usort($right, fn ($p, $q) => $p['x0'] <=> $q['x0']);
        usort($above, fn ($p, $q) => [round($p['y']), $p['x0']] <=> [round($q['y']), $q['x0']]);
        usort($inside, fn ($p, $q) => [round($p['y']), $p['x0']] <=> [round($q['y']), $q['x0']]);

        $join = fn (array $ws) => trim(implode(' ', array_map(fn ($w) => $w['text'], $ws)));

        $slot['left']   = mb_substr($join($left), -60);
        $slot['right']  = mb_substr($join($right), 0, 40);
        $slot['above']  = mb_substr($join(array_slice($above, -8)), -80);
        $slot['inside'] = mb_substr($join($inside), 0, 80);

        // Dentro una cella: lo spazio libero inizia dopo l'eventuale etichetta sulla stessa riga del testo.
        $slot['inside_words'] = array_map(fn ($w) => ['x1' => $w['x1'], 'top' => $w['top'], 'bottom' => $w['bottom']], $inside);
    }

    /**
     * Testo della pagina riga per riga, con i riempitivi sostituiti da [S#].
     *
     * @return array<int, array{y: float, text: string}>
     */
    private function buildTextLines(array $spans, array $slots, float $height): array
    {
        $rows = [];
        foreach ($spans as $span) {
            $text = '';
            $chars = $span['chars'];
            $n = count($chars);
            for ($i = 0; $i < $n; $i++) {
                if (preg_match('/^[._…]$/u', $chars[$i]['c'])) {
                    $j = $i;
                    while ($j + 1 < $n && preg_match('/^[._…]$/u', $chars[$j + 1]['c'])) {
                        $j++;
                    }
                    if ($j - $i + 1 >= self::MIN_DOT_RUN) {
                        $id = null;
                        foreach ($slots as $s) {
                            if (in_array($s['kind'], ['dots', 'underscore'], true)
                                && abs($s['x0'] - $chars[$i]['x0']) < 0.5 && abs($s['y'] - $span['y']) < 0.5) {
                                $id = $s['id'];
                                break;
                            }
                        }
                        $text .= $id ? " [S{$id}] " : ' ';
                        $i = $j;
                        continue;
                    }
                }
                $text .= $chars[$i]['c'];
            }
            $key = (int) round($span['y'] / 2.5);
            $rows[$key][] = ['x' => $span['x0'], 'y' => $span['y'], 'text' => $text];
        }

        ksort($rows);
        $out = [];
        foreach ($rows as $parts) {
            usort($parts, fn ($a, $b) => $a['x'] <=> $b['x']);
            $line = trim(preg_replace('/\s+/u', ' ', implode('   ', array_column($parts, 'text'))) ?? '');
            if ($line !== '') {
                $out[] = ['y' => round($parts[0]['y'] / $height * 100, 1), 'text' => $line];
            }
        }

        return $out;
    }

    // ─── Immagine annotata (Set-of-Mark) ───────────────────────────────

    private function renderAnnotated(GdImage $img, array $slots): string
    {
        $palette = [[220, 38, 38], [37, 99, 235], [22, 163, 74], [217, 119, 6], [147, 51, 234], [8, 145, 178], [219, 39, 119]];
        $toPx = fn (float $pt) => (int) round($pt / $this->pxToPt);
        $font = 3;
        $fw = imagefontwidth($font);
        $fh = imagefontheight($font);

        foreach ($slots as $i => $s) {
            [$r, $g, $b] = $palette[$i % count($palette)];
            $stroke = imagecolorallocate($img, $r, $g, $b);
            $fill   = imagecolorallocatealpha($img, $r, $g, $b, 112);
            $white  = imagecolorallocate($img, 255, 255, 255);

            $x0 = $toPx($s['x0']);
            $x1 = $toPx($s['x1']);
            $y1 = $toPx($s['y']) + 1;
            $y0 = $s['top'] !== null ? $toPx($s['top']) : $toPx($s['y'] - 3.5 * self::MM);

            if ($s['top'] === null) {
                imagefilledrectangle($img, $x0, $y0, $x1, $y1, $fill);
            }
            imagesetthickness($img, 2);
            imagerectangle($img, $x0, $y0, $x1, $y1, $stroke);
            imagesetthickness($img, 1);

            $label = (string) $s['id'];
            $lw = strlen($label) * $fw + 4;
            // Numero dentro lo slot (in basso a sinistra): non copre le etichette del modulo.
            $lx = $x0 + 1;
            $ly = max(0, $y1 - $fh - 2);
            imagefilledrectangle($img, $lx, $ly, $lx + $lw, $ly + $fh + 1, $stroke);
            imagestring($img, $font, $lx + 2, $ly + 1, $label, $white);
        }

        ob_start();
        imagepng($img, null, 6);

        return (string) ob_get_clean();
    }
}
