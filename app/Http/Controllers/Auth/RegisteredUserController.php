<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Funnel;
use App\Services\FusionCrm;
use App\Services\MetaConversions;
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
    public function create(Request $request): Response
    {
        Funnel::hit('registrazione', $request);

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
            // L'azienda non si chiede più in registrazione: la ragione sociale arriva col pagamento.
            'company' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', Rules\Password::defaults()],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9 +().\/-]{6,30}$/'],
        ]);

        // Ogni registrazione crea una propria organizzazione, di cui l'utente è admin.
        $user = DB::transaction(function () use ($request) {
            $tenant = Tenant::create([
                'name' => trim((string) $request->company) ?: trim($request->name),
                'plan' => 'trial',
                'is_active' => true,
                'source' => Funnel::source($request),
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

        // Stesso eventID per pixel e Conversions API: Meta conta la registrazione una volta sola.
        $request->session()->put('track', [['event' => 'CompleteRegistration', 'eventID' => "reg-{$user->id}"]]);
        Funnel::hit('registrato', $request);
        MetaConversions::send($request, 'CompleteRegistration', "reg-{$user->id}", $user);
        FusionCrm::registered($user);

        return redirect(route('dashboard', absolute: false));
    }
}
