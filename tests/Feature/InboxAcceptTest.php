<?php

use App\Models\Horse;
use App\Models\Message;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('whitelists admin_edits and ignores disallowed keys', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create(['role' => Role::Admin]);
    $otherUser = User::factory()->create();

    $horse = Horse::factory()->for($owner, 'owner')->for($owner, 'bredBy')->create([
        'name' => 'Original Name',
    ]);

    $message = Message::create([
        'horse_id' => $horse->id,
        'user_id' => $owner->id,
        'admin_id' => $admin->id,
        'subject' => 'Review requested',
        'initial_message' => null,
        'admin_edits' => [
            'name' => 'Allowed Update',
            'owner_id' => $otherUser->id,  // disallowed - should be ignored
        ],
        'status' => 'pending',
    ]);

    actingAs($owner)
        ->post(route('inbox.accept', $message))
        ->assertRedirect()
        ->assertSessionHas('success');

    $horse->refresh();
    expect($horse->name)->toBe('Allowed Update')
        ->and($horse->owner_id)->toBe($owner->id);
});
