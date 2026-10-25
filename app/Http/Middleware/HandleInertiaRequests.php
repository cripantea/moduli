<?php

namespace App\Http\Middleware;

use App\Services\LaunchOffer;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user()?->only([
                    'id', 'name', 'email', 'role', 'tenant_id', 'is_active', 'email_verified_at',
                ]),
                'trial' => (bool) $request->user()?->tenant?->isTrial(),
                // Cliente pagante con abbonamento Stripe: può gestirlo (disdetta, carta, fatture).
                'billing' => (bool) $request->user()?->tenant?->stripe_customer_id && ! $request->user()->tenant->isTrial(),
            ],
            // Offerta di lancio (prezzo, scadenza) per conto alla rovescia e prezzi in tutte le pagine.
            'offerta' => fn () => LaunchOffer::toArray(),
            // Eventi per il pixel Meta (es. registrazione), inviati una sola volta.
            'track' => fn () => $request->session()->pull('track', []),
            'flash' => [
                'success' => $request->session()->get('success'),
                'error'   => $request->session()->get('error'),
            ],
        ];
    }
}
