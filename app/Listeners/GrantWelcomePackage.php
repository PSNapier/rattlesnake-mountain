<?php

namespace App\Listeners;

use App\Services\WelcomePackageService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Log;
use Throwable;

class GrantWelcomePackage
{
    public function __construct(private WelcomePackageService $welcomePackageService) {}

    public function handle(Registered $event): void
    {
        try {
            $this->welcomePackageService->grant($event->user);
        } catch (Throwable $exception) {
            Log::error('Welcome package grant failed during registration', [
                'user_id' => $event->user->id,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
