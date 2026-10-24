<?php

namespace App\Http\Controllers;

use App\Models\ModuleTemplate;
use Illuminate\Http\RedirectResponse;

class DashboardController extends Controller
{
    public function index(): RedirectResponse
    {
        // Chi non ha ancora un modulo parte dall'onboarding: carica il primo PDF.
        if (auth()->user()->tenant_id && ! ModuleTemplate::exists()) {
            return redirect()->route('onboarding');
        }

        return redirect()->route('compiled.index');
    }
}
