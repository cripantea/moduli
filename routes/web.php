<?php

use App\Http\Controllers\CompiledModuleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ModuleTemplateController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Superadmin\SuperadminController;
use App\Http\Controllers\Superadmin\TenantController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public landing page
Route::get('/', function () {
    return Inertia::render('Landing');
})->name('landing');

// Tenant app
Route::middleware(['auth', 'active', 'verified'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Templates
    Route::get('/templates', [ModuleTemplateController::class, 'index'])->name('templates.index');
    Route::get('/templates/create', [ModuleTemplateController::class, 'create'])->name('templates.create');
    Route::post('/templates', [ModuleTemplateController::class, 'store'])->name('templates.store');
    Route::get('/templates/{template}/edit', [ModuleTemplateController::class, 'edit'])->name('templates.edit');
    Route::put('/templates/{template}', [ModuleTemplateController::class, 'update'])->name('templates.update');
    Route::delete('/templates/{template}', [ModuleTemplateController::class, 'destroy'])->name('templates.destroy');
    Route::post('/templates/upload-pdf', [ModuleTemplateController::class, 'uploadPdf'])->name('templates.upload-pdf');
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
});

require __DIR__ . '/auth.php';
