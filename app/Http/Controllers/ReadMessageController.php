<?php

namespace App\Http\Controllers;

use App\Events\MessageRead;
use App\Models\Chat;
use App\Models\ChatRead;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReadMessageController extends Controller
{
    public function store(Request $request, Chat $chat): JsonResponse
    {
        $user = $request->user();
        $chatConnection = (new Chat())->getConnectionName();

        abort_unless(
            DB::connection($chatConnection)
                ->table('chat_participants')
                ->where('chat_id', $chat->id)
                ->where('user_id', $user->id)
                ->exists(),
            403
        );

        $validated = $request->validate([
            'last_read_message_id' => ['nullable', 'string', 'size:24'],
        ]);

        $message = $this->resolveMessage($chat->id, $validated['last_read_message_id'] ?? null);

        if (! $message) {
            return response()->json([
                'success' => true,
                'data' => null,
            ]);
        }

        $lastReadMessageId = (string) ($message->_id ?? $message->id);
        $read = ChatRead::query()
            ->where('chat_id', $chat->id)
            ->where('user_id', $user->id)
            ->first();

        if ($read && $read->last_read_message_id && strcmp((string) $read->last_read_message_id, $lastReadMessageId) >= 0) {
            return response()->json([
                'success' => true,
                'data' => $read,
            ]);
        }

        $read = ChatRead::query()->updateOrCreate(
            [
                'chat_id' => $chat->id,
                'user_id' => $user->id,
            ],
            [
                'last_read_message_id' => $lastReadMessageId,
                'last_read_at' => now(),
            ]
        );

        broadcast(new MessageRead(
            $chat->id,
            $user->id,
            $lastReadMessageId,
            $read->last_read_at->toISOString(),
        ))->toOthers();

        return response()->json([
            'success' => true,
            'data' => $read,
        ]);
    }

    private function resolveMessage(string $chatId, ?string $messageId): ?Message
    {
        if ($messageId) {
            return Message::query()
                ->where('chat_id', $chatId)
                ->where('_id', $messageId)
                ->first();
        }

        return Message::query()
            ->where('chat_id', $chatId)
            ->orderByDesc('created_at')
            ->first();
    }
}
