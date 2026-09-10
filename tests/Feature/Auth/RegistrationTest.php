<?php

use App\Models\Referral;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

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

test('new users can register with referrer_id', function () {
    $referrer = User::factory()->create(['name' => 'Referrer User']);

    $response = $this->post('/register', [
        'referrer_id' => $referrer->id,
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
    expect(Referral::query()->where('recruit_id', $user->id)->where('referrer_id', $referrer->id)->exists())->toBeTrue();
});

test('registration fails when referrer_id does not exist', function () {
    $response = $this->post('/register', [
        'referrer_id' => 999999,
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'rules_agreed' => true,
    ]);

    $response->assertSessionHasErrors('referrer_id');
    $this->assertGuest();
});

test('registration works without referrer_id', function () {
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
    expect(Referral::query()->where('recruit_id', $user->id)->exists())->toBeFalse();
});
