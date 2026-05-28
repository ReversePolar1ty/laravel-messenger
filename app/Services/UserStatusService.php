<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

class UserStatusService
{
    private const ONLINE_TTL = 60; // Секунд, после которых пользователь считается офлайн

    /**
     * Отметить, что пользователь онлайн (вызывается при пинге из вебсокета)
     */
    public function ping(int $userId): void
    {
        $now = Carbon::now()->toIso8601String();

        // 1. Ключ-флаг, который автоматически удалится через 60 секунд
        Redis::setex("user:{$userId}:online", self::ONLINE_TTL, '1');

        // 2. Ключ со временем последнего визита (хранится постоянно)
        Redis::set("user:{$userId}:last_seen", $now);
    }

    /**
     * Явный офлайн (вызывается при событии дисконнекта сокета)
     */
    public function setOffline(int $userId): void
    {
        Redis::del("user:{$userId}:online");
        Redis::set("user:{$userId}:last_seen", Carbon::now()->toIso8601String());

        // Здесь же можно сделать асинхронную задачу (Job)
        // для сохранения времени last_seen в MongoDB или MariaDB
    }

    /**
     * Получить статус пользователя
     */
    public function getStatus(int $userId): array
    {
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
