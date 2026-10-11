<?php

use App\Services\FormLayout\PdfLayoutAnalyzer;
use App\Support\Ghostscript;

function makeSampleForm(): string
{
    $pdf = new \Fpdf\Fpdf('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->Text(15, 40, 'Il sottoscritto .................................................. nato a ........................');
    $pdf->Text(15, 60, 'Email:');
    $pdf->Line(30, 60.5, 120, 60.5);
    $pdf->SetFont('Helvetica', '', 8);
    $pdf->Text(15, 79, 'Data del sinistro');
    $pdf->Rect(15, 80, 45, 8);
    $pdf->Text(15, 100, 'Testimoni?');
    $pdf->Rect(40, 96.5, 4, 4);

    $path = tempnam(sys_get_temp_dir(), 'form_') . '.pdf';
    $pdf->Output('F', $path);

    return $path;
}

beforeEach(function () {
    try {
        Ghostscript::binary();
    } catch (RuntimeException) {
        $this->markTestSkipped('Ghostscript non installato.');
    }
});

test('trova puntini, righe, caselle e checkbox con il loro contesto', function () {
    $path = makeSampleForm();

    try {
        $pages = (new PdfLayoutAnalyzer)->analyze($path, 5);
    } finally {
        @unlink($path);
    }

    expect($pages)->toHaveCount(1);
    $slots = collect($pages[0]['slots']);
    $mm = 72 / 25.4;

    expect($slots->pluck('kind')->all())->toBe(['dots', 'dots', 'line', 'box', 'checkbox']);

    $dots = $slots->firstWhere('kind', 'dots');
    expect($dots['left'])->toBe('Il sottoscritto')
        ->and($dots['right'])->toBe('nato a')
        ->and($dots['y'] / $mm)->toEqualWithDelta(40, 0.5);

    $line = $slots->firstWhere('kind', 'line');
    expect($line['left'])->toBe('Email:')
        ->and($line['x0'] / $mm)->toEqualWithDelta(30, 0.6)
        ->and($line['x1'] / $mm)->toEqualWithDelta(120, 0.6)
        ->and($line['y'] / $mm)->toEqualWithDelta(60.5, 0.6);

    $box = $slots->firstWhere('kind', 'box');
    expect($box['above'])->toBe('Data del sinistro')
        ->and($box['top'] / $mm)->toEqualWithDelta(80, 0.6)
        ->and($box['y'] / $mm)->toEqualWithDelta(88, 0.6);

    expect($slots->firstWhere('kind', 'checkbox')['left'])->toBe('Testimoni?');

    expect($pages[0]['text_lines'][0]['text'])->toContain('[S1]')->toContain('[S2]');
});

test('aggiunge i campi del rilevatore neurale solo dove la geometria non ha trovato nulla', function () {
    $path = makeSampleForm();

    // Rilevatore finto: un duplicato della riga "Email" e uno spazio bianco a fondo pagina.
    $detector = new class extends \App\Services\FormLayout\FormFieldDetector {
        public function detect(array $pngPaths): ?array
        {
            $mmX = fn (float $mm) => $mm / 210;
            $mmY = fn (float $mm) => $mm / 297;

            return [[
                ['cls' => 'text', 'conf' => 0.8, 'x0' => $mmX(30), 'y0' => $mmY(54), 'x1' => $mmX(120), 'y1' => $mmY(61)],
                ['cls' => 'text', 'conf' => 0.7, 'x0' => $mmX(40), 'y0' => $mmY(200), 'x1' => $mmX(150), 'y1' => $mmY(208)],
            ]];
        }
    };

    try {
        $pages = (new PdfLayoutAnalyzer($detector))->analyze($path, 5);
    } finally {
        @unlink($path);
    }

    $slots = collect($pages[0]['slots']);
    $mm = 72 / 25.4;

    expect($slots)->toHaveCount(6);

    $area = $slots->last();
    expect($area['kind'])->toBe('area')
        ->and($area['detected'])->toBe(0.7)
        ->and($area['top'] / $mm)->toEqualWithDelta(200, 0.1)
        ->and($area['y'] / $mm)->toEqualWithDelta(208, 0.1);
});
