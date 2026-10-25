<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Services\FusionCrm;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'company' => 'required|string|max:255|unique:'.Tenant::class.',name',
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9 +().\/-]{6,30}$/'],
        ]);

        // Ogni registrazione crea una propria organizzazione, di cui l'utente è admin.
        $user = DB::transaction(function () use ($request) {
            $tenant = Tenant::create([
                'name' => $request->company,
                'plan' => 'trial',
                'is_active' => true,
            ]);

            return User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'tenant_id' => $tenant->id,
                'role' => 'admin',
                'is_active' => true,
                'phone' => trim($request->phone),
            ]);
        });

        event(new Registered($user));

        Auth::login($user);

        $request->session()->put('track', [['event' => 'CompleteRegistration']]);
        FusionCrm::registered($user);

        return redirect(route('dashboard', absolute: false));
    }
}
