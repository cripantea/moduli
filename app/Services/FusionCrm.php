<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Invia a Fusion CRM le registrazioni e i pagamenti di Moduli. Il CRM fa partire la sequenza di email
 * per chi si registra e non paga, e la ferma quando arriva il tag "moduli-cliente".
 * Le chiamate partono dopo la risposta al cliente e un errore del CRM non blocca mai Moduli.
 */
class FusionCrm
{
    public static function registered(User $user): void
    {
        [$first, $last] = self::splitName($user->name);

        self::send([
            'externalId'   => "moduli-registrazione-{$user->id}",
            'firstName'    => $first,
            'lastName'     => $last,
            'email'        => $user->email,
            'phone'        => $user->phone ?: null,
            'source'       => 'Moduli',
            'tags'         => ['moduli', 'moduli-prova'],
            'customFields' => array_filter([
                'moduli_azienda' => $user->tenant?->name,
                'moduli_settore' => $user->tenant?->settings['settore'] ?? null,
                'moduli_volume'  => $user->tenant?->settings['volume'] ?? null,
            ]),
            'company'      => $user->tenant?->name,
        ]);
    }

    public static function paid(Tenant $tenant): void
    {
        $admin = $tenant->users()->orderBy('id')->first();
        if (! $admin) {
            return;
        }

        self::send([
            'externalId' => "moduli-pagamento-{$tenant->id}",
            'email'      => $admin->email,
            'phone'      => $admin->phone ?: null,
            'source'     => 'Moduli',
            'tags'       => ['moduli', 'moduli-cliente'],
        ]);
    }

    private static function send(array $payload): void
    {
        $url = config('services.fusion_crm.webhook_url');
        $token = config('services.fusion_crm.webhook_token');
        if (! $url || ! $token) {
            return;
        }

        dispatch(function () use ($url, $token, $payload) {
            try {
                Http::withHeaders(['X-Fusion-Token' => $token])->timeout(10)->post($url, array_filter($payload, fn ($v) => $v !== null))->throw();
            } catch (\Throwable $e) {
                Log::warning('Fusion CRM: invio non riuscito', ['externalId' => $payload['externalId'], 'error' => $e->getMessage()]);
            }
        })->afterResponse();
    }

    private static function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2);

        return [$parts[0] ?? $name, $parts[1] ?? null];
    }
}
