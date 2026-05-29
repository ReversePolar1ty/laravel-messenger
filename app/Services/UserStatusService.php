<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

class UserStatusService
{
    private const ONLINE_TTL = 60; // Секунд, после которых пользователь считается офлайн
    private const BLOCK_TTL = 70; // Время блокировки пингов после логаута

    /**
     * Отметить, что пользователь онлайн (вызывается при пинге из вебсокета)
     */
    public function ping(int $userId): void
    {
        // ПРОВЕРКА: Если пользователь только что разлогинился, игнорируем пинг
        if (Redis::exists("user:{$userId}:logout_block")) {
            return;
        }

        $now = Carbon::now()->toIso8601String();

        // 1. Ключ-флаг, который автоматически удалится через 60 секунд
        Redis::setex("user:{$userId}:online", self::ONLINE_TTL, '1');

        // 2. Ключ со временем последнего визита (хранится постоянно)
        Redis::set("user:{$userId}:last_seen", $now);
    }

    public function clearLogoutBlock(int $userId): void
    {
        Redis::del("user:{$userId}:logout_block");
    }

    /**
     * Явный офлайн (вызывается при событии дисконнекта сокета)
     */
    public function setOffline(int $userId): void
    {
        // 1. Ставим временную заглушку, которая запретит пингам поднимать статус
        Redis::setex("user:{$userId}:logout_block", self::BLOCK_TTL, '1');

        // 2. Удаляем статус онлайн
        Redis::del("user:{$userId}:online");
        Redis::set("user:{$userId}:last_seen", Carbon::now()->toIso8601String());

        // Асинхронная задача (Job) для сохранения времени last_seen в MongoDB или MariaDB
    }

    /**
     * Получить статус пользователя
     */
    public function getStatus(int $userId): array
    {
        if (Redis::exists("user:{$userId}:logout_block")) {
            return [
                'status' => 'offline',
                'last_seen' => 'только что',
            ];
        }

        $isOnline = (bool)Redis::exists("user:{$userId}:online");

        if ($isOnline) {
            return ['status' => 'online', 'last_seen' => null];
        }

        $lastSeen = Redis::get("user:{$userId}:last_seen");

        return [
            'status' => 'offline',
            'last_seen' => $lastSeen ? Carbon::parse($lastSeen)->diffForHumans() : 'давно',
        ];
    }
}
