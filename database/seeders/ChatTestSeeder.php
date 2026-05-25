<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Chat;
use App\Models\Message;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ChatTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Очищаем старые данные для чистоты теста (по желанию)
        // Message::truncate(); // В MongoDB

        $this->command->info('Генерация пользователей...');

        // 1. Создаем 4 тестовых пользователей в MariaDB
        $users = collect([
            [
                'name' => 'Иван Иванов',
                'email' => 'ivan@example.com',
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'Мария Сидорова',
                'email' => 'maria@example.com',
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'Алексей Петров',
                'email' => 'alex@example.com',
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'Техподдержка',
                'email' => 'support@example.com',
                'password' => Hash::make('password'),
            ],
        ])->map(function ($userData) {
            return User::firstOrCreate(['email' => $userData['email']], $userData);
        });

        $ivan = $users->firstWhere('email', 'ivan@example.com');
        $maria = $users->firstWhere('email', 'maria@example.com');
        $alex = $users->firstWhere('email', 'alex@example.com');

        // ==========================================
        // Приватный чат
        // ==========================================
        $this->command->info('Создание приватного чата...');

        $directChat = Chat::create([
            'type' => 'direct',
            'creator_id' => $ivan->id,
        ]);

        // Добавляем участников в MariaDB
        DB::table('chat_participants')->insert([
            ['chat_id' => $directChat->id, 'user_id' => $ivan->id, 'role' => 'member', 'joined_at' => now()],
            ['chat_id' => $directChat->id, 'user_id' => $maria->id, 'role' => 'member', 'joined_at' => now()],
        ]);

        // Генерируем историю сообщений в MongoDB
        $this->command->info('Наполнение приватного чата сообщениями...');
        $this->seedDirectChatMessages($directChat, $ivan, $maria);


        // ==========================================
        // СЦЕНАРИЙ 2: Групповой чат
        // ==========================================
        $this->command->info('Создание группового чата...');

        $groupChat = Chat::create([
            'type' => 'group',
            'title' => 'Команда стартапа 🚀',
            'creator_id' => $ivan->id,
        ]);

        // Добавляем участников в MariaDB
        DB::table('chat_participants')->insert([
            ['chat_id' => $groupChat->id, 'user_id' => $ivan->id, 'role' => 'admin', 'joined_at' => now()],
            ['chat_id' => $groupChat->id, 'user_id' => $maria->id, 'role' => 'member', 'joined_at' => now()],
            ['chat_id' => $groupChat->id, 'user_id' => $alex->id, 'role' => 'member', 'joined_at' => now()],
        ]);

        // Генерируем историю сообщений в MongoDB
        $this->command->info('Наполнение группового чата сообщениями...');
        $this->seedGroupChatMessages($groupChat, $ivan, $maria, $alex);

        $this->command->info('Готово! Тестовые данные успешно созданы.');
    }

    /**
     * Сообщения для тет-а-тет чата
     */
    private function seedDirectChatMessages(Chat $chat, User $ivan, User $maria): void
    {
        $time = Carbon::now()->subHours(2);

        $messages = [
            ['sender_id' => $ivan->id, 'text' => 'Привет, Мария! Ты посмотрела макеты нового мессенджера?'],
            ['sender_id' => $maria->id, 'text' => 'Привет! Да, выглядит круто. Но у меня есть пара вопросов по UI.'],
            ['sender_id' => $ivan->id, 'text' => 'Давай обсудим. Что именно смущает?'],
            ['sender_id' => $maria->id, 'text' => 'Кнопка отправки файлов на мобилках слишком мелкая, промахиваюсь.'],
            ['sender_id' => $ivan->id, 'text' => 'Понял, поправлю размер. Скину скриншот через пару минут.', 'type' => 'image', 'attachments' => [
                [
                    'id' => 'att_' . Str::random(8),
                    'type' => 'image',
                    'url' => 'https://placehold.co/600x400.png',
                    'file_name' => 'fix_preview.png',
                    'file_size' => 45200
                ]
            ]],
        ];

        $lastMessage = null;

        foreach ($messages as $msgData) {
            $time->addMinutes(5);

            $lastMessage = Message::create([
                'chat_id' => $chat->id,
                'sender_id' => $msgData['sender_id'],
                'type' => $msgData['type'] ?? 'text',
                'text' => $msgData['text'],
                'attachments' => $msgData['attachments'] ?? [],
                'created_at' => $time->clone(),
                'updated_at' => $time->clone(),
            ]);
        }

        // Обновляем денормализацию в MariaDB
        if ($lastMessage) {
            $chat->update([
                'last_message_id' => $lastMessage->id,
                'last_message_text' => Str::limit($lastMessage->text, 50),
                'last_message_at' => $lastMessage->created_at,
            ]);
        }
    }

    /**
     * Сообщения для группового чата
     */
    private function seedGroupChatMessages(Chat $chat, User $ivan, User $maria, User $alex): void
    {
        $time = Carbon::now()->subMinutes(30);

        $messages = [
            ['sender_id' => $ivan->id, 'text' => 'Всем привет! Добавил всех в рабочий чат.'],
            ['sender_id' => $alex->id, 'text' => 'О, круто, привет! Каков план на сегодня?'],
            ['sender_id' => $ivan->id, 'text' => 'Нужно развернуть MariaDB и MongoDB в Docker и настроить индексы.'],
            ['sender_id' => $maria->id, 'text' => 'Я уже подготовила схему коллекций для Mongo. Ловите PDF.' , 'type' => 'file', 'attachments' => [
                [
                    'id' => 'att_' . Str::random(8),
                    'type' => 'file',
                    'url' => 'https://example.com/docs/mongo_schema.pdf',
                    'file_name' => 'mongo_schema.pdf',
                    'file_size' => 1024500
                ]
            ]],
            ['sender_id' => $alex->id, 'text' => 'Супер, забираю в работу!'],
        ];

        $lastMessage = null;

        foreach ($messages as $msgData) {
            $time->addMinutes(2);

            $lastMessage = Message::create([
                'chat_id' => $chat->id,
                'sender_id' => $msgData['sender_id'],
                'type' => $msgData['type'] ?? 'text',
                'text' => $msgData['text'],
                'attachments' => $msgData['attachments'] ?? [],
                'created_at' => $time->clone(),
                'updated_at' => $time->clone(),
            ]);
        }

        // Обновляем денормализацию в MariaDB
        if ($lastMessage) {
            $chat->update([
                'last_message_id' => $lastMessage->id,
                'last_message_text' => Str::limit($lastMessage->text, 50),
                'last_message_at' => $lastMessage->created_at,
            ]);
        }
    }
}
