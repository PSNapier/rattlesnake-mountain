<?php

namespace App\Providers;

use App\Listeners\RecordLastLogin;
use App\Listeners\SyncAdminRoleFromEmailList;
use App\Models\Role;
use App\Models\User;
use App\Services\Contracts\BreedingGeneticsProvider;
use App\Services\Genetics\FakeExternalGeneticsProvider;
use App\Services\Genetics\LocalPunnettGeneticsProvider;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(BreedingGeneticsProvider::class, function ($app): BreedingGeneticsProvider {
            $driver = config('breeding.genetics_provider', 'local');

            return match ($driver) {
                'local' => $app->make(LocalPunnettGeneticsProvider::class),
                'fake_external' => $app->make(FakeExternalGeneticsProvider::class),
                default => throw new InvalidArgumentException("Unsupported breeding genetics provider [{$driver}]."),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('access-admin', function (?User $user): bool {
            return $user !== null && $user->isStaff();
        });

        foreach (Role::areas() as $area) {
            Gate::define("admin.{$area}", function (?User $user) use ($area): bool {
                return $user !== null && $user->hasCapability($area);
            });
        }

        Event::listen(Login::class, RecordLastLogin::class);
        Event::listen(Login::class, SyncAdminRoleFromEmailList::class);
    }
}
