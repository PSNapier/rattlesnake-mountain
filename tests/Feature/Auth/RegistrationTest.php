<?php

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'rules_agreed' => true,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('verification.notice', absolute: false));
});

test('registration fails when rules are not agreed to', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'rules_agreed' => false,
    ]);

    $response->assertSessionHasErrors('rules_agreed');
    $this->assertGuest();
});

test('registration fails when rules_agreed is missing', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('rules_agreed');
    $this->assertGuest();
});

test('new users can register with referred_by_username', function () {
    $referrer = User::factory()->create(['name' => 'Referrer User']);

    $response = $this->post('/register', [
        'referred_by_username' => 'Referrer User',
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'rules_agreed' => true,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('verification.notice', absolute: false));

    $user = User::where('email', 'test@example.com')->first();
    expect($user->referred_by_username)->toBe('Referrer User');
});

test('registration fails when referred_by_username does not exist', function () {
    $response = $this->post('/register', [
        'referred_by_username' => 'NonExistentUser',
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'rules_agreed' => true,
    ]);

    $response->assertSessionHasErrors('referred_by_username');
    $this->assertGuest();
});

test('registration works without referred_by_username', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'rules_agreed' => true,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('verification.notice', absolute: false));

    $user = User::where('email', 'test@example.com')->first();
    expect($user->referred_by_username)->toBeNull();
});
