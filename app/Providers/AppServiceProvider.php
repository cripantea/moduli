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

        // Ogni estrazione invia il PDF all'API Anthropic: limite per utente e per tenant.
        RateLimiter::for('ai-extraction', function (Request $request) {
            $user = $request->user();

            return [
                Limit::perMinute(5)->by('ai-min:' . $user->id)
                    ->response(fn () => response()->json(['error' => 'Troppe estrazioni AI ravvicinate. Riprova tra un minuto.'], 429)),
                Limit::perDay(100)->by('ai-day:' . ($user->tenant_id ?? 'platform'))
                    ->response(fn () => response()->json(['error' => 'Limite giornaliero di estrazioni AI raggiunto.'], 429)),
            ];
        });

        // L'anteprima esegue Ghostscript sul server.
        RateLimiter::for('pdf-preview', function (Request $request) {
            return Limit::perMinute(60)->by('preview:' . $request->user()->id)
                ->response(fn () => response()->json(['error' => 'Troppe richieste di anteprima. Riprova tra poco.'], 429));
        });
    }
}
