<?php

use App\Models\Horse;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createEquippableItem(string $name = 'Bridle', int $maxCount = 3, int $usesPerUnit = 1): Item
{
    return Item::create([
        'name' => $name,
        'max_count' => $maxCount,
        'uses_per_unit' => $usesPerUnit,
        'description' => 'desc',
        'is_active' => true,
    ]);
}

function equippedOwnerQuantity(User $user, Item $item): int
{
    $row = $user->fresh()->items()->where('items.id', $item->id)->first();

    return $row === null ? 0 : (int) $row->pivot->quantity;
}

it('moves an owned item onto a horse', function () {
    $user = User::factory()->create();
    $horse = Horse::factory()->create(['owner_id' => $user->id, 'bred_by' => $user->id]);
    $item = createEquippableItem();

    $user->items()->attach($item->id, ['quantity' => 2]);

    $response = $this->actingAs($user)->post(route('horses.equipment.store', $horse), ['item_id' => $item->id]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $equipment = $horse->fresh()->equipment;

    expect($equipment)->toHaveCount(1);
    expect($equipment[0]['item_id'])->toBe($item->id);
    expect($equipment[0]['uses_remaining'])->toBe(1);
    expect($equipment[0]['uid'])->toBeString();
    expect(equippedOwnerQuantity($user, $item))->toBe(1);
});

it('returns equipped gear to inventory on dequip', function () {
    $user = User::factory()->create();
    $horse = Horse::factory()->create(['owner_id' => $user->id, 'bred_by' => $user->id]);
    $item = createEquippableItem();

    $user->items()->attach($item->id, ['quantity' => 1]);

    $this->actingAs($user)->post(route('horses.equipment.store', $horse), ['item_id' => $item->id]);

    $uid = $horse->fresh()->equipment[0]['uid'];

    $response = $this->actingAs($user)->delete(route('horses.equipment.destroy', [$horse, $uid]));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect($horse->fresh()->equipment)->toBe([]);
    expect(equippedOwnerQuantity($user, $item))->toBe(1);
});

it('decrements uses per unit when consuming', function () {
    $user = User::factory()->create();
    $horse = Horse::factory()->create(['owner_id' => $user->id, 'bred_by' => $user->id]);
    $item = createEquippableItem(name: 'Salve', usesPerUnit: 3);

    $user->items()->attach($item->id, ['quantity' => 1]);

    $this->actingAs($user)->post(route('horses.equipment.store', $horse), ['item_id' => $item->id]);

    $uid = $horse->fresh()->equipment[0]['uid'];

    expect($horse->fresh()->equipment[0]['uses_remaining'])->toBe(3);

    $this->actingAs($user)->post(route('horses.equipment.use', [$horse, $uid]));
    expect($horse->fresh()->equipment[0]['uses_remaining'])->toBe(2);

    $this->actingAs($user)->post(route('horses.equipment.use', [$horse, $uid]));
    expect($horse->fresh()->equipment[0]['uses_remaining'])->toBe(1);

    $response = $this->actingAs($user)->post(route('horses.equipment.use', [$horse, $uid]));

    $response->assertSessionHas('success');
    expect($horse->fresh()->equipment)->toBe([]);
    expect(equippedOwnerQuantity($user, $item))->toBe(0);
});

it('refuses to return partly used gear to inventory', function () {
    $user = User::factory()->create();
    $horse = Horse::factory()->create(['owner_id' => $user->id, 'bred_by' => $user->id]);
    $item = createEquippableItem(name: 'Salve', usesPerUnit: 3);

    $user->items()->attach($item->id, ['quantity' => 1]);

    $this->actingAs($user)->post(route('horses.equipment.store', $horse), ['item_id' => $item->id]);

    $uid = $horse->fresh()->equipment[0]['uid'];

    $this->actingAs($user)->post(route('horses.equipment.use', [$horse, $uid]));

    $response = $this->actingAs($user)->delete(route('horses.equipment.destroy', [$horse, $uid]));

    $response->assertSessionHasErrors('equipment');
    expect($horse->fresh()->equipment)->toHaveCount(1);
    expect(equippedOwnerQuantity($user, $item))->toBe(0);
});

it('enforces max count on equip', function () {
    $user = User::factory()->create();
    $horse = Horse::factory()->create(['owner_id' => $user->id, 'bred_by' => $user->id]);
    $item = createEquippableItem(maxCount: 2);

    $user->items()->attach($item->id, ['quantity' => 5]);

    $this->actingAs($user)->post(route('horses.equipment.store', $horse), ['item_id' => $item->id]);
    $this->actingAs($user)->post(route('horses.equipment.store', $horse), ['item_id' => $item->id]);

    $response = $this->actingAs($user)->post(route('horses.equipment.store', $horse), ['item_id' => $item->id]);

    $response->assertSessionHasErrors('item_id');
    expect($horse->fresh()->equipment)->toHaveCount(2);
    expect(equippedOwnerQuantity($user, $item))->toBe(3);
});

it('refuses to equip an item the owner does not have', function () {
    $user = User::factory()->create();
    $horse = Horse::factory()->create(['owner_id' => $user->id, 'bred_by' => $user->id]);
    $item = createEquippableItem();

    $response = $this->actingAs($user)->post(route('horses.equipment.store', $horse), ['item_id' => $item->id]);

    $response->assertSessionHasErrors('item_id');
    expect($horse->fresh()->equipment)->toBe([]);
});

it('sends equipment props to both horse show routes', function () {
    $owner = User::factory()->create();
    $horse = Horse::factory()->published()->create(['owner_id' => $owner->id, 'bred_by' => $owner->id]);
    $item = createEquippableItem(name: 'Salve', usesPerUnit: 3);

    $owner->items()->attach($item->id, ['quantity' => 1]);

    $this->actingAs($owner)->post(route('horses.equipment.store', $horse), ['item_id' => $item->id]);

    $this->actingAs($owner)
        ->get(route('horses.show', $horse))
        ->assertInertia(fn ($page) => $page
            ->component('Horses/Show')
            ->has('equipment', 1)
            ->where('equipment.0.name', 'Salve')
            ->where('equipment.0.uses_per_unit', 3)
            ->has('equippableItems'));

    // publicShow renders the same component, so it has to supply the same props
    // or the Equipment card blows up on an undefined prop.
    $this->get(route('users.horses.show', [$owner, $horse]))
        ->assertInertia(fn ($page) => $page
            ->component('Horses/Show')
            ->has('equipment', 1)
            ->where('equippableItems', []));
});

it('forbids equipping on another users horse', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $horse = Horse::factory()->create(['owner_id' => $owner->id, 'bred_by' => $owner->id]);
    $item = createEquippableItem();

    $intruder->items()->attach($item->id, ['quantity' => 1]);

    $this->actingAs($intruder)
        ->post(route('horses.equipment.store', $horse), ['item_id' => $item->id])
        ->assertForbidden();

    expect($horse->fresh()->equipment)->toBe([]);
    expect(equippedOwnerQuantity($intruder, $item))->toBe(1);
});

it('forbids dequipping and using on another users horse', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $horse = Horse::factory()->create(['owner_id' => $owner->id, 'bred_by' => $owner->id]);
    $item = createEquippableItem(name: 'Salve', usesPerUnit: 3);

    $owner->items()->attach($item->id, ['quantity' => 1]);

    $this->actingAs($owner)->post(route('horses.equipment.store', $horse), ['item_id' => $item->id]);

    $uid = $horse->fresh()->equipment[0]['uid'];

    $this->actingAs($intruder)->delete(route('horses.equipment.destroy', [$horse, $uid]))->assertForbidden();
    $this->actingAs($intruder)->post(route('horses.equipment.use', [$horse, $uid]))->assertForbidden();

    expect($horse->fresh()->equipment)->toHaveCount(1);
    expect($horse->fresh()->equipment[0]['uses_remaining'])->toBe(3);
});
