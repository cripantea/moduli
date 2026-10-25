<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Services\Funnel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Funnel di vendita: quante persone fanno ogni passo nel periodo e quante se ne perdono.
 */
class FunnelController extends Controller
{
    public function index(Request $request): Response
    {
        $days = in_array($request->integer('giorni'), [1, 7, 14, 30], true) ? $request->integer('giorni') : 7;
        $source = in_array($request->string('fonte')->toString(), Funnel::SOURCES, true) ? $request->string('fonte')->toString() : 'tutte';
        $from = now()->subDays($days - 1)->toDateString();

        $rows = DB::table('funnel_daily')
            ->where('day', '>=', $from)
            ->when($source !== 'tutte', fn ($q) => $q->where('source', $source))
            ->get(['day', 'step', 'total']);

        $totals = $rows->groupBy('step')->map->sum('total');
        $steps = [];
        $previous = null;
        foreach (Funnel::STEPS as $key => $label) {
            $total = (int) ($totals[$key] ?? 0);
            $steps[] = [
                'key'       => $key,
                'label'     => $label,
                'total'     => $total,
                'dalPrima'  => $previous ? round($total / $previous * 100) : null,
            ];
            $previous = $total ?: null;
        }

        $byDay = [];
        foreach ($rows as $row) {
            $day = substr((string) $row->day, 0, 10);
            $byDay[$day][$row->step] = ($byDay[$day][$row->step] ?? 0) + $row->total;
        }
        krsort($byDay);

        return Inertia::render('Superadmin/Funnel', [
            'steps'     => $steps,
            'giorni'    => array_map(fn ($day, $values) => ['day' => $day] + $values, array_keys($byDay), $byDay),
            'filtri'    => ['giorni' => $days, 'fonte' => $source],
            'clarity'   => config('services.clarity.project_id') ? 'https://clarity.microsoft.com/projects/view/' . config('services.clarity.project_id') . '/dashboard' : null,
        ]);
    }
}
