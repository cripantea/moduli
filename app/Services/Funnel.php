<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Funnel di Moduli: conta quante persone fanno ogni passo, per giorno e per fonte.
 * Ogni passo si conta una volta per visita (sessione), i bot non contano.
 */
class Funnel
{
    public const STEPS = [
        'visita'        => 'Visita la landing',
        'prezzo'        => 'Arriva al prezzo',
        'clic'          => 'Clicca "Inizia ora"',
        'registrazione' => 'Apre la registrazione',
        'registrato'    => 'Si registra',
        'abbonamento'   => 'Apre la pagina abbonamento',
        'checkout'      => 'Va al pagamento',
        'pagato'        => 'Paga',
    ];

    public const SOURCES = ['meta', 'altro'];

    /** All'arrivo sulla landing: ricorda se viene da una pubblicità Meta (fbclid o utm_source). */
    public static function arrive(Request $request): void
    {
        $utm = strtolower((string) $request->query('utm_source'));
        $fromMeta = $request->filled('fbclid') || in_array($utm, ['facebook', 'fb', 'instagram', 'ig', 'meta'], true);

        if ($fromMeta) {
            $request->session()->put('funnel.fonte', 'meta');
        } elseif (! $request->session()->has('funnel.fonte')) {
            $request->session()->put('funnel.fonte', 'altro');
        }

        if ($request->filled('fbclid')) {
            $request->session()->put('funnel.fbclid', substr((string) $request->query('fbclid'), 0, 500));
            $request->session()->put('funnel.fbclid_at', now()->getTimestampMs());
        }

        self::hit('visita', $request);
    }

    public static function source(Request $request): string
    {
        return $request->hasSession() ? $request->session()->get('funnel.fonte', 'altro') : 'altro';
    }

    /** Conta un passo una volta per sessione. Con $source esplicita (es. webhook) conta sempre. */
    public static function hit(string $step, ?Request $request = null, ?string $source = null): void
    {
        if (! isset(self::STEPS[$step])) {
            return;
        }

        if ($source === null) {
            $request ??= request();
            if (self::isBot($request)) {
                return;
            }
            if ($request->hasSession()) {
                if ($request->session()->has("funnel.fatto.$step")) {
                    return;
                }
                $request->session()->put("funnel.fatto.$step", true);
            }
            $source = self::source($request);
        }

        $source = in_array($source, self::SOURCES, true) ? $source : 'altro';

        DB::table('funnel_daily')->upsert(
            [['day' => now()->toDateString(), 'step' => $step, 'source' => $source, 'total' => 1]],
            ['day', 'step', 'source'],
            ['total' => DB::raw('total + 1')],
        );
    }

    private static function isBot(Request $request): bool
    {
        return (bool) preg_match('/bot|crawl|spider|slurp|facebookexternalhit|preview|headless|lighthouse|curl|wget|python|http-client/i', (string) $request->userAgent());
    }
}
