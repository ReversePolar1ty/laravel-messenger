<?php

namespace Tests\Feature\Message;

use App\Models\Chat;
use App\Models\ChatRead;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MessageTest extends TestCase
{
    private array $createdChatIds = [];
    private array $createdUserIds = [];

    public function test_authorized_user_can_send_message(): void
    {
        Event::fake();

        $user = $this->createUser();
        $chat = $this->createChatWithParticipants($user);

        $response = $this->actingAs($user)
            ->postJson("/chats/{$chat->id}/messages", [
                'chat_id' => $chat->id,
                'type' => 'text',
                'text' => 'Hello, how are you?',
            ]);

        $response->assertCreated();

        $this->assertTrue(Message::query()
            ->where('chat_id', $chat->id)
            ->where('user_id', $user->id)
            ->where('text', 'Hello, how are you?')
            ->exists());
    }

    public function test_sender_can_delete_last_message_and_chat_last_message_falls_back(): void
    {
        Event::fake();

        $user = $this->createUser();
        $chat = $this->createChatWithParticipants($user);
        $firstMessage = $this->createMessage($chat, $user, 'First message');
        $lastMessage = $this->createMessage($chat, $user, 'Last message');

        $chat->update([
            'last_message_id' => (string) $lastMessage->_id,
            'last_message_text' => $lastMessage->text,
            'last_message_at' => $lastMessage->created_at,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/chats/{$chat->id}/messages/{$lastMessage->_id}");

        $response->assertOk()
            ->assertJsonPath('data.is_deleted', true);

        $deletedMessage = Message::query()->find((string) $lastMessage->_id);

        $this->assertTrue($deletedMessage->is_deleted);
        $this->assertNull($deletedMessage->text);

        $chat->refresh();

        $this->assertSame((string) $firstMessage->_id, $chat->last_message_id);
        $this->assertSame('First message', $chat->last_message_text);
    }

    public function test_sender_can_delete_non_last_message_without_changing_chat_last_message(): void
    {
        Event::fake();

        $user = $this->createUser();
        $chat = $this->createChatWithParticipants($user);
        $firstMessage = $this->createMessage($chat, $user, 'First message');
        $lastMessage = $this->createMessage($chat, $user, 'Last message');

        $chat->update([
            'last_message_id' => (string) $lastMessage->_id,
            'last_message_text' => $lastMessage->text,
            'last_message_at' => $lastMessage->created_at,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/chats/{$chat->id}/messages/{$firstMessage->_id}");

        $response->assertOk();

        $chat->refresh();

        $this->assertSame((string) $lastMessage->_id, $chat->last_message_id);
        $this->assertSame('Last message', $chat->last_message_text);
    }

    public function test_participant_cannot_delete_another_users_message(): void
    {
        Event::fake();

        $sender = $this->createUser();
        $otherUser = $this->createUser();
        $chat = $this->createChatWithParticipants($sender, $otherUser);
        $message = $this->createMessage($chat, $sender, 'Sender message');

        $response = $this->actingAs($otherUser)
            ->deleteJson("/chats/{$chat->id}/messages/{$message->_id}");

        $response->assertForbidden();

        $freshMessage = Message::query()->find((string) $message->_id);

        $this->assertFalse($freshMessage->is_deleted);
        $this->assertSame('Sender message', $freshMessage->text);
    }

    protected function tearDown(): void
    {
        try {
            $this->deleteTestRecords();
        } finally {
            parent::tearDown();
        }
    }

    private function createUser(): User
    {
        $user = User::factory()->create();

        $this->createdUserIds[] = $user->id;

        return $user;
    }

    private function createChatWithParticipants(User ...$users): Chat
    {
        $chat = Chat::factory()->create([
            'type' => 'direct',
            'creator_id' => null,
            'last_message_id' => null,
            'last_message_text' => null,
            'last_message_at' => null,
        ]);

        $this->createdChatIds[] = $chat->id;

        foreach ($users as $user) {
            DB::connection($chat->getConnectionName())
                ->table('chat_participants')
                ->insert([
                    'chat_id' => $chat->id,
                    'user_id' => $user->id,
                    'role' => 'member',
                    'joined_at' => now(),
                ]);
        }

        return $chat;
    }

    private function createMessage(Chat $chat, User $user, string $text): Message
    {
        return Message::create([
            'chat_id' => $chat->id,
            'user_id' => $user->id,
            'sender_id' => $user->id,
            'type' => 'text',
            'text' => $text,
        ]);
    }

    private function deleteTestRecords(): void
    {
        if ($this->createdChatIds !== []) {
            Message::query()
                ->whereIn('chat_id', $this->createdChatIds)
                ->delete();

            ChatRead::query()
                ->whereIn('chat_id', $this->createdChatIds)
                ->delete();

            DB::connection((new Chat())->getConnectionName())
                ->table('chat_participants')
                ->whereIn('chat_id', $this->createdChatIds)
                ->delete();

            Chat::query()
                ->whereIn('id', $this->createdChatIds)
                ->delete();
        }

        if ($this->createdUserIds !== []) {
            User::query()
                ->whereIn('id', $this->createdUserIds)
                ->delete();
        }
    }
}
