<?php

namespace App\Providers;

use App\Enums\UserRole;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ADMIN pode tudo
        Gate::before(function ($user, $ability) {
            if ($user->role === UserRole::ADMIN) {
                return true;
            }
        });

        Gate::define('manageGateways', function ($user) {
            return $user->role === UserRole::ADMIN;
        });

        Gate::define('manageUsers', function ($user) {
            return in_array($user->role, [UserRole::ADMIN, UserRole::MANAGER], true);
        });

        Gate::define('manageProducts', function ($user) {
            return in_array($user->role, [UserRole::ADMIN, UserRole::MANAGER, UserRole::FINANCE], true);
        });

        Gate::define('performRefund', function ($user) {
            return in_array($user->role, [UserRole::ADMIN, UserRole::FINANCE], true);
        });
    }
}
