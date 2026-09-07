<?php

use App\Models\Role;
use App\Models\User;
use App\Support\UploadLimit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

/**
 * A valid PNG whose reported size is exactly $kilobytes, so validation sees an
 * oversized file while the image pipeline still gets something it can read.
 */
function sizedImage(int $kilobytes): UploadedFile
{
    return UploadedFile::fake()->image('design.png', 32, 32)->size($kilobytes);
}

function uploadHorseImage(User $user, UploadedFile $file)
{
    return actingAs($user)->postJson(route('horses.upload-image'), ['image' => $file]);
}

function uploadAvatar(User $user, UploadedFile $file)
{
    return actingAs($user)->postJson(route('appearance.avatar.upload'), ['avatar' => $file]);
}

it('accepts a staff upload over the player limit', function () {
    $staff = User::factory()->create(['role' => Role::Admin]);

    // 5MB: over the 2MB player limit, under the 10MB staff limit.
    uploadHorseImage($staff, sizedImage(5120))->assertOk();
    uploadAvatar($staff, sizedImage(5120))->assertOk();
});

it('rejects a player upload over the player limit', function () {
    $player = User::factory()->create(['role' => Role::User]);

    uploadHorseImage($player, sizedImage(3072))
        ->assertStatus(422)
        ->assertJsonValidationErrors('image');

    uploadAvatar($player, sizedImage(3072))
        ->assertStatus(422)
        ->assertJsonValidationErrors('avatar');
});

it('names the applicable limit in the error message', function () {
    $player = User::factory()->create(['role' => Role::User]);

    expect(uploadHorseImage($player, sizedImage(3072))->json('errors.image.0'))
        ->toBe('The image must be smaller than 2MB.');

    expect(uploadAvatar($player, sizedImage(3072))->json('errors.avatar.0'))
        ->toBe('The image must be smaller than 2MB.');

    $staff = User::factory()->create(['role' => Role::Admin]);

    expect(uploadHorseImage($staff, sizedImage(11264))->json('errors.image.0'))
        ->toBe('The image must be smaller than 10MB.');

    expect(uploadAvatar($staff, sizedImage(11264))->json('errors.avatar.0'))
        ->toBe('The image must be smaller than 10MB.');
});

it('rejects any upload over the staff limit', function () {
    $staff = User::factory()->create(['role' => Role::Admin]);
    $player = User::factory()->create(['role' => Role::User]);

    // 11MB: over both limits.
    uploadHorseImage($staff, sizedImage(11264))->assertStatus(422);
    uploadAvatar($staff, sizedImage(11264))->assertStatus(422);
    uploadHorseImage($player, sizedImage(11264))->assertStatus(422);
    uploadAvatar($player, sizedImage(11264))->assertStatus(422);
});

it('enforces the limit from config', function () {
    $player = User::factory()->create(['role' => Role::User]);

    // Rejected under the shipped 2MB player limit...
    uploadHorseImage($player, sizedImage(3072))->assertStatus(422);

    // ...and accepted once config alone is raised, with no other edit.
    config(['uploads.max_kilobytes.player' => 5120]);

    uploadHorseImage($player, sizedImage(3072))->assertOk();
    uploadAvatar($player, sizedImage(3072))->assertOk();

    // Lowering it takes effect the same way.
    config(['uploads.max_kilobytes.player' => 1024]);

    expect(uploadHorseImage($player, sizedImage(2048))->json('errors.image.0'))
        ->toBe('The image must be smaller than 1MB.');
});

it('shares the resolved limit with inertia', function () {
    $player = User::factory()->create(['role' => Role::User]);

    actingAs($player)->get(route('appearance'))
        ->assertInertia(fn ($page) => $page
            ->where('uploads.maxKilobytes', 2048)
            ->where('uploads.maxBytes', 2 * 1024 * 1024)
            ->where('uploads.maxMegabytes', 2)
        );

    $staff = User::factory()->create(['role' => Role::Admin]);

    actingAs($staff)->get(route('appearance'))
        ->assertInertia(fn ($page) => $page
            ->where('uploads.maxKilobytes', 10240)
            ->where('uploads.maxBytes', 10 * 1024 * 1024)
            ->where('uploads.maxMegabytes', 10)
        );

    expect(UploadLimit::kilobytesFor(null))->toBe(2048);
});
