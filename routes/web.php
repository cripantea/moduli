<?php

use App\Http\Controllers\CompiledModuleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ModuleTemplateController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Templates
    Route::get('/templates', [ModuleTemplateController::class, 'index'])->name('templates.index');
    Route::get('/templates/create', [ModuleTemplateController::class, 'create'])->name('templates.create');
    Route::post('/templates', [ModuleTemplateController::class, 'store'])->name('templates.store');
    Route::get('/templates/{template}/edit', [ModuleTemplateController::class, 'edit'])->name('templates.edit');
    Route::put('/templates/{template}', [ModuleTemplateController::class, 'update'])->name('templates.update');
    Route::delete('/templates/{template}', [ModuleTemplateController::class, 'destroy'])->name('templates.destroy');

    // Template utilities (JSON)
    Route::post('/templates/upload-pdf', [ModuleTemplateController::class, 'uploadPdf'])->name('templates.upload-pdf');
    Route::get('/templates/preview', [ModuleTemplateController::class, 'previewPage'])->name('templates.preview');
    Route::post('/templates/extract-fields', [ModuleTemplateController::class, 'extractFields'])->name('templates.extract-fields');

    // Compiled modules
    Route::get('/compiled', [CompiledModuleController::class, 'index'])->name('compiled.index');
    Route::get('/compiled/create', [CompiledModuleController::class, 'create'])->name('compiled.create');
    Route::post('/compiled', [CompiledModuleController::class, 'store'])->name('compiled.store');
    Route::get('/compiled/{compiled}/download', [CompiledModuleController::class, 'download'])->name('compiled.download');
    Route::delete('/compiled/{compiled}', [CompiledModuleController::class, 'destroy'])->name('compiled.destroy');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
