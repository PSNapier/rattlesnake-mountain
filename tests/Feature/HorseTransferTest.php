<?php

use App\Enums\AdminAction;
use App\Enums\BreedingRequestStatus;
use App\Enums\HorseState;
use App\Enums\HorseTransferStatus;
use App\Models\AdminSubmissionLog;
use App\Models\BreedingRequest;
use App\Models\Herd;
use App\Models\Horse;
use App\Models\HorseTransfer;
use App\Models\Item;
use App\Models\Message;
use App\Models\Role;
use App\Models\User;
use App\Services\HorseTransferService;
use App\Services\RoleCapabilityService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::forget(RoleCapabilityService::CACHE_KEY);
});

function transferableHorse(User $owner): Horse
{
    return Horse::factory()->published()->create([
        'owner_id' => $owner->id,
        'bred_by' => $owner->id,
        'approved_at' => now(),
    ]);
}

function pendingTransfer(Horse $horse, User $from, User $to): HorseTransfer
{
    return HorseTransfer::create([
        'horse_id' => $horse->id,
        'from_user_id' => $from->id,
        'to_user_id' => $to->id,
        'status' => HorseTransferStatus::Pending,
        'notes' => 'Please take good care of them.',
    ]);
}

it('lets an owner request a transfer', function () {
    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $horse = transferableHorse($owner);

    actingAs($owner)
        ->post(route('horse-transfers.store', $horse), [
            'to_user_id' => $recipient->id,
            'notes' => 'A gift.',
        ])
        ->assertRedirect();

    $transfer = HorseTransfer::query()->where('horse_id', $horse->id)->sole();

    expect($transfer->from_user_id)->toBe($owner->id)
        ->and($transfer->to_user_id)->toBe($recipient->id)
        ->and($transfer->status)->toBe(HorseTransferStatus::Pending)
        ->and($transfer->notes)->toBe('A gift.')
        ->and($horse->fresh()->owner_id)->toBe($owner->id);
});

it('refuses requests for ineligible horses', function () {
    $owner = User::factory()->create();
    $recipient = User::factory()->create();

    $unapproved = Horse::factory()->create([
        'owner_id' => $owner->id,
        'bred_by' => $owner->id,
        'state' => HorseState::Pending,
    ]);

    $archived = Horse::factory()->create([
        'owner_id' => $owner->id,
        'bred_by' => $owner->id,
        'state' => HorseState::Pending,
        'archived_at' => now(),
    ]);

    $dead = transferableHorse($owner);
    $dead->update(['died_at' => now()]);

    foreach ([$unapproved, $archived, $dead] as $horse) {
        actingAs($owner)
            ->post(route('horse-transfers.store', $horse), ['to_user_id' => $recipient->id])
            ->assertSessionHasErrors();
    }

    expect(HorseTransfer::query()->count())->toBe(0);
});

it('forbids requesting a transfer of someone elses horse', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $recipient = User::factory()->create();
    $horse = transferableHorse($owner);

    actingAs($stranger)
        ->post(route('horse-transfers.store', $horse), ['to_user_id' => $recipient->id])
        ->assertForbidden();

    expect(HorseTransfer::query()->count())->toBe(0);
});

it('allows only one pending transfer per horse', function () {
    $owner = User::factory()->create();
    $first = User::factory()->create();
    $second = User::factory()->create();
    $horse = transferableHorse($owner);

    pendingTransfer($horse, $owner, $first);

    expect(fn () => DB::table('horse_transfers')->insert([
        'horse_id' => $horse->id,
        'from_user_id' => $owner->id,
        'to_user_id' => $second->id,
        'status' => HorseTransferStatus::Pending->value,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    actingAs($owner)
        ->post(route('horse-transfers.store', $horse), ['to_user_id' => $second->id])
        ->assertSessionHasErrors();

    expect(HorseTransfer::query()->where('horse_id', $horse->id)->count())->toBe(1);
});

it('moves the horse exactly once under concurrent approval', function () {
    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $admin = User::factory()->create(['role' => Role::Admin]);
    $horse = transferableHorse($owner);

    $transfer = pendingTransfer($horse, $owner, $recipient);
    $stale = HorseTransfer::query()->find($transfer->id);

    $service = app(HorseTransferService::class);
    $service->approve($transfer, $admin);

    expect(fn () => $service->approve($stale, $admin))->toThrow(RuntimeException::class);

    expect($horse->fresh()->owner_id)->toBe($recipient->id)
        ->and(HorseTransfer::query()->where('horse_id', $horse->id)->where('status', HorseTransferStatus::Approved)->count())->toBe(1);
});

it('lets the sender cancel a pending transfer', function () {
    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $horse = transferableHorse($owner);
    $transfer = pendingTransfer($horse, $owner, $recipient);

    actingAs($recipient)
        ->post(route('horse-transfers.cancel', $transfer))
        ->assertForbidden();

    actingAs($owner)
        ->post(route('horse-transfers.cancel', $transfer))
        ->assertRedirect();

    expect($transfer->fresh()->status)->toBe(HorseTransferStatus::Cancelled)
        ->and($horse->fresh()->owner_id)->toBe($owner->id);
});

it('leaves ownership untouched on rejection', function () {
    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $admin = User::factory()->create(['role' => Role::Admin]);
    $horse = transferableHorse($owner);
    $transfer = pendingTransfer($horse, $owner, $recipient);

    actingAs($admin)
        ->post(route('admin.horse-transfers.reject', $transfer), ['reason' => 'Not allowed right now.'])
        ->assertRedirect();

    $transfer->refresh();

    expect($transfer->status)->toBe(HorseTransferStatus::Rejected)
        ->and($transfer->reason)->toBe('Not allowed right now.')
        ->and($horse->fresh()->owner_id)->toBe($owner->id)
        ->and(Message::query()->where('user_id', $owner->id)->where('initial_message', 'like', '%Not allowed right now.%')->exists())->toBeTrue();
});

it('moves ownership and detaches the horse from its herd', function () {
    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $admin = User::factory()->create(['role' => Role::Admin]);

    $herd = Herd::factory()->create(['owner_id' => $owner->id, 'created_by' => $owner->id]);
    $horse = transferableHorse($owner);
    $horse->update(['herd_id' => $herd->id]);

    $transfer = pendingTransfer($horse, $owner, $recipient);

    actingAs($admin)
        ->post(route('admin.horse-transfers.approve', $transfer))
        ->assertRedirect();

    $horse->refresh();

    expect($horse->owner_id)->toBe($recipient->id)
        ->and($horse->herd_id)->toBeNull()
        ->and($transfer->fresh()->status)->toBe(HorseTransferStatus::Approved)
        ->and(Message::query()->where('user_id', $recipient->id)->exists())->toBeTrue()
        ->and(Message::query()->where('user_id', $owner->id)->exists())->toBeTrue();
});

it('clears herd leadership when the leader is transferred', function () {
    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $admin = User::factory()->create(['role' => Role::Admin]);

    $horse = transferableHorse($owner);
    $herd = Herd::factory()->create([
        'owner_id' => $owner->id,
        'created_by' => $owner->id,
        'herd_leader_id' => $horse->id,
    ]);
    $horse->update(['herd_id' => $herd->id]);

    $transfer = pendingTransfer($horse, $owner, $recipient);

    actingAs($admin)
        ->post(route('admin.horse-transfers.approve', $transfer))
        ->assertRedirect();

    expect($herd->fresh()->herd_leader_id)->toBeNull()
        ->and($horse->fresh()->herd_id)->toBeNull();
});

it('returns equipped items to the sender', function () {
    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $admin = User::factory()->create(['role' => Role::Admin]);

    $item = Item::create([
        'name' => 'Saddle',
        'max_count' => 3,
        'uses_per_unit' => 1,
        'description' => 'desc',
        'is_active' => true,
    ]);

    $horse = transferableHorse($owner);
    $owner->items()->attach($item->id, ['quantity' => 1]);

    actingAs($owner)->post(route('horses.equipment.store', $horse), ['item_id' => $item->id]);

    expect($horse->fresh()->equipment)->toHaveCount(1);

    $transfer = pendingTransfer($horse->fresh(), $owner, $recipient);

    actingAs($admin)
        ->post(route('admin.horse-transfers.approve', $transfer))
        ->assertRedirect();

    $senderQuantity = (int) DB::table('user_items')
        ->where('user_id', $owner->id)
        ->where('item_id', $item->id)
        ->value('quantity');

    expect($horse->fresh()->equipment)->toBe([])
        ->and($senderQuantity)->toBe(1)
        ->and(DB::table('user_items')->where('user_id', $recipient->id)->count())->toBe(0);
});

it('leaves pending breeding requests with the original requester', function () {
    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $admin = User::factory()->create(['role' => Role::Admin]);

    $breeding = BreedingRequest::factory()->create();
    $requesterId = $breeding->requester_id;
    $sire = Horse::query()->findOrFail($breeding->sire_id);
    $sire->update(['owner_id' => $owner->id, 'approved_at' => now()]);

    $transfer = pendingTransfer($sire->fresh(), $owner, $recipient);

    actingAs($admin)
        ->post(route('admin.horse-transfers.approve', $transfer))
        ->assertRedirect();

    $breeding->refresh();

    expect($breeding->status)->toBe(BreedingRequestStatus::PendingStaff)
        ->and($breeding->requester_id)->toBe($requesterId)
        ->and($sire->fresh()->owner_id)->toBe($recipient->id);
});

it('requires a reason to reject', function () {
    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $admin = User::factory()->create(['role' => Role::Admin]);
    $horse = transferableHorse($owner);
    $transfer = pendingTransfer($horse, $owner, $recipient);

    actingAs($admin)
        ->post(route('admin.horse-transfers.reject', $transfer), ['reason' => ''])
        ->assertSessionHasErrors('reason');

    expect($transfer->fresh()->status)->toBe(HorseTransferStatus::Pending);
});

it('messages both parties on an admin transfer', function () {
    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $admin = User::factory()->create(['role' => Role::Admin]);
    $horse = transferableHorse($owner);

    actingAs($admin)
        ->post(route('admin.horses.transfer', $horse), [
            'to_user_id' => $recipient->id,
            'reason' => 'Correcting a bad import.',
        ])
        ->assertRedirect();

    expect(Message::query()->where('user_id', $owner->id)->where('horse_id', $horse->id)->exists())->toBeTrue()
        ->and(Message::query()->where('user_id', $recipient->id)->where('horse_id', $horse->id)->exists())->toBeTrue();
});

it('lets an admin transfer any horse immediately', function () {
    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $admin = User::factory()->create(['role' => Role::Admin]);

    $unapproved = Horse::factory()->create([
        'owner_id' => $owner->id,
        'bred_by' => $owner->id,
        'state' => HorseState::Pending,
    ]);

    $dead = transferableHorse($owner);
    $dead->update(['died_at' => now()]);

    foreach ([$unapproved, $dead] as $horse) {
        actingAs($admin)
            ->post(route('admin.horses.transfer', $horse), [
                'to_user_id' => $recipient->id,
                'reason' => 'Admin correction.',
            ])
            ->assertRedirect();

        expect($horse->fresh()->owner_id)->toBe($recipient->id);
    }

    $transfers = HorseTransfer::query()->get();

    expect($transfers)->toHaveCount(2)
        ->and($transfers->pluck('status')->unique()->values()->all())->toBe([HorseTransferStatus::Approved]);
});

it('requires a reason for an admin transfer', function () {
    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $admin = User::factory()->create(['role' => Role::Admin]);
    $horse = transferableHorse($owner);

    actingAs($admin)
        ->post(route('admin.horses.transfer', $horse), ['to_user_id' => $recipient->id])
        ->assertSessionHasErrors('reason');

    expect($horse->fresh()->owner_id)->toBe($owner->id);
});

it('logs admin transfers', function () {
    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $admin = User::factory()->create(['role' => Role::Admin]);
    $horse = transferableHorse($owner);

    actingAs($admin)
        ->post(route('admin.horses.transfer', $horse), [
            'to_user_id' => $recipient->id,
            'reason' => 'Admin correction.',
        ])
        ->assertRedirect();

    $log = AdminSubmissionLog::query()->where('horse_id', $horse->id)->sole();

    expect($log->admin_id)->toBe($admin->id)
        ->and($log->action)->toBe(AdminAction::TransferredDirectly)
        ->and($log->notes)->toBe('Admin correction.');

    $queued = pendingTransfer($horse->fresh(), $recipient, $owner);

    actingAs($admin)
        ->post(route('admin.horse-transfers.approve', $queued))
        ->assertRedirect();

    expect(AdminSubmissionLog::query()->where('horse_id', $horse->id)->where('action', AdminAction::TransferApproved)->exists())->toBeTrue();
});

it('marks a sanctuary transfer as npc and claimable', function () {
    $owner = User::factory()->create();
    $sanctuary = User::factory()->create(['is_sanctuary' => true, 'name' => 'Sanctuary']);
    $admin = User::factory()->create(['role' => Role::Admin]);
    $horse = transferableHorse($owner);

    actingAs($admin)
        ->post(route('admin.horses.transfer', $horse), [
            'to_user_id' => $sanctuary->id,
            'reason' => 'Handing over to the Sanctuary.',
        ])
        ->assertRedirect();

    $horse->refresh();

    expect($horse->owner_id)->toBe($sanctuary->id)
        ->and($horse->is_npc)->toBeTrue()
        ->and($horse->is_claimable)->toBeTrue();
});

it('excludes the sanctuary from the player recipient list', function () {
    $owner = User::factory()->create();
    $sanctuary = User::factory()->create(['is_sanctuary' => true, 'name' => 'Sanctuary']);
    $banned = User::factory()->create(['banned_at' => now()]);
    $friend = User::factory()->create();
    $horse = transferableHorse($owner);

    actingAs($owner)
        ->get(route('horses.show', $horse))
        ->assertInertia(fn ($page) => $page
            ->where('can.transfer', true)
            ->where('transferRecipients', fn ($recipients) => collect($recipients)->pluck('id')->all() === [$friend->id])
        );

    actingAs($owner)
        ->post(route('horse-transfers.store', $horse), ['to_user_id' => $sanctuary->id])
        ->assertSessionHasErrors('to_user_id');

    actingAs($owner)
        ->post(route('horse-transfers.store', $horse), ['to_user_id' => $banned->id])
        ->assertSessionHasErrors('to_user_id');

    expect(HorseTransfer::query()->count())->toBe(0);
});

it('forbids approving without the submissions capability', function () {
    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $gameMaster = User::factory()->create(['role' => Role::GameMaster]);
    $horse = transferableHorse($owner);
    $transfer = pendingTransfer($horse, $owner, $recipient);

    actingAs($gameMaster)
        ->post(route('admin.horse-transfers.approve', $transfer))
        ->assertForbidden();

    actingAs($gameMaster)
        ->post(route('admin.horse-transfers.reject', $transfer), ['reason' => 'No.'])
        ->assertForbidden();

    expect($transfer->fresh()->status)->toBe(HorseTransferStatus::Pending)
        ->and($horse->fresh()->owner_id)->toBe($owner->id);
});

it('forbids direct transfer without the horses capability', function () {
    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $designer = User::factory()->create(['role' => Role::Designer]);
    $horse = transferableHorse($owner);

    expect($designer->can('admin.horses'))->toBeFalse();

    actingAs($designer)
        ->post(route('admin.horses.transfer', $horse), [
            'to_user_id' => $recipient->id,
            'reason' => 'Nope.',
        ])
        ->assertForbidden();

    actingAs($designer)
        ->get(route('admin.index'))
        ->assertInertia(fn ($page) => $page->where('adminCapabilities', fn ($areas) => ! in_array('horses', collect($areas)->all(), true)));

    expect($horse->fresh()->owner_id)->toBe($owner->id);
});
