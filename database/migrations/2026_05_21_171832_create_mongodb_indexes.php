<?php

use Illuminate\Database\Migrations\Migration;
use MongoDB\Laravel\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Указываем, что работаем с соединением MongoDB
        $connection = Schema::connection('mongodb');

        // Коллекция должна совпадать с Message::$collection (messenger_messages).
        $connection->create('messenger_messages', function (Blueprint $collection) {
            // Пагинация истории: ChatShowController сортирует по _id desc.
            $collection->index(['chat_id' => 1, '_id' => -1]);

            // Загрузка по времени создания (списки / fallback-сортировка).
            $collection->index(['chat_id' => 1, 'created_at' => -1]);

            // Поиск медиафайлов/вложений в чате.
            $collection->index(['chat_id' => 1, 'type' => 1, 'created_at' => -1]);
        });

        // Статусы прочтения: один юзер — один документ на чат.
        $connection->create('chat_reads', function (Blueprint $collection) {
            $collection->unique(['chat_id' => 1, 'user_id' => 1]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = Schema::connection('mongodb');
        $connection->dropIfExists('messenger_messages');
        $connection->dropIfExists('chat_reads');
    }
};
