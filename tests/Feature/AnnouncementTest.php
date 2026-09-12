<?php

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the latest published announcement on home', function () {
    $author = User::factory()->create();

    Announcement::create([
        'title' => 'Older News',
        'body' => 'This happened a while ago.',
        'published_at' => now()->subWeek(),
        'author_id' => $author->id,
    ]);

    Announcement::create([
        'title' => 'Newest News',
        'body' => 'The range is open.',
        'published_at' => now()->subHour(),
        'author_id' => $author->id,
    ]);

    $this->get('/')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('cms/Show')
            ->where('isHome', true)
            ->has('announcements', 2)
            ->where('announcements.0.title', 'Newest News')
            ->where('announcements.0.body', 'The range is open.')
            ->where('announcements.1.title', 'Older News'));
});

it('hides unpublished announcements from guests', function () {
    Announcement::create([
        'title' => 'Draft News',
        'body' => 'Not ready yet.',
        'published_at' => null,
    ]);

    Announcement::create([
        'title' => 'Scheduled News',
        'body' => 'Lands next week.',
        'published_at' => now()->addWeek(),
    ]);

    Announcement::create([
        'title' => 'Live News',
        'body' => 'Readable now.',
        'published_at' => now()->subMinute(),
    ]);

    $this->get('/')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('announcements', 1)
            ->where('announcements.0.title', 'Live News'));
});
