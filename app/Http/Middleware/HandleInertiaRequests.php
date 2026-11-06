<?php

namespace App\Http\Middleware;

use App\Domain\Compliance\LaunchGate\LaunchGate;
use App\Domain\ResponsiblePlay\Models\ResponsiblePlaySetting;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                // The signed-in user's own permissions, for showing or hiding
                // navigation only. The server still checks every action.
                'permissions' => fn (): array => $request->user() instanceof User
                    ? $request->user()->getAllPermissions()->pluck('name')->values()->all()
                    : [],
            ],
            'luxbet' => function (): array {
                $gate = app(LaunchGate::class)->status();

                $user = request()->user();

                return [
                    'reminderMinutes' => $user instanceof User
                        ? ResponsiblePlaySetting::forUser($user)->demo_session_reminder_minutes
                        : null,
                    'environmentMode' => $gate->environmentMode->value,
                    'realMoneyEnabled' => $gate->realMoneyEnabled,
                    'demoNotice' => [
                        'title' => config('luxbet.demo_notice.title'),
                        'body' => config('luxbet.demo_notice.body'),
                    ],
                ];
            },
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
