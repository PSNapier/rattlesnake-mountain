<?php

use App\Models\Announcement;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('lets staff create and unpublish announcements', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);

    actingAs($admin)->post(route('admin.announcements.store'), [
        'title' => 'Range Reopens',
        'body' => 'Travel is live again.',
        'published_at' => now()->subMinute()->toDateTimeString(),
    ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $announcement = Announcement::where('title', 'Range Reopens')->firstOrFail();
    expect($announcement->author_id)->toBe($admin->id);
    expect($announcement->published_at)->not->toBeNull();

    $this->get('/')->assertInertia(fn ($page) => $page->has('announcements', 1));

    actingAs($admin)->put(route('admin.announcements.update', $announcement), [
        'title' => 'Range Reopens',
        'body' => 'Travel is live again.',
        'published_at' => null,
    ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($announcement->fresh()->published_at)->toBeNull();

    $this->get('/')->assertInertia(fn ($page) => $page->has('announcements', 0));

    actingAs($admin)->delete(route('admin.announcements.destroy', $announcement))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Announcement::find($announcement->id))->toBeNull();
});

it('stores an offset aware publish time as the correct instant', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);

    // The admin UI sends an absolute ISO instant, not a naive wall clock, so an
    // admin outside UTC does not silently schedule into the future or the past.
    $instant = now()->subMinutes(30);

    actingAs($admin)->post(route('admin.announcements.store'), [
        'title' => 'Offset Aware',
        'body' => 'Published half an hour ago, wherever the admin sits.',
        'published_at' => $instant->copy()->setTimezone('America/Denver')->toIso8601String(),
    ])->assertRedirect();

    $announcement = Announcement::where('title', 'Offset Aware')->firstOrFail();
    expect($announcement->published_at->timestamp)->toBe($instant->timestamp);

    // Already live: it must show on Home immediately, not six hours from now.
    $this->get('/')->assertInertia(fn ($page) => $page->has('announcements', 1));
});

it('forbids non staff from managing announcements', function () {
    $user = User::factory()->create(['role' => Role::User]);
    $announcement = Announcement::create([
        'title' => 'Staff Only',
        'body' => 'Hands off.',
        'published_at' => now()->subMinute(),
    ]);

    actingAs($user)->post(route('admin.announcements.store'), [
        'title' => 'Sneaky',
        'body' => 'Should not persist.',
        'published_at' => null,
    ])->assertForbidden();

    actingAs($user)->put(route('admin.announcements.update', $announcement), [
        'title' => 'Hijacked',
        'body' => 'Should not persist.',
        'published_at' => null,
    ])->assertForbidden();

    actingAs($user)->delete(route('admin.announcements.destroy', $announcement))
        ->assertForbidden();

    expect(Announcement::count())->toBe(1);
    expect($announcement->fresh()->title)->toBe('Staff Only');

    // Guests are stopped by the admin gate before the auth redirect, since the
    // gate accepts a nullable user and denies null.
    $this->post(route('admin.announcements.store'), [
        'title' => 'Guest',
        'body' => 'Should not persist.',
        'published_at' => null,
    ])->assertForbidden();

    expect(Announcement::count())->toBe(1);
});
