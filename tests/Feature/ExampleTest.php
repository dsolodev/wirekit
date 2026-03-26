<?php

declare(strict_types = 1);

test('the application returns a successful response', function(): void {
    $response = $this->get('admin/login');

    $response->assertStatus(200);
});
