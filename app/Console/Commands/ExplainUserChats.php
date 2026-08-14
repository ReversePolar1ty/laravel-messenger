<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExplainUserChats extends Command
{
    protected $signature = 'debug:explain-user-chats {userId? : ID пользователя; по умолчанию берётся первый пользователь}';

    protected $description = 'Показывает EXPLAIN и ANALYZE для запроса списка чатов пользователя';

    public function handle(): int
    {
        $connection = DB::connection('mariadb');
        $userId = $this->argument('userId') ?? $connection->table('users')->value('id');

        if ($userId === null) {
            $this->error('В таблице users нет пользователей. Создайте пользователя или передайте его ID.');

            return self::FAILURE;
        }

        $query = <<<'SQL'
SELECT *
FROM chats
WHERE id IN (
    SELECT chat_id
    FROM chat_participants
    WHERE user_id = ?
)
ORDER BY last_message_at DESC, created_at DESC
SQL;

        $this->info("План запроса списка чатов для user_id={$userId}");
        $this->newLine();
        $this->line($query);
        $this->newLine();

        $this->info('EXPLAIN FORMAT=JSON — предполагаемый план:');
        $this->line($this->jsonPlan($connection->select("EXPLAIN FORMAT=JSON {$query}", [$userId])));
        $this->newLine();

        // В MariaDB команда ANALYZE FORMAT=JSON является аналогом EXPLAIN ANALYZE в MySQL.
        // Она выполняет SELECT и добавляет к плану фактические метрики выполнения.
        $this->info('ANALYZE FORMAT=JSON — фактическое выполнение:');
        $this->line($this->jsonPlan($connection->select("ANALYZE FORMAT=JSON {$query}", [$userId])));

        return self::SUCCESS;
    }

    /**
     * MariaDB возвращает JSON-план одной строкой в поле EXPLAIN.
     * Преобразуем его в удобный для чтения вид в консоли.
     *
     * @param  array<int, object>  $rows
     */
    private function jsonPlan(array $rows): string
    {
        $plan = $rows[0]->EXPLAIN ?? $rows[0]->ANALYZE ?? null;

        if (! is_string($plan)) {
            return json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }

        return json_encode(
            json_decode($plan, true, 512, JSON_THROW_ON_ERROR),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );
    }
}
