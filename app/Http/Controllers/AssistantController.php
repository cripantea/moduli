<?php

namespace App\Http\Controllers;

use App\Models\AssistantChat;
use App\Services\AssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssistantController extends Controller
{
    private const FALLBACK = "Al momento non riesco a rispondere. Apri una richiesta con il pulsante **Assistenza** in alto a destra o scrivici su WhatsApp: ti rispondiamo noi.";

    public function store(Request $request, AssistantService $assistant): JsonResponse
    {
        $data = $request->validate([
            'chat_id' => ['nullable', 'integer'],
            'message' => ['required', 'string', 'max:2000'],
            'page'    => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();

        $chat = ! empty($data['chat_id'])
            ? AssistantChat::where('user_id', $user->id)->findOrFail($data['chat_id'])
            : AssistantChat::create(['tenant_id' => $user->tenant_id, 'user_id' => $user->id, 'page' => $data['page'] ?? null]);

        $chat->messages()->create(['role' => 'user', 'content' => $data['message']]);

        // Solo gli ultimi scambi: bastano per il contesto e tengono basso il costo.
        $turns = $chat->messages()->latest('id')->limit(12)->get(['role', 'content'])->reverse()
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])->values()->all();

        $reply = $assistant->ask([
            'name'    => $user->name,
            'company' => $user->tenant?->name ?? '—',
            'trial'   => (bool) $user->tenant?->isTrial(),
            'page'    => $data['page'] ?? null,
        ], $turns);

        $text = $reply['ok'] ? $reply['text'] : self::FALLBACK;
        $chat->messages()->create(['role' => 'assistant', 'content' => $text, 'model' => $reply['model'] ?? null]);
        $chat->touch();

        return response()->json(['chat_id' => $chat->id, 'reply' => $text]);
    }
}
