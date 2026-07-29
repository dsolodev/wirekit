<?php

declare(strict_types = 1);

namespace App\Observers;

use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;

final class UserObserver
{
    /**
     * Promote the very first user of the application to super admin.
     *
     * Somebody has to be able to hand out roles before any roles exist, so the first user created —
     * whether by the seeder, by `make:filament-user`, or by hand — becomes the super admin. Every user
     * created afterwards starts with no roles and must be granted them from the panel.
     */
    public function created(User $user): void
    {
        if (! Utils::isSuperAdminEnabled()) {
            return;
        }

        if (User::query()->count() > 1) {
            return;
        }

        $user->assignRole(Utils::createRole());
    }
}
