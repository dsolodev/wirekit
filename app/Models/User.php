<?php

declare(strict_types = 1);

namespace App\Models;

use App\Observers\UserObserver;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Hidden(['password', 'remember_token'])]
#[ObservedBy(UserObserver::class)]
final class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasRoles;
    use Notifiable;

    /**
     * Determine whether the user may reach the panel at all.
     *
     * This is deliberately only an on/off switch. What a user can actually do once inside is decided by
     * their roles and the Shield generated policies, so a user with no roles can sign in and will simply
     * find an empty panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }
}
