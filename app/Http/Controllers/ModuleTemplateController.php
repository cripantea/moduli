<?php

namespace App\Http\Controllers;

use App\Models\ModuleTemplate;
use App\Services\AiFieldExtractorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ModuleTemplateController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Templates/Index', [
            'templates' => ModuleTemplate::orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Templates/Edit', ['template' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'                 => ['required', 'string', 'max:120'],
            'pdf_template_s3_key'  => ['nullable', 'string'],
            'fields_schema'        => ['nullable', 'array'],
            'font_size'            => ['nullable', 'integer', 'min:6', 'max:24'],
        ]);

        $template = ModuleTemplate::create($data);

        return redirect()->route('templates.edit', $template)->with('success', 'Template creato.');
    }

    public function edit(ModuleTemplate $template): Response
    {
        return Inertia::render('Templates/Edit', ['template' => $template]);
    }

    public function update(Request $request, ModuleTemplate $template): RedirectResponse
    {
        $data = $request->validate([
            'name'                => ['required', 'string', 'max:120'],
            'pdf_template_s3_key' => ['nullable', 'string'],
            'fields_schema'       => ['nullable', 'array'],
            'font_size'           => ['nullable', 'integer', 'min:6', 'max:24'],
        ]);

        $template->update($data);

        return back()->with('success', 'Template salvato.');
    }

    public function destroy(ModuleTemplate $template): RedirectResponse
    {
        if ($template->pdf_template_s3_key) {
            Storage::disk('s3')->delete($template->pdf_template_s3_key);
        }
        $template->delete();

        return redirect()->route('templates.index')->with('success', 'Template eliminato.');
    }

    public function uploadPdf(Request $request): JsonResponse
    {
        $request->validate(['pdf' => ['required', 'file', 'mimes:pdf', 'max:20480']]);

        $file  = $request->file('pdf');
        $s3Key = 'templates/' . Str::uuid() . '.pdf';
        Storage::disk('s3')->put($s3Key, file_get_contents($file->getRealPath()), 'private');

        return response()->json(['s3_key' => $s3Key]);
    }

    public function previewPage(Request $request): JsonResponse
    {
        $request->validate([
            's3_key' => ['required', 'string'],
            'page'   => ['nullable', 'integer', 'min:1'],
        ]);

        $gs = collect(['/usr/bin/gs', '/usr/local/bin/gs', '/opt/homebrew/bin/gs'])
            ->first(fn ($b) => file_exists($b));

        if (! $gs) {
            exec('which gs 2>/dev/null', $out, $code);
            $gs = ($code === 0 && ! empty($out[0])) ? trim($out[0]) : null;
        }

        if (! $gs) {
            return response()->json(['error' => 'Ghostscript non trovato sul server.'], 422);
        }

        $pdfContent = Storage::disk('s3')->get($request->s3_key);
        if (! $pdfContent) {
            return response()->json(['error' => 'PDF non trovato su S3.'], 404);
        }

        $tmpPdf = tempnam(sys_get_temp_dir(), 'prev_') . '.pdf';
        $tmpJpg = $tmpPdf . '.jpg';
        file_put_contents($tmpPdf, $pdfContent);

        $page = max(1, (int) ($request->page ?? 1));
        $cmd = sprintf(
            '%s -dBATCH -dNOPAUSE -dQUIET -sDEVICE=jpeg -r150 -dFirstPage=%d -dLastPage=%d -sOutputFile=%s %s 2>&1',
            escapeshellarg($gs), $page, $page, escapeshellarg($tmpJpg), escapeshellarg($tmpPdf)
        );

        exec($cmd, $cmdOut, $exitCode);
        @unlink($tmpPdf);

        if ($exitCode !== 0 || ! file_exists($tmpJpg)) {
            return response()->json(['error' => 'Errore generazione anteprima.'], 500);
        }

        $imgData = file_get_contents($tmpJpg);
        @unlink($tmpJpg);

        return response()->json(['image' => 'data:image/jpeg;base64,' . base64_encode($imgData)]);
    }

    public function extractFields(Request $request): JsonResponse
    {
        $request->validate(['s3_key' => ['required', 'string']]);

        $pdfContent = Storage::disk('s3')->get($request->s3_key);
        if (! $pdfContent) {
            return response()->json(['error' => 'PDF non trovato'], 404);
        }

        $extractor = app(AiFieldExtractorService::class);
        $fields    = $extractor->extract($pdfContent);

        return response()->json(['fields' => $fields]);
    }
}
