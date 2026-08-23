<?php

use App\Enums\TradeStatus;
use App\Models\Item;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function makeTradeItem(string $name = 'Feather', int $maxCount = 10): Item
{
    return Item::create([
        'name' => $name,
        'max_count' => $maxCount,
        'description' => 'A tradeable trinket.',
        'is_active' => true,
    ]);
}

function tradeOwnedQuantity(User $user, Item $item): int
{
    return (int) (DB::table('user_items')
        ->where('user_id', $user->id)
        ->where('item_id', $item->id)
        ->value('quantity') ?? 0);
}

it('creates an offer between two users', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $item = makeTradeItem();
    $sender->items()->attach($item->id, ['quantity' => 5]);

    actingAs($sender)->post(route('trades.store'), [
        'to_user_id' => $recipient->id,
        'note' => 'Enjoy.',
        'items' => [
            ['item_id' => $item->id, 'quantity' => 2],
        ],
    ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $trade = Trade::query()->latest('id')->firstOrFail();

    expect($trade->from_user_id)->toBe($sender->id);
    expect($trade->to_user_id)->toBe($recipient->id);
    expect($trade->status)->toBe(TradeStatus::Pending);
    expect($trade->items)->toHaveCount(1);
    expect($trade->items->first()->quantity)->toBe(2);

    // Nothing moves until the recipient accepts.
    expect(tradeOwnedQuantity($sender, $item))->toBe(5);
    expect(tradeOwnedQuantity($recipient, $item))->toBe(0);

    // Both parties can see the trade in their history.
    actingAs($sender)->get(route('trades.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Trades/Index')->has('trades.data', 1));

    actingAs($recipient)->get(route('trades.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->has('trades.data', 1));
});

it('transfers quantities atomically on accept', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $feather = makeTradeItem('Feather', 10);
    $stone = makeTradeItem('Stone', 10);

    $sender->items()->attach($feather->id, ['quantity' => 5]);
    $sender->items()->attach($stone->id, ['quantity' => 3]);
    $recipient->items()->attach($feather->id, ['quantity' => 1]);

    actingAs($sender)->post(route('trades.store'), [
        'to_user_id' => $recipient->id,
        'items' => [
            ['item_id' => $feather->id, 'quantity' => 2],
            ['item_id' => $stone->id, 'quantity' => 3],
        ],
    ])->assertRedirect();

    $trade = Trade::query()->latest('id')->firstOrFail();

    actingAs($recipient)->post(route('trades.accept', $trade))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(tradeOwnedQuantity($sender, $feather))->toBe(3);
    expect(tradeOwnedQuantity($sender, $stone))->toBe(0);
    expect(tradeOwnedQuantity($recipient, $feather))->toBe(3);
    expect(tradeOwnedQuantity($recipient, $stone))->toBe(3);

    expect($trade->fresh()->status)->toBe(TradeStatus::Accepted);
});

it('rejects an offer exceeding owned quantity', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $item = makeTradeItem();
    $sender->items()->attach($item->id, ['quantity' => 1]);

    actingAs($sender)->post(route('trades.store'), [
        'to_user_id' => $recipient->id,
        'items' => [
            ['item_id' => $item->id, 'quantity' => 2],
        ],
    ])->assertSessionHasErrors();

    expect(Trade::query()->count())->toBe(0);
    expect(tradeOwnedQuantity($sender, $item))->toBe(1);
});

it('rejects an accept that would exceed the recipient max count', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $item = makeTradeItem('Feather', 5);

    $sender->items()->attach($item->id, ['quantity' => 3]);
    $recipient->items()->attach($item->id, ['quantity' => 4]);

    actingAs($sender)->post(route('trades.store'), [
        'to_user_id' => $recipient->id,
        'items' => [
            ['item_id' => $item->id, 'quantity' => 3],
        ],
    ])->assertRedirect();

    $trade = Trade::query()->latest('id')->firstOrFail();

    actingAs($recipient)->post(route('trades.accept', $trade))->assertSessionHasErrors();

    // Nothing moved, and the offer stays open rather than dying silently.
    expect(tradeOwnedQuantity($sender, $item))->toBe(3);
    expect(tradeOwnedQuantity($recipient, $item))->toBe(4);
    expect($trade->fresh()->status)->toBe(TradeStatus::Pending);
});

it('refuses to accept an item retired after the offer', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $item = makeTradeItem();
    $sender->items()->attach($item->id, ['quantity' => 5]);

    actingAs($sender)->post(route('trades.store'), [
        'to_user_id' => $recipient->id,
        'items' => [['item_id' => $item->id, 'quantity' => 2]],
    ])->assertRedirect();

    $trade = Trade::query()->latest('id')->firstOrFail();

    $item->update(['is_active' => false]);

    actingAs($recipient)->post(route('trades.accept', $trade))->assertSessionHasErrors();

    expect($trade->fresh()->status)->toBe(TradeStatus::Pending);
    expect(tradeOwnedQuantity($recipient, $item))->toBe(0);
    expect(tradeOwnedQuantity($sender, $item))->toBe(5);
});

it('refuses to accept a trade whose items were deleted', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $item = makeTradeItem();
    $sender->items()->attach($item->id, ['quantity' => 5]);

    actingAs($sender)->post(route('trades.store'), [
        'to_user_id' => $recipient->id,
        'items' => [['item_id' => $item->id, 'quantity' => 2]],
    ])->assertRedirect();

    $trade = Trade::query()->latest('id')->firstOrFail();

    // Hard-deleting the item cascades the trade line away. Accepting an empty
    // trade would close it as if a transfer had happened.
    $item->delete();

    actingAs($recipient)->post(route('trades.accept', $trade))->assertSessionHasErrors();

    expect($trade->fresh()->status)->toBe(TradeStatus::Pending);
});

it('forbids a banned user from acting on an open trade', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $item = makeTradeItem();
    $sender->items()->attach($item->id, ['quantity' => 5]);

    actingAs($sender)->post(route('trades.store'), [
        'to_user_id' => $recipient->id,
        'items' => [['item_id' => $item->id, 'quantity' => 2]],
    ])->assertRedirect();

    $trade = Trade::query()->latest('id')->firstOrFail();

    $recipient->update(['banned_at' => now()]);
    $sender->update(['banned_at' => now()]);

    actingAs($recipient)->post(route('trades.accept', $trade))->assertForbidden();
    actingAs($recipient)->post(route('trades.decline', $trade))->assertForbidden();
    actingAs($sender)->post(route('trades.cancel', $trade))->assertForbidden();

    expect($trade->fresh()->status)->toBe(TradeStatus::Pending);
    expect(tradeOwnedQuantity($recipient, $item))->toBe(0);
});

it('counts rejected offers against the rate limit', function () {
    config()->set('trading.rate_limit.max_attempts', 3);

    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $item = makeTradeItem();
    $sender->items()->attach($item->id, ['quantity' => 1]);

    // Each of these fails on quantity, but a failed offer is just as cheap to
    // spam as a successful one.
    foreach (range(1, 3) as $ignored) {
        actingAs($sender)->post(route('trades.store'), [
            'to_user_id' => $recipient->id,
            'items' => [['item_id' => $item->id, 'quantity' => 99]],
        ])->assertSessionHasErrors('items');
    }

    actingAs($sender)->post(route('trades.store'), [
        'to_user_id' => $recipient->id,
        'items' => [['item_id' => $item->id, 'quantity' => 1]],
    ])->assertSessionHasErrors('trade');

    expect(Trade::query()->count())->toBe(0);
});

it('credits a recipient who has never held the item', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $item = makeTradeItem();
    $sender->items()->attach($item->id, ['quantity' => 4]);

    actingAs($sender)->post(route('trades.store'), [
        'to_user_id' => $recipient->id,
        'items' => [['item_id' => $item->id, 'quantity' => 4]],
    ])->assertRedirect();

    $trade = Trade::query()->latest('id')->firstOrFail();

    actingAs($recipient)->post(route('trades.accept', $trade))->assertRedirect();

    expect(tradeOwnedQuantity($recipient, $item))->toBe(4);
    expect(tradeOwnedQuantity($sender, $item))->toBe(0);
    expect(DB::table('user_items')->where('user_id', $recipient->id)->count())->toBe(1);
});

it('offers to a recipient picked by name', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create(['name' => 'Sagebrush']);
    $banned = User::factory()->create(['banned_at' => now()]);
    $item = makeTradeItem();
    $sender->items()->attach($item->id, ['quantity' => 1]);

    actingAs($sender)->get(route('trades.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('recipients', fn ($recipients) => collect($recipients)->contains(
                fn ($candidate) => $candidate['id'] === $recipient->id && $candidate['name'] === 'Sagebrush'
            ))
            // Self and banned players are not offerable.
            ->where('recipients', fn ($recipients) => collect($recipients)
                ->pluck('id')
                ->doesntContain($sender->id))
            ->where('recipients', fn ($recipients) => collect($recipients)
                ->pluck('id')
                ->doesntContain($banned->id)));
});

it('forbids a third party from accepting', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $stranger = User::factory()->create();
    $item = makeTradeItem();
    $sender->items()->attach($item->id, ['quantity' => 5]);

    actingAs($sender)->post(route('trades.store'), [
        'to_user_id' => $recipient->id,
        'items' => [['item_id' => $item->id, 'quantity' => 2]],
    ])->assertRedirect();

    $trade = Trade::query()->latest('id')->firstOrFail();

    actingAs($stranger)->post(route('trades.accept', $trade))->assertForbidden();
    actingAs($stranger)->post(route('trades.decline', $trade))->assertForbidden();
    actingAs($stranger)->post(route('trades.cancel', $trade))->assertForbidden();

    // The sender cannot accept their own offer either.
    actingAs($sender)->post(route('trades.accept', $trade))->assertForbidden();

    expect($trade->fresh()->status)->toBe(TradeStatus::Pending);
    expect(tradeOwnedQuantity($recipient, $item))->toBe(0);

    // A stranger cannot even read someone else's trade history.
    actingAs($stranger)->get(route('trades.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->has('trades.data', 0));
});

it('cancels open offers and freezes accepted trades', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $item = makeTradeItem();
    $sender->items()->attach($item->id, ['quantity' => 5]);

    // Open offer: the sender can cancel it.
    actingAs($sender)->post(route('trades.store'), [
        'to_user_id' => $recipient->id,
        'items' => [['item_id' => $item->id, 'quantity' => 1]],
    ])->assertRedirect();

    $open = Trade::query()->latest('id')->firstOrFail();

    actingAs($sender)->post(route('trades.cancel', $open))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($open->fresh()->status)->toBe(TradeStatus::Cancelled);

    // A cancelled offer cannot then be accepted.
    actingAs($recipient)->post(route('trades.accept', $open))->assertSessionHasErrors();
    expect($open->fresh()->status)->toBe(TradeStatus::Cancelled);
    expect(tradeOwnedQuantity($recipient, $item))->toBe(0);

    // Accepted trades are immutable.
    actingAs($sender)->post(route('trades.store'), [
        'to_user_id' => $recipient->id,
        'items' => [['item_id' => $item->id, 'quantity' => 1]],
    ])->assertRedirect();

    $accepted = Trade::query()->latest('id')->firstOrFail();
    actingAs($recipient)->post(route('trades.accept', $accepted))->assertRedirect();

    actingAs($sender)->post(route('trades.cancel', $accepted))->assertSessionHasErrors();
    actingAs($recipient)->post(route('trades.decline', $accepted))->assertSessionHasErrors();
    actingAs($recipient)->post(route('trades.accept', $accepted))->assertSessionHasErrors();

    expect($accepted->fresh()->status)->toBe(TradeStatus::Accepted);
    // The double accept did not move a second copy.
    expect(tradeOwnedQuantity($recipient, $item))->toBe(1);
    // 5 owned, the cancelled offer moved nothing, the accepted one moved 1.
    expect(tradeOwnedQuantity($sender, $item))->toBe(4);
});
