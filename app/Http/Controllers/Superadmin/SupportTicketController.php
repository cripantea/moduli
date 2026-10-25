<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupportTicketController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->string('stato')->toString() ?: 'aperto';

        return Inertia::render('Superadmin/Tickets', [
            'tickets' => SupportTicket::with(['tenant:id,name', 'user:id,name,email'])
                ->when($status !== 'tutti', fn ($q) => $q->where('status', $status))
                ->latest()
                ->paginate(30)
                ->withQueryString(),
            'stato'  => $status,
            'aperti' => SupportTicket::where('status', 'aperto')->count(),
        ]);
    }

    public function update(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $ticket->update($request->validate(['status' => ['required', 'in:aperto,chiuso']]));

        return back();
    }
}
