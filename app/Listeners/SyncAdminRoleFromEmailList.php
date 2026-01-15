<?php

namespace App\Listeners;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Login;

class SyncAdminRoleFromEmailList
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        $shouldBeAdmin = $this->shouldUserBeAdmin($user->email);
        $targetRole = $shouldBeAdmin ? Role::Admin : Role::User;

        if ($user->role === $targetRole) {
            return;
        }

        $user->forceFill([
            'role' => $targetRole,
        ])->save();
    }

    /**
     * Determine if the provided email address is in the admin list.
     */
    protected function shouldUserBeAdmin(string $email): bool
    {
        $adminEmails = config('auth.admin_emails', []);

        if ($adminEmails === []) {
            return false;
        }

        return in_array(strtolower($email), $adminEmails, true);
    }
}
