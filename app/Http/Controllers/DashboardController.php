<?php

namespace App\Http\Controllers;

use App\Models\CompiledModule;
use App\Models\ModuleTemplate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index()
    {
        return redirect()->route('compiled.index');
    }
}
