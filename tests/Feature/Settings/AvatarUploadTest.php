<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

/**
 * Reads the avatar the controller stored and reports its real format and size,
 * so the assertions below describe the processed file rather than the upload.
 */
function storedAvatar(string $avatarUrl): array
{
    $filename = basename(parse_url($avatarUrl, PHP_URL_PATH));
    $path = 'avatars/'.$filename;

    expect(Storage::disk('public')->exists($path))->toBeTrue();

    $contents = Storage::disk('public')->get($path);
    [$width, $height, $type] = getimagesizefromstring($contents);

    return ['width' => $width, 'height' => $height, 'type' => $type, 'path' => $path];
}

it('stores an uploaded avatar as a square webp', function () {
    $user = User::factory()->create(['role' => Role::User]);

    $response = actingAs($user)
        ->postJson(route('appearance.avatar.upload'), [
            'avatar' => UploadedFile::fake()->image('me.png', 800, 600),
        ])
        ->assertOk()
        ->assertJson(['success' => true]);

    $stored = storedAvatar($response->json('avatar'));

    expect($stored['type'])->toBe(IMAGETYPE_WEBP)
        ->and($stored['width'])->toBe(400)
        ->and($stored['height'])->toBe(400);
});

it('does not upscale an avatar smaller than the 400px cover size', function () {
    $user = User::factory()->create(['role' => Role::User]);

    $response = actingAs($user)
        ->postJson(route('appearance.avatar.upload'), [
            'avatar' => UploadedFile::fake()->image('tiny.png', 120, 90),
        ])
        ->assertOk();

    $stored = storedAvatar($response->json('avatar'));

    expect($stored['width'])->toBe(90)
        ->and($stored['height'])->toBe(90);
});

it('records the avatar url on the user and replaces the previous file', function () {
    $user = User::factory()->create(['role' => Role::User]);

    $first = actingAs($user)
        ->postJson(route('appearance.avatar.upload'), [
            'avatar' => UploadedFile::fake()->image('first.png', 300, 300),
        ])
        ->assertOk();

    $firstPath = storedAvatar($first->json('avatar'))['path'];

    expect($user->fresh()->avatar)->toBe($first->json('avatar'));

    $second = actingAs($user)
        ->postJson(route('appearance.avatar.upload'), [
            'avatar' => UploadedFile::fake()->image('second.png', 300, 300),
        ])
        ->assertOk();

    expect(Storage::disk('public')->exists($firstPath))->toBeFalse()
        ->and($user->fresh()->avatar)->toBe($second->json('avatar'));
});
