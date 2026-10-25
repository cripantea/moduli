<?php

namespace App\Providers;

use App\Services\FormLayout\FormFieldDetector;
use App\Services\FormLayout\PdfLayoutAnalyzer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Il rilevatore FFDetr è opzionale: senza, l'analisi usa solo la geometria (es. nei test unitari).
        $this->app->bind(PdfLayoutAnalyzer::class, fn ($app) => new PdfLayoutAnalyzer($app->make(FormFieldDetector::class)));
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        $limits = config('moduli.limits');
        $tenantKey = fn (Request $request) => $request->user()->tenant_id ?? 'platform';
        $isTrial = fn (Request $request) => (bool) $request->user()->tenant?->isTrial();

        // Ogni estrazione invia il PDF all'API Anthropic: limite per utente, per azienda e (in prova) complessivo.
        RateLimiter::for('ai-extraction', function (Request $request) use ($limits, $tenantKey, $isTrial) {
            $trial = $isTrial($request);
            $daily = fn () => response()->json(['error' => $trial
                ? 'Hai raggiunto il limite di letture AI della prova. Attiva l\'abbonamento per continuare.'
                : 'Limite giornaliero di estrazioni AI raggiunto. Riprova domani.'], 429);

            return array_filter([
                Limit::perMinute(5)->by('ai-min:' . $request->user()->id)
                    ->response(fn () => response()->json(['error' => 'Troppe estrazioni AI ravvicinate. Riprova tra un minuto.'], 429)),
                Limit::perDay($trial ? $limits['trial']['ai_per_day'] : $limits['paid']['ai_per_day'])
                    ->by('ai-day:' . $tenantKey($request))->response($daily),
                $trial ? Limit::perDay($limits['trial_ai_per_day_total'])->by('ai-day:trial-total')
                    ->response(fn () => response()->json(['error' => 'Servizio molto richiesto in questo momento: riprova tra qualche ora.'], 429)) : null,
            ]);
        });

        // Caricamento PDF su S3.
        RateLimiter::for('pdf-upload', function (Request $request) use ($limits, $tenantKey, $isTrial) {
            $trial = $isTrial($request);

            return [
                Limit::perMinute(10)->by('upload-min:' . $request->user()->id)
                    ->response(fn () => response()->json(['error' => 'Troppi caricamenti ravvicinati. Riprova tra un minuto.'], 429)),
                Limit::perDay($trial ? $limits['trial']['uploads_per_day'] : $limits['paid']['uploads_per_day'])
                    ->by('upload-day:' . $tenantKey($request))
                    ->response(fn () => response()->json(['error' => $trial
                        ? 'Hai raggiunto il limite di caricamenti della prova. Attiva l\'abbonamento per continuare.'
                        : 'Limite giornaliero di caricamenti raggiunto. Riprova domani.'], 429)),
            ];
        });

        // Registrazioni: contro la creazione automatica di account.
        RateLimiter::for('register', function (Request $request) use ($limits) {
            $tooMany = fn () => back()->withErrors(['email' => 'Troppe registrazioni in poco tempo. Riprova più tardi.']);

            return [
                Limit::perHour($limits['registrations_per_ip_per_hour'])->by('register-ip:' . $request->ip())->response($tooMany),
                Limit::perDay($limits['registrations_per_day_total'])->by('register:total')->response($tooMany),
            ];
        });

        // L'anteprima esegue Ghostscript sul server.
        RateLimiter::for('pdf-preview', function (Request $request) {
            return Limit::perMinute(60)->by('preview:' . $request->user()->id)
                ->response(fn () => response()->json(['error' => 'Troppe richieste di anteprima. Riprova tra poco.'], 429));
        });
    }
}
