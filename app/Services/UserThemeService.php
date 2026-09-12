<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class UserThemeService
{
    public const DARK = 'dark';

    public const LIGHT = 'light';

    private const ALLOWED = [self::DARK, self::LIGHT];

    /**
     * Возвращает сохранённую тему пользователя или тёмную по умолчанию.
     */
    public function get(int $userId): string
    {
        $theme = Redis::get($this->key($userId));

        return in_array($theme, self::ALLOWED, true) ? $theme : self::DARK;
    }

    /**
     * Сохраняет тему пользователя в Redis без TTL (постоянно).
     */
    public function set(int $userId, string $theme): string
    {
        $normalized = in_array($theme, self::ALLOWED, true) ? $theme : self::DARK;

        Redis::set($this->key($userId), $normalized);

        return $normalized;
    }

    private function key(int $userId): string
    {
        return "user:{$userId}:theme";
    }
}
