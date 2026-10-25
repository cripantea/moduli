<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Conversions API di Meta: manda registrazioni e pagamenti anche dal server, con email e telefono
 * cifrati (SHA-256), così Meta li conta anche quando il browser blocca il pixel. Stesso eventID del
 * pixel, quindi Meta li conta una volta sola. Parte solo se il visitatore ha accettato i cookie e
 * dopo la risposta al cliente: un errore di Meta non blocca mai Moduli.
 */
class MetaConversions
{
    public static function send(Request $request, string $event, string $eventId, User $user, array $customData = []): void
    {
        $pixel = config('services.meta.pixel_id');
        $token = config('services.meta.capi_token');
        if (! $pixel || ! $token || $request->cookie('cookie-consenso') !== 'si') {
            return;
        }

        $userData = array_filter([
            'em'                => [self::hash(strtolower(trim($user->email)))],
            'ph'                => $user->phone ? [self::hash(self::phone($user->phone))] : null,
            'external_id'       => [self::hash((string) $user->id)],
            'fn'                => [self::hash(strtolower(strtok(trim($user->name), ' ')))],
            'country'           => [self::hash('it')],
            'client_ip_address' => $request->ip(),
            'client_user_agent' => $request->userAgent(),
            'fbp'               => $request->cookie('_fbp'),
            'fbc'               => $request->cookie('_fbc') ?: self::fbc($request),
        ]);

        $payload = [
            'data' => [[
                'event_name'       => $event,
                'event_time'       => time(),
                'event_id'         => $eventId,
                'action_source'    => 'website',
                'event_source_url' => $request->headers->get('referer') ?: $request->fullUrl(),
                'user_data'        => $userData,
                'custom_data'      => $customData ?: null,
            ]],
        ];
        $payload['data'][0] = array_filter($payload['data'][0], fn ($v) => $v !== null);

        dispatch(function () use ($pixel, $token, $payload, $event) {
            try {
                Http::timeout(10)->post("https://graph.facebook.com/v26.0/{$pixel}/events?access_token={$token}", $payload)->throw();
            } catch (\Throwable $e) {
                Log::warning('Meta Conversions API: invio non riuscito', ['event' => $event, 'error' => $e->getMessage()]);
            }
        })->afterResponse();
    }

    private static function hash(string $value): string
    {
        return hash('sha256', $value);
    }

    /** Solo cifre col prefisso internazionale: "333 123 4567" → "393331234567". */
    private static function phone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        if (str_starts_with($digits, '00')) {
            return substr($digits, 2);
        }

        return str_starts_with($digits, '39') && strlen($digits) > 10 ? $digits : '39' . $digits;
    }

    /** Senza cookie _fbc, lo ricostruisce dal fbclid salvato all'arrivo sulla landing. */
    private static function fbc(Request $request): ?string
    {
        if (! $request->hasSession() || ! $request->session()->has('funnel.fbclid')) {
            return null;
        }

        return 'fb.1.' . $request->session()->get('funnel.fbclid_at') . '.' . $request->session()->get('funnel.fbclid');
    }
}
