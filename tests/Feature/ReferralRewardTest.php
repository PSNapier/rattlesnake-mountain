<?php

use App\Models\Item;
use App\Models\Referral;
use App\Models\User;
use App\Services\ReferralRewardService;
use Database\Seeders\ItemSeeder;
use Illuminate\Support\Facades\URL;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function seedReferralCatalog(): void
{
    (new ItemSeeder)->run();

    Item::query()->updateOrCreate(
        ['name' => 'Alder Buckthorn'],
        [
            'max_count' => 999,
            'uses_per_unit' => 1,
            'description' => 'n/a',
            'is_active' => true,
        ]
    );

    Item::query()->updateOrCreate(
        ['name' => 'Bear Clover'],
        [
            'max_count' => 999,
            'uses_per_unit' => 1,
            'description' => 'n/a',
            'is_active' => true,
        ]
    );

    config([
        'welcome-package.random_pools.herbs' => [
            'count' => 2,
            'items' => ['Alder Buckthorn', 'Bear Clover'],
        ],
    ]);
}

function referralQuantity(User $user, string $itemName): int
{
    $item = $user->fresh()->items()->where('items.name', $itemName)->first();

    return $item ? (int) $item->pivot->quantity : 0;
}

it('creates a referral row on registration without granting bonuses yet', function () {
    seedReferralCatalog();

    $referrer = User::factory()->create(['name' => 'Referrer Player']);

    $response = $this->post('/register', [
        'referrer_id' => $referrer->id,
        'name' => 'Recruit Player',
        'email' => 'recruit@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'rules_agreed' => true,
    ]);

    $response->assertRedirect(route('verification.notice', absolute: false));

    $recruit = User::query()->where('email', 'recruit@example.com')->firstOrFail();

    expect($recruit->referred_by_username)->toBe('Referrer Player');
    expect(Referral::query()->where('recruit_id', $recruit->id)->exists())->toBeTrue();
    expect(Referral::query()->where('recruit_id', $recruit->id)->first()->granted_at)->toBeNull();
    expect(referralQuantity($recruit, 'Scorpion'))->toBe(500);
    expect(referralQuantity($referrer, 'Scorpion'))->toBe(0);
    expect(referralQuantity($recruit, 'Stone Voucher'))->toBe(0);
});

it('grants referral bonuses to both parties when recruit verifies email', function () {
    seedReferralCatalog();

    $referrer = User::factory()->create(['name' => 'Referrer Player']);

    $this->post('/register', [
        'referrer_id' => $referrer->id,
        'name' => 'Recruit Player',
        'email' => 'recruit@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'rules_agreed' => true,
    ]);

    $recruit = User::query()->where('email', 'recruit@example.com')->firstOrFail();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $recruit->id, 'hash' => sha1($recruit->email)]
    );

    $this->actingAs($recruit)->get($verificationUrl)->assertRedirect();

    $referral = Referral::query()->where('recruit_id', $recruit->id)->firstOrFail();

    expect($referral->granted_at)->not->toBeNull();
    expect(referralQuantity($recruit, 'Scorpion'))->toBe(600);
    expect(referralQuantity($referrer, 'Scorpion'))->toBe(100);
    expect(referralQuantity($recruit, 'Stone Voucher'))->toBe(2);
    expect(referralQuantity($recruit, 'Herb Voucher'))->toBe(2);
    expect(referralQuantity($recruit, 'Feather Voucher'))->toBe(2);
    expect(referralQuantity($referrer, 'Stone Voucher'))->toBe(2);
    expect(referralQuantity($referrer, 'Herb Voucher'))->toBe(2);
    expect(referralQuantity($referrer, 'Feather Voucher'))->toBe(2);
});

it('is idempotent when verification is processed twice', function () {
    seedReferralCatalog();

    $referrer = User::factory()->create();
    $recruit = User::factory()->unverified()->create();

    Referral::query()->create([
        'recruit_id' => $recruit->id,
        'referrer_id' => $referrer->id,
    ]);

    $service = app(ReferralRewardService::class);

    $first = $service->grantOnVerification($recruit);
    $second = $service->grantOnVerification($recruit->fresh());

    expect($first['granted'])->toBeTrue();
    expect($second['granted'])->toBeFalse();
    expect(referralQuantity($recruit, 'Scorpion'))->toBe(100);
    expect(referralQuantity($referrer, 'Scorpion'))->toBe(100);
});

it('registers without a referrer and does not create a referral row', function () {
    seedReferralCatalog();

    $response = $this->post('/register', [
        'name' => 'Solo Player',
        'email' => 'solo@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'rules_agreed' => true,
    ]);

    $response->assertRedirect(route('verification.notice', absolute: false));

    $user = User::query()->where('email', 'solo@example.com')->firstOrFail();

    expect($user->referred_by_username)->toBeNull();
    expect(Referral::query()->where('recruit_id', $user->id)->exists())->toBeFalse();
    expect(referralQuantity($user, 'Scorpion'))->toBe(500);
});

it('still grants recruit bonuses when referrer is banned before verification', function () {
    seedReferralCatalog();

    $referrer = User::factory()->create();
    $recruit = User::factory()->unverified()->create();

    Referral::query()->create([
        'recruit_id' => $recruit->id,
        'referrer_id' => $referrer->id,
    ]);

    $referrer->forceFill(['banned_at' => now()])->save();

    $result = app(ReferralRewardService::class)->grantOnVerification($recruit);

    expect($result['granted'])->toBeTrue();
    expect($result['referrer_skipped'])->toBeTrue();
    expect(referralQuantity($recruit, 'Scorpion'))->toBe(100);
    expect(referralQuantity($referrer, 'Scorpion'))->toBe(0);
    expect(Referral::query()->where('recruit_id', $recruit->id)->first()->granted_at)->not->toBeNull();
});

it('rejects registration with a banned referrer', function () {
    seedReferralCatalog();

    $referrer = User::factory()->create(['banned_at' => now()]);

    $response = $this->post('/register', [
        'referrer_id' => $referrer->id,
        'name' => 'Recruit Player',
        'email' => 'recruit@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'rules_agreed' => true,
    ]);

    $response->assertSessionHasErrors('referrer_id');
    $this->assertGuest();
});

it('rejects registration with a nonexistent referrer id', function () {
    $response = $this->post('/register', [
        'referrer_id' => 999999,
        'name' => 'Recruit Player',
        'email' => 'recruit@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'rules_agreed' => true,
    ]);

    $response->assertSessionHasErrors('referrer_id');
    $this->assertGuest();
});

it('grants referral rewards when skip_email_verification is enabled at registration', function () {
    seedReferralCatalog();
    config(['app.skip_email_verification' => true]);

    $referrer = User::factory()->create(['name' => 'Referrer Player']);

    $response = $this->post('/register', [
        'referrer_id' => $referrer->id,
        'name' => 'Recruit Player',
        'email' => 'recruit-skip@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'rules_agreed' => true,
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));

    $recruit = User::query()->where('email', 'recruit-skip@example.com')->firstOrFail();

    expect($recruit->hasVerifiedEmail())->toBeTrue();
    expect(Referral::query()->where('recruit_id', $recruit->id)->first()->granted_at)->not->toBeNull();
    expect(referralQuantity($recruit, 'Scorpion'))->toBe(600);
    expect(referralQuantity($referrer, 'Scorpion'))->toBe(100);
});
