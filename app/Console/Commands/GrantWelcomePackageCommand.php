<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\WelcomePackageService;
use Illuminate\Console\Command;
use Throwable;

class GrantWelcomePackageCommand extends Command
{
    protected $signature = 'welcome-package:grant {user : The user ID or email}';

    protected $description = 'Grant (or repair) the welcome package for a user';

    public function handle(WelcomePackageService $welcomePackageService): int
    {
        $identifier = (string) $this->argument('user');

        $user = is_numeric($identifier)
            ? User::query()->find($identifier)
            : User::query()->where('email', $identifier)->first();

        if (! $user) {
            $this->error("User not found: {$identifier}");

            return self::FAILURE;
        }

        try {
            $result = $welcomePackageService->grant($user);
        } catch (Throwable $exception) {
            $this->error("Grant failed: {$exception->getMessage()}");

            return self::FAILURE;
        }

        if (! $result['granted']) {
            $this->info("Welcome package already granted for user #{$user->id} ({$user->email}).");

            return self::SUCCESS;
        }

        $this->info("Welcome package granted for user #{$user->id} ({$user->email}).");
        foreach ($result['items'] as $name => $quantity) {
            $this->line("  - {$name}: {$quantity}");
        }

        return self::SUCCESS;
    }
}
