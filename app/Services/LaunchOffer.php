<?php

namespace App\Services;

use Illuminate\Support\Carbon;

/**
 * Prezzo di lancio con scadenza vera: prima della scadenza 29 €/mese, dopo 49 €/mese.
 * La scadenza è una sola (config moduli.launch.ends_at, ora di Roma) e vale per sito, pagamento e creatività.
 */
class LaunchOffer
{
    public static function endsAt(): Carbon
    {
        return Carbon::parse(config('moduli.launch.ends_at'), 'Europe/Rome');
    }

    public static function active(): bool
    {
        return now()->lt(self::endsAt());
    }

    public static function price(): int
    {
        return self::active() ? (int) config('moduli.launch.price') : (int) config('moduli.launch.standard_price');
    }

    /** Prezzo Stripe da usare adesso. Senza il prezzo pieno configurato resta quello di lancio (a favore del cliente). */
    public static function stripePrice(): ?string
    {
        return self::active()
            ? config('services.stripe.price')
            : (config('services.stripe.price_standard') ?: config('services.stripe.price'));
    }

    public static function toArray(): array
    {
        return [
            'attiva'       => self::active(),
            'scade'        => self::endsAt()->toIso8601String(),
            'prezzo'       => self::price(),
            'prezzo_pieno' => (int) config('moduli.launch.standard_price'),
            'lancio'       => (int) config('moduli.launch.price'),
        ];
    }
}
