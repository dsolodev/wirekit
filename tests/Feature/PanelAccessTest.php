<?php

declare(strict_types = 1);

use App\Models\User;
use Filament\Facades\Filament;

it('redirects the root path to the panel', function(): void {
    $this->get('/')->assertRedirect(Filament::getDefaultPanel()->getUrl());
});

it('shows the login page', function(): void {
    $this->get(Filament::getDefaultPanel()->getLoginUrl())->assertOk();
});

it('lets an active user reach the panel', function(): void {
    $user = User::factory()->create();

    expect($user->canAccessPanel(Filament::getDefaultPanel()))->toBeTrue();

    $this->actingAs($user)
        ->get(Filament::getDefaultPanel()->getUrl())
        ->assertOk();
});

it('keeps an inactive user out of the panel', function(): void {
    $user = User::factory()->inactive()->create();

    expect($user->canAccessPanel(Filament::getDefaultPanel()))->toBeFalse();

    $this->actingAs($user)
        ->get(Filament::getDefaultPanel()->getUrl())
        ->assertForbidden();
});
