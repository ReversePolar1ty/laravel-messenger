<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chat_participants', function (Blueprint $table) {

            $table->id();

            // Связь с таблицей chats (так как там UUID, используем foreignUuid)
            $table->foreignUuid('chat_id')
                ->constrained('chats')
                ->cascadeOnDelete();

            // Связь с таблицей users
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->enum('role', ['member', 'admin'])->default('member');
            $table->timestamp('joined_at')->useCurrent();

            // СТРАТЕГИЧЕСКИЕ ИНДЕКСЫ:

            // 1. Защита от дублей + быстрый поиск «Есть ли юзер X в чате Y?»
            $table->unique(['chat_id', 'user_id']);

            // 2. Индекс для вывода списка всех чатов конкретного пользователя
            // (Laravel автоматически создает индекс для foreignId, но явный поиск по user_id будет частым)
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_participants');
    }
};
