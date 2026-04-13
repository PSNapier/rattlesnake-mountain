<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

interface Item {
	id: number;
	name: string;
	max_count: number;
	description: string | null;
	is_active: boolean;
}

interface ShopListing {
	id: number;
	item_id: number;
	item_name: string;
	item_max_count: number;
	visible_in_shop: boolean;
	scorpion_price: number;
	shop_description: string | null;
	image_path: string | null;
	sort_order: number;
}

const props = defineProps<{
	items: Item[];
	shopListings: ShopListing[];
}>();

const createForm = reactive({
	item_id: 0,
	visible_in_shop: true,
	scorpion_price: 0,
	shop_description: '',
	image_path: '',
	sort_order: 0,
});

const editableRows = reactive<Record<number, ShopListing>>({});

const availableItems = computed(() => {
	const assignedItemIds = new Set(props.shopListings.map((listing) => listing.item_id));

	return props.items
		.filter((item) => item.is_active && !assignedItemIds.has(item.id))
		.sort((a, b) => a.name.localeCompare(b.name));
});

const getRowState = (listing: ShopListing): ShopListing => {
	if (!editableRows[listing.id]) {
		editableRows[listing.id] = { ...listing };
	}

	return editableRows[listing.id];
};

const createListing = (): void => {
	router.post(route('admin.shop-listings.store'), {
		...createForm,
		shop_description: createForm.shop_description || null,
		image_path: createForm.image_path || null,
	});
};

const updateListing = (listing: ShopListing): void => {
	const row = getRowState(listing);
	router.put(route('admin.shop-listings.update', listing.id), {
		visible_in_shop: row.visible_in_shop,
		scorpion_price: row.scorpion_price,
		shop_description: row.shop_description || null,
		image_path: row.image_path || null,
		sort_order: row.sort_order,
	});
};

const removeListing = (listing: ShopListing): void => {
	router.delete(route('admin.shop-listings.destroy', listing.id));
};
</script>

<template>
	<Card class="mb-6">
		<CardHeader>
			<CardTitle>Create Shop Listing</CardTitle>
		</CardHeader>
		<CardContent class="space-y-4">
			<div>
				<Label for="create-item">Item</Label>
				<select
					id="create-item"
					v-model.number="createForm.item_id"
					class="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 mt-1 flex w-full min-w-0 rounded-md border bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50 md:text-sm">
					<option :value="0">Select item...</option>
					<option
						v-for="item in availableItems"
						:key="item.id"
						:value="item.id">
						{{ item.name }}
					</option>
				</select>
			</div>

			<div class="grid gap-3 md:grid-cols-3">
				<div>
					<Label for="create-price">Scorpion Price</Label>
					<Input
						id="create-price"
						v-model.number="createForm.scorpion_price"
						type="number"
						min="0"
						class="mt-1" />
				</div>
				<div>
					<Label for="create-sort-order">Sort Order</Label>
					<Input
						id="create-sort-order"
						v-model.number="createForm.sort_order"
						type="number"
						min="0"
						class="mt-1" />
				</div>
				<div class="flex items-end gap-2">
					<input
						id="create-visible"
						v-model="createForm.visible_in_shop"
						type="checkbox"
						class="h-4 w-4 rounded border-gray-300" />
					<Label for="create-visible">Visible In Shop</Label>
				</div>
			</div>

			<div>
				<Label for="create-image-path">Image Path</Label>
				<Input
					id="create-image-path"
					v-model="createForm.image_path"
					type="text"
					placeholder="/images/shop/example.png"
					class="mt-1" />
			</div>

			<div>
				<Label for="create-description">Shop Description</Label>
				<textarea
					id="create-description"
					v-model="createForm.shop_description"
					rows="3"
					class="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 mt-1 flex w-full min-w-0 rounded-md border bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50 md:text-sm" />
			</div>

			<Button
				:disabled="createForm.item_id === 0"
				@click="createListing">
				Create Listing
			</Button>
		</CardContent>
	</Card>

	<Card>
		<CardHeader>
			<CardTitle>Manage Shop Listings</CardTitle>
		</CardHeader>
		<CardContent>
			<div class="overflow-x-auto">
				<table class="w-full border-collapse">
					<thead>
						<tr class="border-b border-gray-200">
							<th class="text-cape-palliser-950 px-3 py-3 text-left text-sm font-semibold">Item</th>
							<th class="text-cape-palliser-950 px-3 py-3 text-left text-sm font-semibold">Price</th>
							<th class="text-cape-palliser-950 px-3 py-3 text-left text-sm font-semibold">Order</th>
							<th class="text-cape-palliser-950 px-3 py-3 text-left text-sm font-semibold">Visible</th>
							<th class="text-cape-palliser-950 px-3 py-3 text-left text-sm font-semibold">Image</th>
							<th class="text-cape-palliser-950 px-3 py-3 text-left text-sm font-semibold">Description</th>
							<th class="text-cape-palliser-950 px-3 py-3 text-left text-sm font-semibold">Actions</th>
						</tr>
					</thead>
					<tbody>
						<tr
							v-for="listing in props.shopListings"
							:key="listing.id"
							class="border-b border-gray-200 align-top">
							<td class="px-3 py-3 text-sm font-medium text-cape-palliser-950">
								{{ listing.item_name }}
							</td>
							<td class="px-3 py-3">
								<Input
									v-model.number="getRowState(listing).scorpion_price"
									type="number"
									min="0" />
							</td>
							<td class="px-3 py-3">
								<Input
									v-model.number="getRowState(listing).sort_order"
									type="number"
									min="0" />
							</td>
							<td class="px-3 py-3 text-center">
								<input
									v-model="getRowState(listing).visible_in_shop"
									type="checkbox"
									class="h-4 w-4 rounded border-gray-300" />
							</td>
							<td class="px-3 py-3">
								<Input
									v-model="getRowState(listing).image_path"
									type="text"
									placeholder="/images/shop/example.png" />
							</td>
							<td class="px-3 py-3">
								<textarea
									v-model="getRowState(listing).shop_description"
									rows="3"
									class="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 flex w-72 min-w-0 rounded-md border bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50 md:text-sm" />
							</td>
							<td class="px-3 py-3">
								<div class="flex gap-2">
									<Button
										size="sm"
										@click="updateListing(listing)">
										Save
									</Button>
									<Button
										size="sm"
										variant="destructive"
										@click="removeListing(listing)">
										Delete
									</Button>
								</div>
							</td>
						</tr>
						<tr v-if="props.shopListings.length === 0">
							<td
								colspan="7"
								class="px-3 py-6 text-center text-sm text-cape-palliser-600">
								No shop listings yet.
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</CardContent>
	</Card>
</template>
