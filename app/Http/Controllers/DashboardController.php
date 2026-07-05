<?php

namespace App\Http\Controllers;

use App\Models\CompiledModule;
use App\Models\ModuleTemplate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Dashboard', [
            'templatesCount' => ModuleTemplate::count(),
            'compiledCount'  => CompiledModule::count(),
            'recentCompiled' => CompiledModule::orderByDesc('created_at')
                ->limit(5)
                ->get(['id', 'template_name', 'original_filename', 'created_at']),
        ]);
    }
}
