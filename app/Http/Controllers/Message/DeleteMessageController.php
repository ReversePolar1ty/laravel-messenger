<?php

namespace App\Http\Controllers\Message;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteMessageRequest;
use App\Models\Chat;
use App\Services\Messages\MessageDeletionService;
use Illuminate\Http\JsonResponse;

class DeleteMessageController extends Controller
{
    public function __construct(private readonly MessageDeletionService $messages) {}

    public function __invoke(DeleteMessageRequest $request, Chat $chat, string $message): JsonResponse
    {
        $deletedMessage = $this->messages->deleteForEveryone($chat, $message, $request->user());

        return response()->json([
            'success' => true,
            'data' => $deletedMessage,
        ]);
    }
}
