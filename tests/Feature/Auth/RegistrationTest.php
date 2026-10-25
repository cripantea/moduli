<?php

use App\Models\User;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'phone' => '333 000 0000',
    ]);

    $this->assertAuthenticated();
    // Subito dopo la registrazione si propone l'attivazione con il tecnico.
    $response->assertRedirect(route('abbonamento', ['benvenuto' => 1], absolute: false));
    $this->get($response->headers->get('Location'))
        ->assertInertia(fn ($page) => $page->component('Offerta')->where('nome', 'Test'));
});

test('registration creates a dedicated tenant with the user as admin', function () {
    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'phone' => '333 000 0000',
    ]);

    $user = User::where('email', 'test@example.com')->first();

    expect($user->tenant)->not->toBeNull()
        ->and($user->tenant->name)->toBe('Test User')
        ->and($user->role)->toBe('admin');
});

test('newly registered users start from onboarding without verifying the email first', function () {
    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'phone' => '333 000 0000',
    ]);

    $this->get('/dashboard')->assertRedirect(route('onboarding', absolute: false));
    $this->get('/benvenuto')->assertOk();
});
