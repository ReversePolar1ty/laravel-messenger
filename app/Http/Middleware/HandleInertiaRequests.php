<?php

namespace App\Http\Middleware;

use App\Services\UserThemeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $themes = app(UserThemeService::class);

        $theme = $request->user()
            ? $themes->get($request->user()->id)
            : ($request->cookie('theme') === UserThemeService::LIGHT
                ? UserThemeService::LIGHT
                : UserThemeService::DARK);

        // Blade получает тему сразу, чтобы не мигал фон до гидрации Inertia.
        View::share('appTheme', $theme);

        if ($request->user() && $request->cookie('theme') !== $theme) {
            cookie()->queue(cookie('theme', $theme, 60 * 24 * 365));
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'theme' => $theme,
        ];
    }
}
