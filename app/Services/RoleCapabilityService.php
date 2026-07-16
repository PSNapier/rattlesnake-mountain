<?php

namespace App\Services;

use App\Models\Role;
use App\Models\RoleCapability;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RoleCapabilityService
{
    public const CACHE_KEY = 'role_capabilities.matrix';

    /**
     * @return list<string>
     */
    public function capabilitiesFor(Role $role): array
    {
        $matrix = $this->matrix();

        return $matrix[$role->value] ?? $role->defaultCapabilities();
    }

    /**
     * Full role → capabilities map for UI and lookups.
     *
     * @return array<string, list<string>>
     */
    public function matrix(): array
    {
        if (app()->runningUnitTests()) {
            return $this->loadMatrixFromDatabase();
        }

        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            return $this->loadMatrixFromDatabase();
        });
    }

    /**
     * Replace staff-role capability rows. User is always forced empty.
     * Admin always retains the `users` capability.
     *
     * @param  array<string, list<string>>  $matrix
     */
    public function sync(array $matrix): void
    {
        $areas = Role::areas();
        $now = now();

        DB::transaction(function () use ($matrix, $areas, $now): void {
            $rows = [];

            foreach (Role::cases() as $role) {
                if ($role === Role::User) {
                    continue;
                }

                $capabilities = $matrix[$role->value] ?? [];
                $capabilities = array_values(array_unique(array_intersect($capabilities, $areas)));

                if ($role === Role::Admin && ! in_array('users', $capabilities, true)) {
                    $capabilities[] = 'users';
                }

                sort($capabilities);

                foreach ($capabilities as $capability) {
                    $rows[] = [
                        'role' => $role->value,
                        'capability' => $capability,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            RoleCapability::query()
                ->where('role', '!=', Role::User->value)
                ->delete();

            RoleCapability::query()->where('role', Role::User->value)->delete();

            if ($rows !== []) {
                RoleCapability::query()->insert($rows);
            }
        });

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, list<string>>
     */
    private function loadMatrixFromDatabase(): array
    {
        $defaults = [];
        foreach (Role::cases() as $role) {
            $defaults[$role->value] = $role->defaultCapabilities();
        }

        if (! Schema::hasTable('role_capabilities')) {
            return $defaults;
        }

        $grouped = RoleCapability::query()
            ->get(['role', 'capability'])
            ->groupBy('role')
            ->map(fn ($rows) => $rows->pluck('capability')->unique()->values()->all())
            ->all();

        $matrix = [];
        foreach (Role::cases() as $role) {
            if ($role === Role::User) {
                $matrix[$role->value] = [];

                continue;
            }

            if (array_key_exists($role->value, $grouped)) {
                $capabilities = array_values(array_intersect($grouped[$role->value], Role::areas()));
                sort($capabilities);
                $matrix[$role->value] = $capabilities;
            } else {
                $matrix[$role->value] = $role->defaultCapabilities();
            }
        }

        return $matrix;
    }
}
