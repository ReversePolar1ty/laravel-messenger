<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('chats', function (Blueprint $table) {

            $table->uuid('id')->primary(); // UUID в качестве первичного ключа
            $table->enum('type', ['direct', 'group'])->default('direct');
            $table->string('title')->nullable(); // Для групповых чатов
            $table->string('avatar_url', 2048)->nullable();

            // Внешний ключ на создателя (только для групп)
            $table->foreignId('creator_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Поля денормализации для быстрого рендера списка чатов (компонент связи с MongoDB)
            $table->string('last_message_id', 24)->nullable(); // Хранит ObjectId из MongoDB
            $table->text('last_message_text')->nullable();
            $table->timestamp('last_message_at')->nullable();

            $table->timestamps();

            // Индекс для сортировки списка чатов по времени последнего сообщения
            $table->index('last_message_at');
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
