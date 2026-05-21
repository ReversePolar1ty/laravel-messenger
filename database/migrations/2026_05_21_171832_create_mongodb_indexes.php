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

        // 1. Коллекция сообщений
        $connection->create('messages', function (Blueprint $collection) {
            // Индекс для загрузки истории чата (пагинация)
            $collection->index(['chat_id' => 1, 'created_at' => -1]);

            // Индекс для поиска медиафайлов/вложений в чате
            $collection->index(['chat_id' => 1, 'type' => 1, 'created_at' => -1]);
        });

        // 2. Коллекция статусов прочтения
        $connection->create('chat_reads', function (Blueprint $collection) {
            // Уникальный индекс: один юзер - один статус прочтения в конкретном чате
            $collection->unique(['chat_id' => 1, 'user_id' => 1]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = Schema::connection('mongodb');
        $connection->dropIfExists('messages');
        $connection->dropIfExists('chat_reads');
    }
};
