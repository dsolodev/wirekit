<?php

declare(strict_types = 1);

use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Facades\Filament;

it('promotes the first user to super admin', function(): void {
    $first = User::factory()->create();

    expect($first->fresh()?->hasRole(Utils::getSuperAdminName()))->toBeTrue();
});

it('leaves every user created after the first without roles', function(): void {
    User::factory()->create();

    $second = User::factory()->create();

    expect($second->fresh()?->hasRole(Utils::getSuperAdminName()))->toBeFalse()
        ->and($second->roles)->toBeEmpty();
});

it('promotes the first user however they are created', function(): void {
    $user = User::query()->create([
        'name'     => 'Hand rolled',
        'email'    => 'hand@example.test',
        'password' => 'password',
    ]);

    expect($user->fresh()?->hasRole(Utils::getSuperAdminName()))->toBeTrue();
});

it('grants the super admin its Shield permissions', function(): void {
    $user = User::factory()->create();

    Utils::giveSuperAdminPermission(Utils::createPermission('ViewAny:Role'));

    expect($user->fresh()?->can('ViewAny:Role'))->toBeTrue();
});

it('denies a user who holds no roles', function(): void {
    User::factory()->create();

    $user = User::factory()->create();

    Utils::giveSuperAdminPermission(Utils::createPermission('ViewAny:Role'));

    expect($user->fresh()?->can('ViewAny:Role'))->toBeFalse();
});

it('still lets a user without roles reach the panel', function(): void {
    User::factory()->create();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(Filament::getDefaultPanel()->getUrl())
        ->assertOk();
});
