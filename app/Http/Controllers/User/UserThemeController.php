<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\UserThemeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserThemeController extends Controller
{
    public function __construct(private readonly UserThemeService $themes) {}

    /**
     * Сохраняет выбранную тему текущего пользователя в Redis.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'theme' => ['required', 'string', Rule::in([UserThemeService::DARK, UserThemeService::LIGHT])],
        ]);

        $theme = $this->themes->set($request->user()->id, $validated['theme']);

        cookie()->queue(cookie('theme', $theme, 60 * 24 * 365));

        return response()->json([
            'theme' => $theme,
        ]);
    }
}
