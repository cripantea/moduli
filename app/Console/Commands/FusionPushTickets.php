<?php

namespace App\Console\Commands;

use App\FusionKit\KitToken;
use App\Models\SupportTicket;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Porta in Fusion Desk (CRM) le richieste di assistenza salvate qui prima del Fusion Kit.
 * Una volta sola, dopo aver configurato FUSION_CONTROL_URL e FUSION_KIT_SECRET.
 *
 *   php artisan fusion:push-tickets            (prova: elenca soltanto)
 *   php artisan fusion:push-tickets --apply    (le invia)
 */
class FusionPushTickets extends Command
{
    protected $signature = 'fusion:push-tickets {--apply : invia davvero} {--all : anche quelle già chiuse}';

    protected $description = 'Invia a Fusion Desk le richieste di assistenza di Moduli';

    public function handle(): int
    {
        $control = (string) config('fusionkit.control_url');
        if ($control === '' || ! config('fusionkit.kit_secret')) {
            $this->error('Configura prima FUSION_CONTROL_URL e FUSION_KIT_SECRET.');

            return self::FAILURE;
        }

        $tickets = SupportTicket::with(['user', 'tenant'])
            ->when(! $this->option('all'), fn ($q) => $q->where('status', '!=', 'chiuso'))
            ->orderBy('id')->get();
        $this->info($tickets->count().' richieste da inviare');

        foreach ($tickets as $t) {
            $user = $t->user;
            $this->line("  #{$t->id} {$t->subject} — ".($user?->email ?? 'utente eliminato'));
            if (! $this->option('apply') || ! $user) {
                continue;
            }
            $token = KitToken::sign([
                'product' => config('fusionkit.product'),
                'id' => (string) $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'role' => $user->role,
                'tenantId' => $t->tenant_id ? (string) $t->tenant_id : null,
                'tenantName' => $t->tenant?->name,
            ], 600);
            $res = Http::withToken($token)->asMultipart()->timeout(20)->post($control.'/api/fusion/desk/tickets', [
                ['name' => 'kind', 'contents' => 'DOMANDA'],
                ['name' => 'subject', 'contents' => mb_substr($t->subject, 0, 150)],
                ['name' => 'message', 'contents' => "Richiesta aperta in Moduli il ".$t->created_at?->format('d/m/Y H:i').":\n\n".$t->message],
                ['name' => 'path', 'contents' => (string) $t->page],
            ]);
            if ($res->successful()) {
                // Ora vive in Fusion Desk: qui si chiude, così un nuovo giro non la duplica.
                $t->update(['status' => 'chiuso']);
                $this->line('    → '.$res->json('code'));
            } else {
                $this->line('    ✗ '.$res->status().' '.$res->body());
            }
        }

        return self::SUCCESS;
    }
}
