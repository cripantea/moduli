<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\AssistantChat;
use Inertia\Inertia;
use Inertia\Response;

class AssistantChatController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Superadmin/Chats', [
            'chats' => AssistantChat::with(['tenant:id,name', 'user:id,name,email'])
                ->withCount('messages')
                ->with(['messages' => fn ($q) => $q->where('role', 'user')->orderBy('id')->limit(1)])
                ->latest('updated_at')
                ->paginate(30),
            'chat' => null,
        ]);
    }

    public function show(AssistantChat $chat): Response
    {
        return Inertia::render('Superadmin/Chats', [
            'chats' => null,
            'chat'  => $chat->load(['tenant:id,name', 'user:id,name,email', 'messages']),
        ]);
    }
}
