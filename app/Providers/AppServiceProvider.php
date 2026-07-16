<?php

namespace App\Providers;

use App\Listeners\RecordLastLogin;
use App\Listeners\SyncAdminRoleFromEmailList;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
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
        Gate::define('access-admin', function (User $user): bool {
            return $user->isStaff();
        });

        foreach (Role::areas() as $area) {
            Gate::define("admin.{$area}", function (User $user) use ($area): bool {
                return $user->hasCapability($area);
            });
        }

        Event::listen(Login::class, RecordLastLogin::class);
        Event::listen(Login::class, SyncAdminRoleFromEmailList::class);
    }
}
