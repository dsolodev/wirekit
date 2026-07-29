<?php

declare(strict_types = 1);

use App\Models\User;

it('denies guests access to the log viewer', function(): void {
    $this->get(route('log-viewer.index'))->assertForbidden();
});

it('denies inactive users access to the log viewer', function(): void {
    $this->actingAs(User::factory()->inactive()->create())
        ->get(route('log-viewer.index'))
        ->assertForbidden();
});

it('lets an active user open the log viewer', function(): void {
    $this->actingAs(User::factory()->create())
        ->get(route('log-viewer.index'))
        ->assertOk();
});
