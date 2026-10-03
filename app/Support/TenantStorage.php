<?php

namespace App\Support;

use App\Models\ModuleTemplate;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Chiavi S3 legate al tenant.
 *
 * I nuovi file stanno sotto tenants/{tenant_id}/ (tenants/platform/ per il superadmin).
 * Le chiavi storiche (templates/…, moduli/…) restano valide solo se appartengono
 * a un template che l'utente può già vedere tramite il TenantScope.
 */
class TenantStorage
{
    public static function prefix(?int $tenantId): string
    {
        return 'tenants/' . ($tenantId ?? 'platform') . '/';
    }

    public static function newTemplateKey(User $user): string
    {
        return self::prefix($user->tenant_id) . 'templates/' . Str::uuid() . '.pdf';
    }

    public static function newCompiledKey(ModuleTemplate $template): string
    {
        return self::prefix($template->tenant_id) . 'moduli/' . $template->id . '/' . Str::uuid() . '.pdf';
    }

    /**
     * Una chiave di PDF matrice è utilizzabile dall'utente se è stata caricata nel suo
     * spazio, oppure se è già la matrice di un template a lui visibile.
     */
    public static function canUseTemplateKey(User $user, string $key): bool
    {
        $ownUpload = '#^' . preg_quote(self::prefix($user->tenant_id), '#')
            . 'templates/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.pdf$#';

        if (preg_match($ownUpload, $key)) {
            return true;
        }

        return ModuleTemplate::where('pdf_template_s3_key', $key)->exists();
    }
}
