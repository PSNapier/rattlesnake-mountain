<?php

use App\Enums\MessageType;
use App\Models\BreedingRequest;
use App\Models\Horse;
use App\Models\Message;
use App\Models\Role;
use App\Models\User;
use App\Services\BreedingSlotService;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('renders breeding result messages in the inbox without horse data', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['role' => Role::Admin]);
    $sire = Horse::factory()->for($user, 'owner')->for($user, 'bredBy')->breedableStallion()->create();
    $dam = Horse::factory()->for($user, 'owner')->for($user, 'bredBy')->breedableMare()->create();
    app(BreedingSlotService::class)->ensureSlotsForHorse($sire);
    app(BreedingSlotService::class)->ensureSlotsForHorse($dam);

    actingAs($user)->post(route('breedings.store'), [
        'sire_id' => $sire->id,
        'dam_id' => $dam->id,
        'evidence_url' => 'https://example.com/art',
    ]);

    $request = BreedingRequest::query()->firstOrFail();
    actingAs($admin)->post(route('admin.breeding-requests.roll', $request))->assertRedirect();

    $message = Message::query()->where('user_id', $user->id)->firstOrFail();

    actingAs($user)->get(route('inbox.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Inbox/Index')
            ->has('messages', 1)
            ->where('messages.0.type', MessageType::BreedingResult->value)
            ->where('messages.0.horse', null)
            ->where('messages.0.breeding_url', route('breedings.index'))
        );

    actingAs($user)->get(route('inbox.show', $message))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Inbox/Show')
            ->where('message.type', MessageType::BreedingResult->value)
            ->where('message.horse', null)
            ->where('message.breeding_url', route('breedings.index'))
        );
});

it('rejects accept and decline for breeding result messages', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['role' => Role::Admin]);

    $message = Message::query()->create([
        'type' => MessageType::BreedingResult,
        'horse_id' => null,
        'breeding_request_id' => null,
        'user_id' => $user->id,
        'admin_id' => $admin->id,
        'subject' => 'Your breeding results are ready',
        'initial_message' => 'Results ready.',
        'status' => 'pending',
    ]);

    actingAs($user)->post(route('inbox.accept', $message))->assertStatus(400);
    actingAs($user)->post(route('inbox.decline', $message))->assertStatus(400);
});

it('counts unread breeding result messages toward the badge', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['role' => Role::Admin]);

    Message::query()->create([
        'type' => MessageType::BreedingResult,
        'horse_id' => null,
        'user_id' => $user->id,
        'admin_id' => $admin->id,
        'subject' => 'Your breeding results are ready',
        'initial_message' => 'Results ready.',
        'is_read' => false,
        'status' => 'pending',
    ]);

    actingAs($user)->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->where('unreadMessageCount', 1));
});
