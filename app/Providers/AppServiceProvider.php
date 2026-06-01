<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Admin bypasses all permission checks and other roles use hardcoded abilities.
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('admin')) {
                return true;
            }

            $abilities = $user->roles()->get()->pluck('abilities')->flatten(1)->filter()->unique();
            if ($abilities->contains($ability)) {
                return true;
            }

            return null;
        });

        View::composer('layouts.app', function ($view) {
            /** @var User|null $user */
            $user = Auth::user();
            if (!$user) return;
            $unread = $user->unreadNotifications()
                ->whereRaw(
                    "JSON_UNQUOTE(JSON_EXTRACT(data, '$.cr_id')) IN (SELECT id FROM change_requests WHERE deleted_at IS NULL)"
                )
                ->latest()->take(8)->get();
            $view->with([
                'topbarUnreadNotifications' => $unread->count(),
                'topbarNotifications' => $unread,
            ]);
        });
    }
}
