<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
	Card,
	CardContent,
	CardHeader,
	CardTitle,
} from '@/components/ui/card';
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
import { computed, ref, watch } from 'vue';

interface Item {
	id: number;
	name: string;
	max_count: number;
	description: string | null;
	is_active: boolean;
}

interface User {
	id: number;
	name: string;
}

interface UserInventoryItem {
	id: number;
	name: string;
	description: string | null;
	quantity: number;
	max_count: number;
}

interface Props {
	items: Item[];
}

const props = defineProps<Props>();

// User management state
const userSearchQuery = ref('');
const userSuggestions = ref<User[]>([]);
const selectedUser = ref<User | null>(null);
const userInventory = ref<UserInventoryItem[]>([]);
const originalInventory = ref<UserInventoryItem[]>([]); // Original state for change detection
const showSuggestions = ref(false);
const isLoadingInventory = ref(false);
const isSaving = ref(false);
const activeItems = computed(() =>
	props.items.filter((item) => item.is_active),
);
const newItemId = ref<number>(0);
const newItemQuantity = ref<number>(1);

// Check if there are unsaved changes
const hasUnsavedChanges = computed(() => {
	if (originalInventory.value.length === 0) {
		return false;
	}
	
	// Create maps for comparison
	const originalMap = new Map(
		originalInventory.value.map((item) => [item.id, item.quantity]),
	);
	const currentMap = new Map(
		userInventory.value.map((item) => [item.id, item.quantity]),
	);
	
	// Check if any quantities differ
	for (const [id, quantity] of currentMap) {
		if (originalMap.get(id) !== quantity) {
			return true;
		}
	}
	
	// Check if any original items are missing
	for (const [id, quantity] of originalMap) {
		if (currentMap.get(id) !== quantity) {
			return true;
		}
	}
	
	return false;
});

// Search users with debounce
let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(userSearchQuery, (query) => {
	if (searchTimeout) {
		clearTimeout(searchTimeout);
	}

	// Don't search if query matches selected user name
	if (selectedUser.value && query === selectedUser.value.name) {
		userSuggestions.value = [];
		showSuggestions.value = false;
		return;
	}

	if (query.trim().length < 2) {
		userSuggestions.value = [];
		showSuggestions.value = false;
		return;
	}

	searchTimeout = setTimeout(async () => {
		// Double-check we still don't have a selected user matching the query
		if (selectedUser.value && query === selectedUser.value.name) {
			userSuggestions.value = [];
			showSuggestions.value = false;
			return;
		}

		try {
			const response = await fetch(
				route('admin.users.search', { q: query, limit: 10 }),
			);
			const users = await response.json();
			userSuggestions.value = users;
			// Only show suggestions if we don't have a selected user or query changed
			if (!selectedUser.value || query !== selectedUser.value.name) {
				showSuggestions.value = users.length > 0;
			}
		} catch (error) {
			console.error('Error searching users:', error);
			userSuggestions.value = [];
			showSuggestions.value = false;
		}
	}, 300);
});

const selectUser = async (user: User): Promise<void> => {
	// Clear any pending search timeout
	if (searchTimeout) {
		clearTimeout(searchTimeout);
		searchTimeout = null;
	}
	
	// Clear blur timeout
	if (blurTimeout) {
		clearTimeout(blurTimeout);
		blurTimeout = null;
	}
	
	// Hide suggestions immediately
	showSuggestions.value = false;
	userSuggestions.value = [];
	
	// Set selected user and search query
	selectedUser.value = user;
	userSearchQuery.value = user.name;
	
	// Load full inventory (first time load)
	isLoadingInventory.value = true;
	try {
		const response = await fetch(
			route('admin.users.inventory', user.id),
		);
		const data = await response.json();
		// Deep clone for both working and original copies
		userInventory.value = JSON.parse(JSON.stringify(data.inventory));
		originalInventory.value = JSON.parse(JSON.stringify(data.inventory));
	} catch (error) {
		console.error('Error loading inventory:', error);
		userInventory.value = [];
		originalInventory.value = [];
	} finally {
		isLoadingInventory.value = false;
	}
};


let blurTimeout: ReturnType<typeof setTimeout> | null = null;

const handleInputBlur = (): void => {
	// Only hide suggestions if we haven't just selected a user
	// The mousedown.prevent on suggestions should prevent blur from firing
	// but we add a delay just in case
	blurTimeout = setTimeout(() => {
		// Only hide if no user is selected or if search query doesn't match selected user
		if (!selectedUser.value || userSearchQuery.value !== selectedUser.value.name) {
			showSuggestions.value = false;
		}
	}, 200);
};

const handleSuggestionClick = (user: User): void => {
	// Clear any pending blur timeout
	if (blurTimeout) {
		clearTimeout(blurTimeout);
		blurTimeout = null;
	}
	// Immediately hide suggestions and select user
	showSuggestions.value = false;
	selectUser(user);
};

const handleInputKeydown = (event: KeyboardEvent): void => {
	if (event.key === 'Enter') {
		event.preventDefault();
		// If there are suggestions, select the first one
		if (userSuggestions.value.length > 0 && showSuggestions.value) {
			handleSuggestionClick(userSuggestions.value[0]);
		}
	} else if (event.key === 'Escape') {
		// Hide suggestions on Escape
		showSuggestions.value = false;
		if (blurTimeout) {
			clearTimeout(blurTimeout);
			blurTimeout = null;
		}
	}
};

const resetToOriginal = (): void => {
	// Reset local inventory to original state
	userInventory.value = JSON.parse(JSON.stringify(originalInventory.value));
};

const updateLocalItemQuantity = (
	itemId: number,
	quantity: number,
): void => {
	// Update local inventory only (no server call)
	const item = userInventory.value.find((i) => i.id === itemId);
	if (item) {
		item.quantity = quantity;
	}
};

const saveChanges = async (): Promise<void> => {
	if (!selectedUser.value || !hasUnsavedChanges.value || isSaving.value) {
		return;
	}

	isSaving.value = true;
	
	try {
		// Get all items that have changed
		const changes = userInventory.value
			.filter((item) => {
				const original = originalInventory.value.find(
					(o) => o.id === item.id,
				);
				return !original || original.quantity !== item.quantity;
			})
			.map((item) => ({
				item_id: item.id,
				quantity: item.quantity,
			}));

		// Send all changes sequentially using fetch
		const csrfToken = document
			.querySelector('meta[name="csrf-token"]')
			?.getAttribute('content') || '';

		const errors: string[] = [];
		for (const change of changes) {
			try {
				// Build URL manually to avoid route helper issues
				const url = `/admin/users/${selectedUser.value!.id}/items`;
				
				if (!csrfToken) {
					throw new Error('CSRF token not found');
				}

				console.log('Sending update request:', {
					url,
					change,
					csrfToken: csrfToken.substring(0, 10) + '...',
				});

				const response = await fetch(url, {
					method: 'PUT',
					headers: {
						'Content-Type': 'application/json',
						'X-CSRF-TOKEN': csrfToken,
						Accept: 'application/json',
						'X-Requested-With': 'XMLHttpRequest',
					},
					body: JSON.stringify(change),
					credentials: 'same-origin',
				});

				console.log('Response received:', {
					status: response.status,
					statusText: response.statusText,
					ok: response.ok,
				});

				if (!response.ok) {
					let errorMessage = `Failed to update item ${change.item_id}`;
					try {
						const errorData = await response.json();
						if (errorData.message) {
							errorMessage = errorData.message;
						} else if (errorData.errors) {
							const errorMessages = Object.values(errorData.errors)
								.flat()
								.join(', ');
							if (errorMessages) {
								errorMessage = errorMessages;
							}
						}
					} catch {
						errorMessage = `${response.status} ${response.statusText}`;
					}
					errors.push(errorMessage);
					console.error('Update failed:', {
						itemId: change.item_id,
						quantity: change.quantity,
						status: response.status,
						error: errorMessage,
						url: url,
					});
				}
			} catch (error) {
				console.error('Error updating item:', error);
				errors.push(
					`Network error updating item ${change.item_id}: ${
						error instanceof Error ? error.message : 'Unknown error'
					}`,
				);
			}
		}

		if (errors.length > 0) {
			throw new Error(errors.join('\n'));
		}

		// Update original inventory to match current state after successful save
		originalInventory.value = JSON.parse(
			JSON.stringify(userInventory.value),
		);
	} catch (error) {
		console.error('Error saving changes:', error);
		const errorMessage =
			error instanceof Error ? error.message : 'Failed to save changes. Please try again.';
		alert(errorMessage);
	} finally {
		isSaving.value = false;
	}
};


const removeItemFromUser = (itemId: number): void => {
	updateLocalItemQuantity(itemId, 0);
};

const reduceItemQuantity = (
	itemId: number,
	currentQuantity: number,
): void => {
	const newQuantity = Math.max(0, currentQuantity - 1);
	updateLocalItemQuantity(itemId, newQuantity);
};

const increaseItemQuantity = (
	itemId: number,
	currentQuantity: number,
	maxCount: number,
): void => {
	const newQuantity = Math.min(maxCount, currentQuantity + 1);
	updateLocalItemQuantity(itemId, newQuantity);
};

const addItemToUser = (
	itemId: number,
	quantity: number,
): void => {
	if (!selectedUser.value || !itemId || !quantity) {
		return;
	}

	// Find if item already exists in inventory
	const existingItem = userInventory.value.find(
		(item) => item.id === itemId,
	);

	// Find the max_count for this item
	const itemMaxCount = activeItems.value.find(
		(item) => item.id === itemId,
	)?.max_count || 999;

	// Calculate new quantity: add to existing or use new quantity, capped at max_count
	const currentQuantity = existingItem?.quantity || 0;
	const newQuantity = Math.min(
		currentQuantity + quantity,
		itemMaxCount,
	);

	updateLocalItemQuantity(itemId, newQuantity);
	// Reset form
	newItemId.value = 0;
	newItemQuantity.value = 1;
};

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
	<!-- Manage User Items Card -->
	<Card class="mb-6">
		<CardHeader>
			<CardTitle>Manage User Items</CardTitle>
		</CardHeader>
		<CardContent>
			<div class="space-y-4">
				<!-- User Search -->
				<div class="relative">
					<Label for="user-search">Search User</Label>
					<Input
						id="user-search"
						v-model="userSearchQuery"
						type="text"
						placeholder="Type user name..."
						class="mt-1 w-full"
						@focus="
							userSuggestions.length > 0 &&
								userSearchQuery.length >= 2
								? (showSuggestions = true)
								: null
						"
						@blur="handleInputBlur"
						@keydown="handleInputKeydown" />
					<!-- Suggestions Dropdown -->
					<div
						v-if="showSuggestions && userSuggestions.length > 0"
						class="absolute z-10 mt-1 w-full rounded-md border border-gray-200 bg-white shadow-lg">
						<div
							v-for="user in userSuggestions"
							:key="user.id"
							class="cursor-pointer px-4 py-2 hover:bg-gray-100"
							@mousedown.prevent
							@click="handleSuggestionClick(user)">
							<div class="font-medium">{{ user.name }}</div>
						</div>
					</div>
				</div>

				<!-- Selected User Info -->
				<div
					v-if="selectedUser"
					class="ml-2 mt-4 mb-2 font-semibold text-3xl">
					{{ selectedUser.name }}'s Inventory
				</div>

				<!-- User Inventory -->
				<div v-if="selectedUser">
					<div
						v-if="isLoadingInventory"
						class="py-4 text-center text-gray-500">
						Loading inventory...
					</div>
					<div
						v-else
						class="overflow-x-auto">
						<table class="w-full border-collapse">
							<thead>
								<tr class="border-b border-gray-200">
									<th class="text-cape-palliser-950 px-4 py-3 text-left text-sm font-semibold">
										Item Name
									</th>
									<th class="text-cape-palliser-950 px-4 py-3 text-left text-sm font-semibold">
										Description
									</th>
									<th class="text-cape-palliser-950 px-4 py-3 text-left text-sm font-semibold">
										Quantity
									</th>
									<th class="text-cape-palliser-950 px-4 py-3 text-left text-sm font-semibold">
										Max Count
									</th>
									<th class="text-cape-palliser-950 px-4 py-3 text-left text-sm font-semibold">
										Actions
									</th>
								</tr>
							</thead>
							<tbody>
								<tr
									v-for="item in userInventory"
									:key="item.id"
									class="border-b border-gray-200 hover:bg-gray-50">
									<td class="text-cape-palliser-950 px-4 py-3 text-sm font-medium">
										{{ item.name }}
									</td>
									<td class="text-cape-palliser-700 px-4 py-3 text-sm">
										{{ item.description || '—' }}
									</td>
									<td class="text-cape-palliser-700 px-4 py-3 text-sm">
										{{ item.quantity }}
									</td>
									<td class="text-cape-palliser-700 px-4 py-3 text-sm">
										{{ item.max_count }}
									</td>
									<td class="px-4 py-3 text-sm">
										<div class="flex items-center gap-2">
											<Button
												variant="outline"
												size="sm"
												:disabled="
													item.quantity <= 0 ||
													isSaving
												"
												@click="
													reduceItemQuantity(
														item.id,
														item.quantity,
													)
												">
												-
											</Button>
											<Button
												variant="outline"
												size="sm"
												:disabled="
													item.quantity >=
														item.max_count ||
													isSaving
												"
												@click="
													increaseItemQuantity(
														item.id,
														item.quantity,
														item.max_count,
													)
												">
												+
											</Button>
											<Button
												variant="destructive"
												size="sm"
												:disabled="
													item.quantity <= 0 ||
													isSaving
												"
												@click="removeItemFromUser(item.id)">
												Remove
											</Button>
										</div>
									</td>
								</tr>
							</tbody>
						</table>
					</div>

					<!-- Add Item Form -->
					<div class="mt-4 rounded border border-gray-200 p-4">
						<Label class="mb-2 block">Add Item</Label>
						<div class="flex gap-2">
							<select
								v-model.number="newItemId"
								class="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 flex flex-1 min-w-0 rounded-md border bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50 md:text-sm">
								<option :value="0">Select an item...</option>
								<option
									v-for="item in activeItems"
									:key="item.id"
									:value="item.id">
									{{ item.name }} (max:
									{{ item.max_count }})
								</option>
							</select>
							<Input
								v-model.number="newItemQuantity"
								type="number"
								min="1"
								:max="
									activeItems.find(
										(i) => i.id === newItemId,
									)?.max_count || 999
								"
								placeholder="Qty"
								class="w-24" />
							<Button
								:disabled="
									!newItemId ||
									!newItemQuantity ||
									isSaving
								"
								@click="
									addItemToUser(
										newItemId,
										newItemQuantity || 1,
									)
								">
								Add
							</Button>
						</div>
					</div>

					<!-- Save/Reset Controls -->
					<div
						v-if="selectedUser"
						class="mt-4 flex items-center justify-between rounded border border-gray-200 bg-gray-50 p-4">
						<div>
							<div
								v-if="hasUnsavedChanges"
								class="text-sm text-amber-600">
								You have unsaved changes
							</div>
							<div
								v-else
								class="text-sm text-gray-500">
								No unsaved changes
							</div>
						</div>
						<div class="flex items-center gap-2">
							<Button
								v-if="hasUnsavedChanges"
								variant="outline"
								size="sm"
								:disabled="isSaving"
								@click="resetToOriginal">
								Reset
							</Button>
							<Button
								variant="default"
								size="sm"
								:disabled="!hasUnsavedChanges || isSaving"
								@click="saveChanges">
								{{ isSaving ? 'Saving...' : 'Save Changes' }}
							</Button>
						</div>
					</div>
				</div>
				<div
					v-if="isLoadingInventory"
					class="py-4 text-center text-gray-500">
					Loading inventory...
				</div>
			</div>
		</CardContent>
	</Card>

	<!-- Items Table Card -->
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
