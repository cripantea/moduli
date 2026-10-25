<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Assistente AI dentro Moduli (come il "?" di Fusion CRM): DeepSeek, API compatibile OpenAI.
 * Risponde solo su come usare Moduli e rimanda all'assistenza umana quando non sa.
 */
class AssistantService
{
    public function configured(): bool
    {
        return (bool) config('services.deepseek.key');
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $turns
     * @return array{ok: bool, text?: string, model?: string, error?: string}
     */
    public function ask(array $context, array $turns): array
    {
        if (! $this->configured()) {
            return ['ok' => false, 'error' => 'Assistente non configurato'];
        }

        $model = config('services.deepseek.model');

        try {
            $response = Http::withToken(config('services.deepseek.key'))
                ->timeout(60)
                ->post(rtrim(config('services.deepseek.base_url'), '/') . '/chat/completions', [
                    'model'       => $model,
                    'max_tokens'  => 900,
                    'temperature' => 0.3,
                    'messages'    => [
                        ['role' => 'system', 'content' => $this->systemPrompt($context)],
                        ...$turns,
                    ],
                ]);
        } catch (\Throwable $e) {
            Log::warning('Assistente: DeepSeek non raggiungibile', ['error' => $e->getMessage()]);

            return ['ok' => false, 'error' => 'DeepSeek non raggiungibile'];
        }

        $text = trim((string) $response->json('choices.0.message.content'));
        if ($response->failed() || $text === '') {
            Log::warning('Assistente: risposta DeepSeek non valida', ['status' => $response->status()]);

            return ['ok' => false, 'error' => 'Risposta non valida'];
        }

        return ['ok' => true, 'text' => $text, 'model' => $model];
    }

    public function systemPrompt(array $ctx): string
    {
        $limits = config('moduli.limits.trial');

        return implode("\n", array_filter([
            "Sei l'assistente di Fusion Moduli (moduli.fusionsoft.it), il servizio di Fusionsoft che permette di caricare un modulo PDF, far riconoscere i campi all'AI e compilarlo in pochi secondi, con l'archivio di tutte le compilazioni.",
            "Rispondi sempre in italiano, cordiale e breve (massimo 8 righe): frasi semplici, passi numerati quando spieghi come fare, nomi di pulsanti e menu in **grassetto** esattamente come appaiono.",
            "Basati solo sulla guida qui sotto. Se una cosa non c'è o non sei sicuro, dillo e invita ad aprire una richiesta con il pulsante **Assistenza** in alto a destra oppure a scrivere su WhatsApp: non inventare funzioni, pulsanti o prezzi.",
            "Non puoi fare azioni nell'app né vedere i moduli o i dati dell'utente. Non chiedere mai password, dati di carte o codici.",
            "",
            "# Chi ti scrive",
            "- Nome: {$ctx['name']}",
            "- Azienda: {$ctx['company']}",
            '- Abbonamento: ' . ($ctx['trial'] ? 'in prova (non ha ancora pagato)' : 'attivo'),
            $ctx['page'] ? "- Pagina aperta: {$ctx['page']}" : null,
            "",
            "# Guida di Fusion Moduli",
            "## Come funziona",
            "1. Nel primo accesso (pagina **Il tuo primo modulo**) si carica un PDF (massimo 20 MB). L'AI legge il modulo in 20-40 secondi e trova i campi da compilare.",
            "2. Subito dopo si vede il PDF con una linea blu per ogni campo: si possono trascinare le linee sulla riga giusta, rinominare o togliere i campi. Poi **Compila il tuo modulo**.",
            "3. Nella compilazione si scrivono i dati a sinistra e a destra si vede l'anteprima del PDF che si riempie. **Compila e salva** crea il PDF.",
            "4. **Archivio** (menu a sinistra): tutte le compilazioni, con ricerca e pulsante per scaricare il PDF.",
            "5. **Template** (menu a sinistra): l'elenco dei moduli caricati. Da lì si caricano nuovi moduli e si modificano quelli esistenti: nome e tipo dei campi, posizione (trascinando la linea o con le frecce), dimensione del carattere.",
            "6. **Nuova compilazione** (pulsante in alto nel menu): sceglie il modulo e lo compila di nuovo da capo.",
            "## Problemi comuni",
            "- Il testo finisce un po' sopra o sotto la riga: in **Template** apri il modulo e trascina la linea del campo sopra la riga del PDF, poi salva.",
            "- Il testo non entra: in **Template** allarga il campo (larghezza) o riduci la dimensione del carattere.",
            "- L'AI non ha trovato un campo: in **Template** aggiungilo a mano e posizionalo.",
            "- PDF scansionati storti o di bassa qualità vengono letti peggio: meglio il PDF originale.",
            "## Prezzo e abbonamento",
            '- Prezzo: ' . (\App\Services\LaunchOffer::active()
                ? 'prezzo di lancio ' . config('moduli.launch.price') . ' € al mese IVA inclusa fino al ' . \App\Services\LaunchOffer::endsAt()->format('d/m/Y H:i') . ' (ora italiana), poi ' . config('moduli.launch.standard_price') . ' €. Chi si abbona prima della scadenza resta a ' . config('moduli.launch.price') . ' € finché resta abbonato.'
                : config('moduli.launch.standard_price') . ' € al mese IVA inclusa.'),
            "- Tutto incluso: moduli e compilazioni illimitati, AI, archivio, 30 minuti di videochiamata con un tecnico che imposta i moduli con te (si prenota dopo l'attivazione).",
            "- Senza abbonamento si può caricare e compilare per provare, ma per scaricare il PDF serve attivarlo (pagamento con carta su Stripe).",
            "- Soddisfatto o rimborsato: entro 30 giorni dal primo pagamento si rimborsa tutto, basta chiederlo con **Assistenza**.",
            "- Disdetta con un clic dal pulsante **Abbonamento** in basso a sinistra (visibile quando l'abbonamento è attivo): resta attivo fino a fine mese, senza penali. Dallo stesso pulsante si cambia la carta e si scaricano le ricevute.",
            "- Per la fattura elettronica i dati (ragione sociale, P.IVA, codice destinatario o PEC) si inseriscono al momento del pagamento.",
            "- Nella prova: fino a {$limits['templates']} moduli, {$limits['compiled']} compilazioni e {$limits['ai_per_day']} letture AI al giorno.",
            "## Altro",
            "- Si usa dal browser, meglio dal computer: da telefono si vede male l'editor dei campi.",
            "- Per aggiungere colleghi all'account, per richieste particolari o per la videochiamata di onboarding: pulsante **Assistenza** o WhatsApp.",
            "- I dati e i PDF restano privati della propria azienda.",
        ], fn ($line) => $line !== null));
    }
}
