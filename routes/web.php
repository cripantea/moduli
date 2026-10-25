<?php

use App\Http\Controllers\AssistantController;
use App\Http\Controllers\CompiledModuleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ModuleTemplateController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\Superadmin\SupportTicketController as SuperadminTicketController;
use App\Http\Controllers\Superadmin\AssistantChatController as SuperadminChatController;
use App\Http\Controllers\Superadmin\SuperadminController;
use App\Http\Controllers\Superadmin\TenantController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public landing page
Route::get('/', function () {
    return Inertia::render('Landing');
})->name('landing');

// Landing dedicata alle campagne Meta.
Route::get('/prova', fn () => Inertia::render('Prova'))->name('prova');

Route::get('/termini', fn () => Inertia::render('Termini', [
    'azienda' => config('moduli.company'),
]))->name('termini');

// Stripe chiama questo indirizzo quando un pagamento va a buon fine o un abbonamento si chiude.
Route::post('/stripe/webhook', [SubscriptionController::class, 'webhook'])->name('stripe.webhook');

// Tenant app (il superadmin viene mandato al suo pannello).
// Niente 'verified': chi si registra deve arrivare subito al primo modulo.
Route::middleware(['auth', 'active', 'tenant'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/benvenuto', fn () => Inertia::render('Onboarding'))->name('onboarding');
    Route::get('/abbonamento', [SubscriptionController::class, 'show'])->name('abbonamento');
    Route::post('/abbonamento/checkout', [SubscriptionController::class, 'checkout'])->name('abbonamento.checkout');
    Route::get('/abbonamento/attivo', [SubscriptionController::class, 'activated'])->name('abbonamento.attivo');
    Route::post('/abbonamento/gestisci', [SubscriptionController::class, 'portal'])->name('abbonamento.gestisci');
    Route::post('/assistente', [AssistantController::class, 'store'])
        ->middleware('throttle:assistant')
        ->name('assistente.store');
    Route::post('/assistenza', [SupportTicketController::class, 'store'])
        ->middleware('throttle:5,60')
        ->name('assistenza.store');

    // Templates
    Route::get('/templates', [ModuleTemplateController::class, 'index'])->name('templates.index');
    Route::get('/templates/create', [ModuleTemplateController::class, 'create'])->name('templates.create');
    Route::post('/templates', [ModuleTemplateController::class, 'store'])->name('templates.store');
    Route::get('/templates/{template}/edit', [ModuleTemplateController::class, 'edit'])->name('templates.edit');
    Route::put('/templates/{template}', [ModuleTemplateController::class, 'update'])->name('templates.update');
    Route::delete('/templates/{template}', [ModuleTemplateController::class, 'destroy'])->name('templates.destroy');
    Route::post('/templates/upload-pdf', [ModuleTemplateController::class, 'uploadPdf'])
        ->middleware('throttle:pdf-upload')
        ->name('templates.upload-pdf');
    Route::get('/templates/preview', [ModuleTemplateController::class, 'previewPage'])
        ->middleware('throttle:pdf-preview')
        ->name('templates.preview');
    Route::post('/templates/extract-fields', [ModuleTemplateController::class, 'extractFields'])
        ->middleware('throttle:ai-extraction')
        ->name('templates.extract-fields');

    // Compiled modules
    Route::get('/compiled', [CompiledModuleController::class, 'index'])->name('compiled.index');
    Route::get('/compiled/create', [CompiledModuleController::class, 'create'])->name('compiled.create');
    Route::post('/compiled', [CompiledModuleController::class, 'store'])->name('compiled.store');
    Route::get('/compiled/{compiled}/download', [CompiledModuleController::class, 'download'])->name('compiled.download');
    Route::delete('/compiled/{compiled}', [CompiledModuleController::class, 'destroy'])->name('compiled.destroy');
});

// Profile: accessible before email verification so the address can be corrected
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Superadmin panel
Route::prefix('superadmin')->name('superadmin.')->middleware(['auth', 'active', 'verified', 'superadmin'])->group(function () {

    Route::get('/', [SuperadminController::class, 'dashboard'])->name('dashboard');

    // Tenants
    Route::get('/tenants', [TenantController::class, 'index'])->name('tenants.index');
    Route::post('/tenants', [TenantController::class, 'store'])->name('tenants.store');
    Route::get('/tenants/{tenant}/edit', [TenantController::class, 'edit'])->name('tenants.edit');
    Route::put('/tenants/{tenant}', [TenantController::class, 'update'])->name('tenants.update');
    Route::delete('/tenants/{tenant}', [TenantController::class, 'destroy'])->name('tenants.destroy');

    // Users
    Route::get('/users', [SuperadminController::class, 'users'])->name('users');
    Route::post('/users', [SuperadminController::class, 'storeUser'])->name('users.store');
    Route::put('/users/{user}', [SuperadminController::class, 'updateUser'])->name('users.update');
    Route::post('/users/{user}/toggle', [SuperadminController::class, 'toggleUser'])->name('users.toggle');

    // Assistenza
    Route::get('/ticket', [SuperadminTicketController::class, 'index'])->name('tickets.index');
    Route::put('/ticket/{ticket}', [SuperadminTicketController::class, 'update'])->name('tickets.update');
    Route::get('/chat', [SuperadminChatController::class, 'index'])->name('chats.index');
    Route::get('/chat/{chat}', [SuperadminChatController::class, 'show'])->name('chats.show');
});

require __DIR__ . '/auth.php';
