<?php

use App\Models\CharacterImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

it('requires authentication to upload images', function () {
    $response = $this->postJson('/character-images', [
        'image' => UploadedFile::fake()->image('test.png'),
    ]);

    $response->assertStatus(401);
});

it('validates image file type', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/character-images', [
        'image' => UploadedFile::fake()->create('test.txt', 100),
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['image']);
});

it('validates image file size', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/character-images', [
        'image' => UploadedFile::fake()->image('test.png')->size(3000), // 3MB
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['image']);
});

it('successfully uploads a PNG image', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('character.png', 800, 600);

    $response = $this->actingAs($user)->postJson('/character-images', [
        'image' => $file,
        'alt_text' => 'My character',
        'description' => 'A brave horse warrior',
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => 'Image uploaded successfully!',
        ]);

    $this->assertDatabaseHas('character_images', [
        'user_id' => $user->id,
        'filename' => 'character.png',
        'alt_text' => 'My character',
        'description' => 'A brave horse warrior',
        'mime_type' => 'image/webp',
    ]);

    // Check that files were stored
    Storage::disk('public')->assertExists('character-images/');
});

it('resizes large images to max dimensions', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('large.png', 2000, 1500);

    $response = $this->actingAs($user)->postJson('/character-images', [
        'image' => $file,
    ]);

    $response->assertSuccessful();

    $image = CharacterImage::where('user_id', $user->id)->first();

    // Should be resized to max 1200x1200 while maintaining aspect ratio
    expect($image->width)->toBeLessThanOrEqual(1200);
    expect($image->height)->toBeLessThanOrEqual(1200);
});

it('allows users to delete their own images', function () {
    $user = User::factory()->create();
    $image = CharacterImage::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->deleteJson("/character-images/{$image->id}");

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => 'Image deleted successfully!',
        ]);

    $this->assertSoftDeleted('character_images', ['id' => $image->id]);
});

it('prevents users from deleting others images', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $image = CharacterImage::factory()->create(['user_id' => $user2->id]);

    $response = $this->actingAs($user1)->deleteJson("/character-images/{$image->id}");

    $response->assertStatus(403);
});

it('shows user images on dashboard', function () {
    $user = User::factory()->create();
    $images = CharacterImage::factory()->count(3)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('Dashboard')
        ->has('characterImages', 3)
    );
});

it('rate limits uploads', function () {
    $user = User::factory()->create();

    // Try to upload 21 images (over the 20 per hour limit)
    for ($i = 0; $i < 21; $i++) {
        $file = UploadedFile::fake()->image("image{$i}.png");

        $response = $this->actingAs($user)->postJson('/character-images', [
            'image' => $file,
        ]);

        if ($i < 20) {
            $response->assertSuccessful();
        } else {
            $response->assertStatus(429); // Too Many Requests
            break;
        }
    }
});
