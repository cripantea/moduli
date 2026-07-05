<?php

namespace App\Http\Controllers;

use App\Models\CompiledModule;
use App\Models\ModuleTemplate;
use App\Services\PdfFormFillerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CompiledModuleController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->get('search', '');

        $query = CompiledModule::with('template:id,name')
            ->orderByDesc('created_at');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('template_name', 'like', "%{$search}%")
                  ->orWhere('values', 'like', "%{$search}%");
            });
        }

        return Inertia::render('Compiled/Index', [
            'compiled' => $query->paginate(30)->withQueryString(),
            'filters'  => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Compiled/Create', [
            'templates' => ModuleTemplate::orderBy('name')->get(['id', 'name', 'fields_schema', 'font_size']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'module_template_id' => ['required', 'exists:module_templates,id'],
            'values'             => ['nullable', 'array'],
        ]);

        $template = ModuleTemplate::findOrFail($request->module_template_id);

        $filler = app(PdfFormFillerService::class);
        $s3Key  = $filler->compile($template, $request->values ?? []);

        CompiledModule::create([
            'module_template_id' => $template->id,
            'template_name'      => $template->name,
            'values'             => $request->values ?? [],
            's3_key'             => $s3Key ?: null,
            'original_filename'  => Str::slug($template->name) . '-' . now()->format('Ymd-His') . '.pdf',
        ]);

        return redirect()->route('compiled.index')->with('success', 'Modulo compilato e salvato.');
    }

    public function download(CompiledModule $compiled)
    {
        if (! $compiled->s3_key) {
            abort(404, 'File PDF non disponibile.');
        }

        $filler = app(PdfFormFillerService::class);
        $url    = $filler->temporaryDownloadUrl($compiled->s3_key);

        return redirect($url);
    }

    public function destroy(CompiledModule $compiled): RedirectResponse
    {
        if ($compiled->s3_key) {
            \Illuminate\Support\Facades\Storage::disk('s3')->delete($compiled->s3_key);
        }
        $compiled->delete();

        return back()->with('success', 'Compilazione eliminata.');
    }
}
