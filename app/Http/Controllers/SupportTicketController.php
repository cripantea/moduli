<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:5000'],
            'page'    => ['nullable', 'string', 'max:255'],
        ]);

        SupportTicket::create([
            ...$data,
            'tenant_id' => $request->user()->tenant_id,
            'user_id'   => $request->user()->id,
        ]);

        return back()->with('success', 'Richiesta inviata: ti rispondiamo via email al più presto.');
    }
}
