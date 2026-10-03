<?php

use App\Models\User;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'company' => 'Test Company',
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('registration creates a dedicated tenant with the user as admin', function () {
    $this->post('/register', [
        'company' => 'Acme Srl',
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::where('email', 'test@example.com')->first();

    expect($user->tenant)->not->toBeNull()
        ->and($user->tenant->name)->toBe('Acme Srl')
        ->and($user->role)->toBe('admin');
});

test('newly registered users must verify their email before using the app', function () {
    $this->post('/register', [
        'company' => 'Acme Srl',
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->get('/templates')->assertRedirect(route('verification.notice', absolute: false));
});
