<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\ChatRead;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function index()
    {
        // Текущий пользователь нужен для списка его чатов и для исключения его из поиска людей.
        $user = request()->user();
        $search = trim((string) request('search', ''));

        // Чаты и участники лежат в MariaDB, поэтому явно используем соединение модели Chat.
        $chatConnection = (new Chat())->getConnectionName();

        // Показываем только чаты, где пользователь есть в таблице участников.
        $chats = Chat::query()
            ->whereIn('id', DB::connection($chatConnection)->table('chat_participants')
                ->select('chat_id')
                ->where('user_id', $user->id))
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->get();

        $users = collect();

        // Если введен поиск, ищем людей по имени и сразу отмечаем, есть ли уже direct-чат.
        if ($search !== '') {
            $users = $this->searchUsers($search, $chatConnection, $user->id);
        }

        return Inertia::render('Chat/Index', [
            'chats' => $this->decorateChats($chats, $user->id, $chatConnection),
            'users' => $users,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function search(Request $request)
    {
        $user = $request->user();
        $search = trim((string) $request->query('search', ''));
        $chatConnection = (new Chat())->getConnectionName();

        return response()->json([
            'users' => $this->searchUsers($search, $chatConnection, $user->id),
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
        // Для личного чата пока нужен только ID второго пользователя.
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $user = $request->user();
        $targetUserId = (int) $validated['user_id'];

        abort_if($user->id === $targetUserId, 422, 'Нельзя создать чат с самим собой.');

        $chatConnection = (new Chat())->getConnectionName();

        // Не создаем дубль: если direct-чат между этими людьми уже есть, открываем его.
        $existingChatId = $this->findDirectChatId($chatConnection, $user->id, $targetUserId);

        if ($existingChatId) {
            return redirect()->route('chats.show', $existingChatId);
        }

        // Чат и участники должны создаться вместе, поэтому используем транзакцию MariaDB.
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

        // UUID чата можно получить извне, поэтому перед показом проверяем участие.
        abort_unless($this->isParticipant($chatConnection, $chat->id, $user->id), 403);

        // Сообщения хранятся в MongoDB, а сам чат и участники - в MariaDB.
        $messages = Message::where('chat_id', $chat->id)
            ->orderBy('created_at', 'asc')
            ->get();

        return Inertia::render('Chat/Show', [
            'chat' => $this->decorateChat($chat, $user->id, $chatConnection),
            'messages' => $messages,
            'readStates' => $this->readStates($chat->id),
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
        // Добавляем к каждому чату участников и понятное название для фронтенда.
        return $chats->map(fn (Chat $chat) => $this->decorateChat($chat, $currentUserId, $chatConnection));
    }

    private function decorateChat(Chat $chat, int $currentUserId, string $chatConnection): array
    {
        // Участники нужны фронтенду и для отображения direct-чата именем собеседника.
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
        $currentUserRead = ChatRead::query()
            ->where('chat_id', $chat->id)
            ->where('user_id', $currentUserId)
            ->first();
        $data['last_read_message_id'] = $currentUserRead?->last_read_message_id;
        $data['has_unread'] = $chat->last_message_id
            && (! $currentUserRead?->last_read_message_id
                || strcmp((string) $currentUserRead->last_read_message_id, (string) $chat->last_message_id) < 0);
        $data['display_title'] = $chat->type === 'direct'
            ? ($otherUser->name ?? 'Личный чат')
            : ($chat->title ?? 'Групповой чат');

        return $data;
    }

    private function searchUsers(string $search, string $chatConnection, int $currentUserId)
    {
        if ($search === '') {
            return collect();
        }

        return User::query()
            ->where('id', '!=', $currentUserId)
            ->where('name', 'like', "%{$search}%")
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'email'])
            ->map(function (User $foundUser) use ($chatConnection, $currentUserId) {
                return [
                    'id' => $foundUser->id,
                    'name' => $foundUser->name,
                    'email' => $foundUser->email,
                    'chat_id' => $this->findDirectChatId($chatConnection, $currentUserId, $foundUser->id),
                ];
            });
    }

    private function findDirectChatId(string $chatConnection, int $firstUserId, int $secondUserId): ?string
    {
        // Сначала ищем chat_id, где есть оба пользователя.
        $chatIds = DB::connection($chatConnection)
            ->table('chat_participants')
            ->whereIn('user_id', [$firstUserId, $secondUserId])
            ->groupBy('chat_id')
            ->havingRaw('COUNT(DISTINCT user_id) = 2')
            ->pluck('chat_id');

        if ($chatIds->isEmpty()) {
            return null;
        }

        // Затем убеждаемся, что найденный чат именно личный, а не будущий групповой.
        return DB::connection($chatConnection)
            ->table('chats')
            ->whereIn('id', $chatIds)
            ->where('type', 'direct')
            ->value('id');
    }

    private function isParticipant(string $chatConnection, string $chatId, int $userId): bool
    {
        // Общая проверка доступа к чату по таблице участников.
        return DB::connection($chatConnection)
            ->table('chat_participants')
            ->where('chat_id', $chatId)
            ->where('user_id', $userId)
            ->exists();
    }

    private function readStates(string $chatId)
    {
        return ChatRead::query()
            ->where('chat_id', $chatId)
            ->get(['chat_id', 'user_id', 'last_read_message_id', 'last_read_at'])
            ->map(fn (ChatRead $read) => [
                'chat_id' => $read->chat_id,
                'user_id' => $read->user_id,
                'last_read_message_id' => $read->last_read_message_id,
                'last_read_at' => $read->last_read_at,
            ]);
    }
}
