<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * L'app dei moduli è per gli utenti delle aziende: il superadmin gestisce aziende e utenti
 * dal pannello e non vede né compila i moduli dei clienti.
 */
class EnsureTenantUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isSuperAdmin()) {
            return redirect()->route('superadmin.dashboard');
        }

        return $next($request);
    }
}
