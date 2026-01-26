<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
	Dialog,
	DialogContent,
	DialogFooter,
	DialogHeader,
	DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Item {
	id: number;
	name: string;
	max_count: number;
	description: string | null;
	is_active: boolean;
}

interface Props {
	items: Item[];
}

const props = defineProps<Props>();

const showEditItemModal = ref(false);
const selectedItem = ref<Item | null>(null);
const itemForm = ref({
	name: '',
	max_count: 1,
	description: '',
	is_active: true,
});

const openEditItemModal = (item: Item): void => {
	selectedItem.value = item;
	itemForm.value = {
		name: item.name,
		max_count: item.max_count,
		description: item.description || '',
		is_active: item.is_active,
	};
	showEditItemModal.value = true;
};

const closeEditItemModal = (): void => {
	showEditItemModal.value = false;
	selectedItem.value = null;
	itemForm.value = {
		name: '',
		max_count: 1,
		description: '',
		is_active: true,
	};
};

const handleUpdateItem = (): void => {
	if (!selectedItem.value) {
		return;
	}

	router.put(
		route('admin.items.update', selectedItem.value.id),
		itemForm.value,
		{
			onSuccess: () => {
				closeEditItemModal();
			},
			onError: () => {
				// Keep modal open on error so user can fix validation issues
			},
		},
	);
};
</script>

<template>
	<Card>
		<CardContent>
			<div class="overflow-x-auto">
				<table class="w-full border-collapse">
					<thead>
						<tr class="border-b border-gray-200">
							<th class="text-cape-palliser-950 px-4 py-3 text-left text-sm font-semibold">
								Name
							</th>
							<th class="text-cape-palliser-950 px-4 py-3 text-left text-sm font-semibold">
								Max Count
							</th>
							<th class="text-cape-palliser-950 px-4 py-3 text-left text-sm font-semibold">
								Description
							</th>
							<th class="text-cape-palliser-950 px-4 py-3 text-left text-sm font-semibold">
								Status
							</th>
							<th class="text-cape-palliser-950 px-4 py-3 text-left text-sm font-semibold"></th>
						</tr>
					</thead>
					<tbody>
						<tr
							v-if="props.items.length === 0"
							class="border-b border-gray-200">
							<td
								colspan="5"
								class="text-cape-palliser-600 px-4 py-8 text-center">
								No items found
							</td>
						</tr>
						<tr
							v-for="item in props.items"
							:key="item.id"
							class="border-b border-gray-200 hover:bg-gray-50">
							<td class="text-cape-palliser-950 px-4 py-3 text-sm">
								{{ item.name }}
							</td>
							<td class="text-cape-palliser-700 px-4 py-3 text-sm">
								{{ item.max_count }}
							</td>
							<td class="text-cape-palliser-700 px-4 py-3 text-sm">
								{{ item.description || '—' }}
							</td>
							<td class="px-4 py-3 text-sm">
								<span
									:class="[
										'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
										item.is_active
											? 'bg-green-100 text-green-800'
											: 'bg-gray-100 text-gray-800',
									]">
									{{ item.is_active ? 'Active' : 'Inactive' }}
								</span>
							</td>
							<td class="px-4 py-3 text-sm">
								<Button
									variant="outline"
									size="sm"
									@click="openEditItemModal(item)">
									Edit
								</Button>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</CardContent>
	</Card>

	<!-- Edit Item Modal -->
	<Dialog v-model:open="showEditItemModal">
		<DialogContent>
			<DialogHeader>
				<DialogTitle>
					Edit Item
					<span
						v-if="selectedItem"
						class="text-cape-palliser-700 text-sm font-normal">
						— {{ selectedItem.name }}
					</span>
				</DialogTitle>
			</DialogHeader>

			<div class="space-y-4">
				<div>
					<Label for="item-name">Name</Label>
					<Input
						id="item-name"
						v-model="itemForm.name"
						type="text"
						class="mt-1 w-full" />
				</div>

				<div>
					<Label for="item-max-count">Max Count</Label>
					<Input
						id="item-max-count"
						v-model.number="itemForm.max_count"
						type="number"
						min="1"
						max="999"
						class="mt-1 w-full" />
				</div>

				<div>
					<Label for="item-description">Description</Label>
					<textarea
						id="item-description"
						v-model="itemForm.description"
						rows="4"
						class="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 mt-1 flex w-full min-w-0 rounded-md border bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
						placeholder="Item description..." />
				</div>

				<div class="flex items-center gap-2">
					<input
						id="item-is-active"
						v-model="itemForm.is_active"
						type="checkbox"
						class="h-4 w-4 rounded border-gray-300" />
					<Label for="item-is-active">Active</Label>
				</div>
			</div>

			<DialogFooter>
				<Button
					variant="outline"
					@click="closeEditItemModal">
					Cancel
				</Button>
				<Button @click="handleUpdateItem">
					Save Changes
				</Button>
			</DialogFooter>
		</DialogContent>
	</Dialog>
</template>
