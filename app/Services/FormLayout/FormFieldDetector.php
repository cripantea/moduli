<?php

namespace App\Services\FormLayout;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

/**
 * Rilevatore neurale dei campi (FFDetr, Apache-2.0) eseguito da scripts/form-detector/detect.py.
 *
 * È opzionale: se Python o il modello non sono installati (vedi scripts/form-detector/setup.sh)
 * oppure qualcosa va storto, ritorna null e l'analisi prosegue solo con la geometria.
 */
class FormFieldDetector
{
    public function isAvailable(): bool
    {
        $python = (string) config('services.form_detector.python');
        $model  = (string) config('services.form_detector.model');

        return config('services.form_detector.enabled', true)
            && $python !== '' && is_executable($python)
            && $model !== '' && is_readable($model);
    }

    /**
     * @param  array<int, string>  $pngPaths  un'immagine per pagina
     * @return array<int, array<int, array{cls: string, conf: float, x0: float, y0: float, x1: float, y1: float}>>|null
     *         per pagina, riquadri con coordinate normalizzate 0..1; null se il rilevatore non è disponibile
     */
    public function detect(array $pngPaths): ?array
    {
        if ($pngPaths === [] || ! $this->isAvailable()) {
            return null;
        }

        $result = Process::timeout(120)->run([
            config('services.form_detector.python'),
            base_path('scripts/form-detector/detect.py'),
            config('services.form_detector.model'),
            ...$pngPaths,
        ]);

        if (! $result->successful()) {
            Log::warning('FormFieldDetector: rilevamento fallito, continuo senza', [
                'exit'   => $result->exitCode(),
                'stderr' => mb_substr($result->errorOutput(), -1000),
            ]);

            return null;
        }

        $pages = json_decode($result->output(), true);
        if (! is_array($pages) || count($pages) !== count($pngPaths)) {
            Log::warning('FormFieldDetector: output non valido', ['output' => mb_substr($result->output(), 0, 500)]);

            return null;
        }

        return $pages;
    }
}
