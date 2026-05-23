<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function index()
    {
        $user = request()->user();
        $search = trim((string) request('search', ''));
        $chatConnection = (new Chat())->getConnectionName();

        $chats = Chat::query()
            ->whereIn('id', DB::connection($chatConnection)->table('chat_participants')
                ->select('chat_id')
                ->where('user_id', $user->id))
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->get();

        $users = collect();

        if ($search !== '') {
            $users = User::query()
                ->where('id', '!=', $user->id)
                ->where('name', 'like', "%{$search}%")
                ->orderBy('name')
                ->limit(10)
                ->get(['id', 'name', 'email'])
                ->map(function (User $foundUser) use ($chatConnection, $user) {
                    return [
                        'id' => $foundUser->id,
                        'name' => $foundUser->name,
                        'email' => $foundUser->email,
                        'chat_id' => $this->findDirectChatId($chatConnection, $user->id, $foundUser->id),
                    ];
                });
        }

        return Inertia::render('Chat/Index', [
            'chats' => $this->decorateChats($chats, $user->id, $chatConnection),
            'users' => $users,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $user = $request->user();
        $targetUserId = (int) $validated['user_id'];

        abort_if($user->id === $targetUserId, 422, 'Нельзя создать чат с самим собой.');

        $chatConnection = (new Chat())->getConnectionName();
        $existingChatId = $this->findDirectChatId($chatConnection, $user->id, $targetUserId);

        if ($existingChatId) {
            return redirect()->route('chats.show', $existingChatId);
        }

        $chat = DB::connection($chatConnection)->transaction(function () use ($chatConnection, $targetUserId, $user) {
            $chat = Chat::create([
                'type' => 'direct',
                'creator_id' => $user->id,
            ]);

            DB::connection($chatConnection)->table('chat_participants')->insert([
                [
                    'chat_id' => $chat->id,
                    'user_id' => $user->id,
                    'role' => 'member',
                    'joined_at' => now(),
                ],
                [
                    'chat_id' => $chat->id,
                    'user_id' => $targetUserId,
                    'role' => 'member',
                    'joined_at' => now(),
                ],
            ]);

            return $chat;
        });

        return redirect()->route('chats.show', $chat);
    }

    public function show(Chat $chat)
    {
        $user = request()->user();
        $chatConnection = (new Chat())->getConnectionName();

        abort_unless($this->isParticipant($chatConnection, $chat->id, $user->id), 403);

        $messages = Message::where('chat_id', $chat->id)
            ->orderBy('created_at', 'asc')
            ->get();

        return Inertia::render('Chat/Show', [
            'chat' => $this->decorateChat($chat, $user->id, $chatConnection),
            'messages' => $messages,
        ]);
    }

    public function edit(Chat $chat)
    {
        //
    }

    public function update(Request $request, Chat $chat)
    {
        //
    }

    public function destroy(Chat $chat)
    {
        //
    }

    private function decorateChats($chats, int $currentUserId, string $chatConnection)
    {
        return $chats->map(fn (Chat $chat) => $this->decorateChat($chat, $currentUserId, $chatConnection));
    }

    private function decorateChat(Chat $chat, int $currentUserId, string $chatConnection): array
    {
        $participants = DB::connection($chatConnection)
            ->table('chat_participants')
            ->join('users', 'users.id', '=', 'chat_participants.user_id')
            ->where('chat_participants.chat_id', $chat->id)
            ->orderBy('users.name')
            ->get([
                'users.id',
                'users.name',
                'users.email',
                'chat_participants.role',
            ]);

        $otherUser = $participants->firstWhere('id', '!=', $currentUserId);
        $data = $chat->toArray();
        $data['participants'] = $participants;
        $data['display_title'] = $chat->type === 'direct'
            ? ($otherUser->name ?? 'Личный чат')
            : ($chat->title ?? 'Групповой чат');

        return $data;
    }

    private function findDirectChatId(string $chatConnection, int $firstUserId, int $secondUserId): ?string
    {
        $chatIds = DB::connection($chatConnection)
            ->table('chat_participants')
            ->whereIn('user_id', [$firstUserId, $secondUserId])
            ->groupBy('chat_id')
            ->havingRaw('COUNT(DISTINCT user_id) = 2')
            ->pluck('chat_id');

        if ($chatIds->isEmpty()) {
            return null;
        }

        return DB::connection($chatConnection)
            ->table('chats')
            ->whereIn('id', $chatIds)
            ->where('type', 'direct')
            ->value('id');
    }

    private function isParticipant(string $chatConnection, string $chatId, int $userId): bool
    {
        return DB::connection($chatConnection)
            ->table('chat_participants')
            ->where('chat_id', $chatId)
            ->where('user_id', $userId)
            ->exists();
    }
}
