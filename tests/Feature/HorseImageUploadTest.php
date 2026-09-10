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
 * Reads the design image the controller stored and reports its real format and
 * size, so the assertions below describe the processed file rather than the upload.
 */
function storedHorseImage(string $url): array
{
    $filename = basename(parse_url($url, PHP_URL_PATH));
    $path = 'horse-images/'.$filename;

    expect(Storage::disk('public')->exists($path))->toBeTrue();

    $contents = Storage::disk('public')->get($path);
    [$width, $height, $type] = getimagesizefromstring($contents);

    return ['width' => $width, 'height' => $height, 'type' => $type, 'path' => $path];
}

it('stores an uploaded design as webp', function () {
    $user = User::factory()->create(['role' => Role::User]);

    $response = actingAs($user)
        ->postJson(route('horses.upload-image'), [
            'image' => UploadedFile::fake()->image('design.png', 500, 400),
        ])
        ->assertOk()
        ->assertJson(['success' => true]);

    $stored = storedHorseImage($response->json('url'));

    expect($stored['type'])->toBe(IMAGETYPE_WEBP)
        ->and($stored['width'])->toBe(500)
        ->and($stored['height'])->toBe(400);
});

it('scales an oversized design down to 1200px on its longest side', function () {
    $user = User::factory()->create(['role' => Role::User]);

    $response = actingAs($user)
        ->postJson(route('horses.upload-image'), [
            'image' => UploadedFile::fake()->image('big.png', 2400, 1200),
        ])
        ->assertOk();

    $stored = storedHorseImage($response->json('url'));

    expect($stored['width'])->toBe(1200)
        ->and($stored['height'])->toBe(600);
});

it('leaves a design at or under the cap untouched', function () {
    $user = User::factory()->create(['role' => Role::Admin]);

    $response = actingAs($user)
        ->postJson(route('horses.upload-image'), [
            'image' => UploadedFile::fake()->image('exact.png', 1200, 800),
        ])
        ->assertOk();

    $stored = storedHorseImage($response->json('url'));

    expect($stored['width'])->toBe(1200)
        ->and($stored['height'])->toBe(800);
});
