<?php

namespace App\Services;

use App\Models\ModuleTemplate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;

if (! class_exists('FPDF', false)) {
    class_alias(\Fpdf\Fpdf::class, 'FPDF');
}

class PdfFormFillerService
{
    /**
     * Compiles a module template with the given values, uploads to S3 and returns the S3 key.
     */
    public function compile(ModuleTemplate $template, array $values): string
    {
        $inputTmp  = null;
        $compatTmp = null;
        $outputTmp = null;

        try {
            if (! $template->pdf_template_s3_key) {
                throw new \RuntimeException("Template #{$template->id} non ha un PDF matrice configurato.");
            }

            $pdfContent = Storage::disk('s3')->get($template->pdf_template_s3_key);
            if ($pdfContent === false || $pdfContent === null) {
                throw new \RuntimeException("PDF matrice non trovato su S3: {$template->pdf_template_s3_key}");
            }

            $inputTmp  = tempnam(sys_get_temp_dir(), 'fpdi_in_');
            $outputTmp = $inputTmp . '_out.pdf';
            file_put_contents($inputTmp, $pdfContent);

            $compatTmp  = $this->toCompatPdf($inputTmp);
            $sourceFile = $compatTmp ?? $inputTmp;

            $pdf = new Fpdi('P', 'mm', 'A4');
            $pdf->SetAutoPageBreak(false);
            $pdf->SetMargins(0, 0, 0);

            $pageCount = $pdf->setSourceFile($sourceFile);

            $schema = $template->fields_schema ?? [];
            $fieldsByPage = [];
            foreach ($schema as $field) {
                $page = max(1, (int) ($field['page'] ?? 1));
                $fieldsByPage[$page][] = $field;
            }

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $tpl = $pdf->importPage($pageNo);
                $pdf->AddPage('P', 'A4');
                $pdf->useTemplate($tpl, 0, 0, 210, 297);

                $fields = $fieldsByPage[$pageNo] ?? [];
                if (empty($fields)) {
                    continue;
                }

                $fontSize = max(6, min(24, (int) ($template->font_size ?? 10)));
                $pdf->SetFont('Helvetica', '', $fontSize);
                $pdf->SetTextColor(0, 0, 0);

                foreach ($fields as $field) {
                    $fieldName = $field['name'] ?? '';
                    $rawValue  = (string) ($values[$fieldName] ?? '');
                    if ($rawValue === '') {
                        continue;
                    }

                    $mmX = ((float) ($field['x'] ?? 0)) / 100.0 * 210.0;
                    $mmY = ((float) ($field['y'] ?? 0)) / 100.0 * 297.0;
                    $mmW = ((float) ($field['w'] ?? 20)) / 100.0 * 210.0;

                    $encoded = iconv('UTF-8', 'windows-1252//IGNORE', $rawValue);
                    if ($encoded === false || $encoded === '') {
                        $encoded = $rawValue;
                    }

                    $pdf->SetXY($mmX, $mmY);
                    $pdf->Cell($mmW, 5, $encoded, 0, 0, 'L');
                }
            }

            $pdfOutput = $pdf->Output('S');
            file_put_contents($outputTmp, $pdfOutput);

            if (! file_exists($outputTmp) || filesize($outputTmp) === 0) {
                throw new \RuntimeException('FPDI non ha prodotto un file PDF di output.');
            }

            $s3Key = sprintf('moduli/%s/%s.pdf', $template->id, (string) Str::uuid());
            Storage::disk('s3')->put($s3Key, file_get_contents($outputTmp), 'private');

            Log::info('PdfFormFillerService: compilato', [
                'template_id' => $template->id,
                'pages'       => $pageCount,
                's3_key'      => $s3Key,
            ]);

            return $s3Key;

        } catch (\RuntimeException $e) {
            Log::error('PdfFormFillerService: errore', ['template_id' => $template->id ?? null, 'errore' => $e->getMessage()]);
            return '';

        } catch (\Throwable $e) {
            Log::error('PdfFormFillerService: errore grave', ['errore' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            throw $e;

        } finally {
            if ($inputTmp  && file_exists($inputTmp))  @unlink($inputTmp);
            if ($compatTmp && file_exists($compatTmp)) @unlink($compatTmp);
            if ($outputTmp && file_exists($outputTmp)) @unlink($outputTmp);
        }
    }

    public function temporaryDownloadUrl(string $s3Key): string
    {
        return Storage::disk('s3')->temporaryUrl($s3Key, now()->addDays(7));
    }

    private function toCompatPdf(string $inputPath): ?string
    {
        $gs = collect(['/usr/bin/gs', '/usr/local/bin/gs', '/opt/homebrew/bin/gs'])
            ->first(fn ($b) => file_exists($b));

        if (! $gs) {
            exec('which gs 2>/dev/null', $whichOut, $whichCode);
            $gs = ($whichCode === 0 && ! empty($whichOut[0])) ? trim($whichOut[0]) : null;
        }

        if (! $gs) {
            Log::error('PdfFormFillerService: Ghostscript not found.');
            return null;
        }

        $outputPath = $inputPath . '_compat.pdf';
        $cmd = sprintf(
            '%s -dBATCH -dNOPAUSE -dQUIET -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -sOutputFile=%s %s 2>&1',
            escapeshellarg($gs), escapeshellarg($outputPath), escapeshellarg($inputPath)
        );

        exec($cmd, $cmdOutput, $exitCode);

        if ($exitCode !== 0 || ! file_exists($outputPath) || filesize($outputPath) === 0) {
            return null;
        }

        return $outputPath;
    }
}
