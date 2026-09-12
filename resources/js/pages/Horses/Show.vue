<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Herd {
	id: number;
	name: string;
}

interface User {
	id: number;
	name: string;
}

interface Horse {
	id: number;
	name: string;
	sex?: string | null;
	age_years: number;
	age_months_part: number;
	formatted_age: string;
	geno: string;
	design_link?: string;
	owner_id: number;
	herd_id?: number;
	bloodline: number[];
	progeny: number[];
	stats: any[];
	inventory: any[];
	equipment: any[];
	created_at: string;
	updated_at: string;
	owner: User;
	bred_by: User;
	herd?: Herd;
}

interface EquippedItem {
	uid: string;
	item_id: number;
	name: string;
	uses_remaining: number;
	uses_per_unit: number;
}

interface EquippableItem {
	id: number;
	name: string;
	quantity: number;
	max_count: number;
	uses_per_unit: number;
}

interface PendingTransfer {
	id: number;
	to_user_name: string;
	notes: string | null;
	created_at: string;
}

interface TransferRecipient {
	id: number;
	name: string;
}

interface Props {
	horse: Horse;
	equipment: EquippedItem[];
	equippableItems: EquippableItem[];
	can: {
		update: boolean;
		delete: boolean;
		transfer?: boolean;
	};
	pendingTransfer?: PendingTransfer | null;
	transferRecipients?: TransferRecipient[];
}

const props = withDefaults(defineProps<Props>(), {
	pendingTransfer: null,
	transferRecipients: () => [],
});

const page = usePage();

const equipForm = useForm({
	item_id: null as number | null,
});

const equippableOptions = computed(() =>
	props.equippableItems.map((item) => ({
		value: item.id,
		label: `${item.name} (x${item.quantity})`,
	})),
);

const submitEquip = (): void => {
	equipForm.post(route('horses.equipment.store', props.horse.id), {
		preserveScroll: true,
		onSuccess: () => equipForm.reset('item_id'),
	});
};

const useEquipment = (uid: string): void => {
	router.post(
		route('horses.equipment.use', [props.horse.id, uid]),
		{},
		{ preserveScroll: true },
	);
};

const returnEquipment = (uid: string): void => {
	router.delete(route('horses.equipment.destroy', [props.horse.id, uid]), {
		preserveScroll: true,
	});
};

const transferForm = useForm({
	to_user_id: null as number | null,
	notes: '',
});

const transferRecipientOptions = computed(() =>
	props.transferRecipients.map((recipient) => ({
		value: recipient.id,
		label: recipient.name,
	})),
);

const showTransferCard = computed(
	() => Boolean(props.can.transfer) || props.pendingTransfer !== null,
);

const formatTransferDate = (value: string): string =>
	new Date(value).toLocaleDateString();

const submitTransfer = (): void => {
	transferForm.post(route('horse-transfers.store', props.horse.id), {
		preserveScroll: true,
		onSuccess: () => transferForm.reset(),
	});
};

const cancelTransfer = (): void => {
	if (!props.pendingTransfer) {
		return;
	}

	router.post(
		route('horse-transfers.cancel', props.pendingTransfer.id),
		{},
		{ preserveScroll: true },
	);
};

const breadcrumbs: BreadcrumbItem[] = [
	{
		title: 'Horses',
		href: '/horses',
	},
	{
		title: props.horse.name,
		href: `/horses/${props.horse.id}`,
	},
];

const deleteHorse = () => {
	if (
		confirm(
			'Are you sure you want to delete this horse? This action cannot be undone.',
		)
	) {
		router.delete(route('horses.destroy', props.horse.id));
	}
};
</script>

<template>
	<Head :title="props.horse.name" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="space-y-6">
			<div class="flex items-center justify-between">
				<div>
					<h1 class="text-3xl font-bold">
						{{ props.horse.name }}
					</h1>
					<p class="text-gray-600">
						Owned by {{ props.horse.owner.name }}
					</p>
				</div>
				<div
					v-if="props.can.update || props.can.delete"
					class="flex gap-2">
					<Link
						v-if="props.can.update"
						:href="route('horses.edit', props.horse.id)">
						<Button variant="outline">Edit Horse</Button>
					</Link>
					<Button
						v-if="props.can.delete"
						variant="destructive"
						@click="deleteHorse"
						>Delete Horse</Button
					>
				</div>
			</div>

			<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
				<!-- Horse Information -->
				<Card class="lg:col-span-2">
					<CardHeader>
						<CardTitle>Horse Information</CardTitle>
					</CardHeader>
					<CardContent class="space-y-4">
						<div
							v-if="props.horse.design_link"
							class="mb-4">
							<img
								:src="props.horse.design_link"
								:alt="props.horse.name"
								class="h-64 w-full max-w-md rounded object-cover" />
						</div>

						<div class="grid grid-cols-2 gap-4">
							<div>
								<h3 class="font-semibold">
									Basic Details
								</h3>
								<p>
									<strong>Name:</strong>
									{{ props.horse.name }}
								</p>
								<p>
									<strong>Sex:</strong>
									{{ props.horse.sex ?? 'Unset' }}
								</p>
								<p>
									<strong>Age:</strong>
									{{ props.horse.formatted_age }}
								</p>
								<p>
									<strong>Geno:</strong>
									{{ props.horse.geno }}
								</p>
								<p>
									<strong>Owner:</strong>
									{{ props.horse.owner.name }}
								</p>
								<p>
									<strong>Bred by:</strong>
									{{ props.horse.bred_by.name }}
								</p>
								<p v-if="props.horse.herd">
									<strong>Herd:</strong>
									{{ props.horse.herd.name }}
								</p>
							</div>

							<div>
								<h3 class="font-semibold">
									Bloodline & Progeny
								</h3>
								<p>
									<strong>Bloodline:</strong>
									{{ props.horse.bloodline.length }}
									ancestors
								</p>
								<p>
									<strong>Progeny:</strong>
									{{ props.horse.progeny.length }}
									offspring
								</p>
								<p>
									<strong>Created:</strong>
									{{
										new Date(
											props.horse.created_at,
										).toLocaleDateString()
									}}
								</p>
							</div>
						</div>

						<div
							v-if="
								props.horse.stats &&
								props.horse.stats.length > 0
							">
							<h3 class="font-semibold">Stats</h3>
							<div class="grid grid-cols-2 gap-2">
								<div
									v-for="(stat, key) in props.horse
										.stats"
									:key="key"
									class="flex justify-between">
									<span class="capitalize" />
									<span>{{ stat }}</span>
								</div>
							</div>
						</div>

						<div
							v-if="
								props.horse.inventory &&
								props.horse.inventory.length > 0
							">
							<h3 class="font-semibold">Inventory</h3>
							<p class="text-gray-600">
								{{ props.horse.inventory.length }} items
							</p>
						</div>
					</CardContent>
				</Card>

				<!-- Equipment -->
				<Card class="lg:col-span-2">
					<CardHeader>
						<CardTitle>Equipment</CardTitle>
					</CardHeader>
					<CardContent class="space-y-4">
						<div
							v-if="(page.props.flash as any)?.success"
							class="rounded-lg bg-green-50 p-4">
							<p
								class="text-sm font-medium text-green-800">
								{{ (page.props.flash as any)?.success }}
							</p>
						</div>

						<p
							v-if="equipForm.errors.item_id"
							class="text-sm text-red-500">
							{{ equipForm.errors.item_id }}
						</p>

						<p
							v-if="props.equipment.length === 0"
							class="text-gray-600">
							Nothing equipped.
						</p>

						<div
							v-for="e in props.equipment"
							:key="e.uid"
							class="flex items-center justify-between gap-3 border-b pb-2 last:border-b-0">
							<span class="text-sm">
								{{ e.name }}
								<span
									v-if="e.uses_per_unit > 1"
									class="text-gray-600">
									{{ e.uses_remaining }} /
									{{ e.uses_per_unit }} uses
								</span>
							</span>
							<div
								v-if="props.can.update"
								class="flex gap-2">
								<Button
									v-if="e.uses_per_unit > 1"
									size="sm"
									variant="outline"
									@click="useEquipment(e.uid)">
									Use
								</Button>
								<Button
									size="sm"
									variant="outline"
									:disabled="
										e.uses_remaining <
										e.uses_per_unit
									"
									:title="
										e.uses_remaining <
										e.uses_per_unit
											? 'Partially used gear cannot be returned to inventory.'
											: undefined
									"
									@click="returnEquipment(e.uid)">
									Return to inventory
								</Button>
							</div>
						</div>

						<div
							v-if="props.can.update"
							class="pt-2">
							<p
								v-if="
									props.equippableItems.length === 0
								"
								class="text-sm text-gray-600">
								No items in your inventory.
							</p>
							<div
								v-else
								class="flex items-center gap-2">
								<Select
									v-model="equipForm.item_id"
									:options="equippableOptions"
									placeholder="Select an item"
									class="w-64" />
								<Button
									:disabled="
										!equipForm.item_id ||
										equipForm.processing
									"
									@click="submitEquip">
									Equip
								</Button>
							</div>
						</div>
					</CardContent>
				</Card>

				<!-- Transfer -->
				<Card
					v-if="showTransferCard"
					class="lg:col-span-2">
					<CardHeader>
						<CardTitle>Transfer</CardTitle>
					</CardHeader>
					<CardContent class="space-y-4">
						<div v-if="props.pendingTransfer">
							<p class="text-sm">
								Pending transfer to
								<strong>{{
									props.pendingTransfer.to_user_name
								}}</strong>
								, offered
								{{
									formatTransferDate(
										props.pendingTransfer
											.created_at,
									)
								}}.
							</p>
							<p
								v-if="props.pendingTransfer.notes"
								class="mt-1 text-sm text-gray-600">
								{{ props.pendingTransfer.notes }}
							</p>
							<p class="mt-1 text-sm text-gray-600">
								Staff must approve this before ownership
								changes.
							</p>
							<Button
								variant="outline"
								size="sm"
								class="mt-3"
								@click="cancelTransfer">
								Cancel transfer
							</Button>
						</div>

						<div
							v-else-if="props.can.transfer"
							class="space-y-3">
							<p class="text-sm text-gray-600">
								Offer this horse to another player.
								Staff review the request before
								ownership changes.
							</p>

							<p
								v-if="transferForm.errors.to_user_id"
								class="text-sm text-red-500">
								{{ transferForm.errors.to_user_id }}
							</p>
							<p
								v-if="transferForm.errors.notes"
								class="text-sm text-red-500">
								{{ transferForm.errors.notes }}
							</p>

							<p
								v-if="
									props.transferRecipients.length ===
									0
								"
								class="text-sm text-gray-600">
								No players are available to receive this
								horse.
							</p>
							<template v-else>
								<div>
									<Label for="transfer-recipient"
										>Recipient</Label
									>
									<Select
										id="transfer-recipient"
										v-model="
											transferForm.to_user_id
										"
										:options="
											transferRecipientOptions
										"
										placeholder="Select a player"
										class="mt-1 w-64" />
								</div>
								<div>
									<Label for="transfer-notes"
										>Note (optional)</Label
									>
									<textarea
										id="transfer-notes"
										v-model="transferForm.notes"
										rows="3"
										class="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 mt-1 flex w-full min-w-0 rounded-md border bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
										placeholder="Why are you transferring this horse?" />
								</div>
								<Button
									:disabled="
										!transferForm.to_user_id ||
										transferForm.processing
									"
									@click="submitTransfer">
									Transfer this horse
								</Button>
							</template>
						</div>
					</CardContent>
				</Card>

				<!-- Quick Actions -->
				<Card>
					<CardHeader>
						<CardTitle>Quick Actions</CardTitle>
					</CardHeader>
					<CardContent class="space-y-2">
						<Link
							v-if="props.can.update"
							:href="route('horses.edit', props.horse.id)"
							class="block">
							<Button class="w-full">Edit Horse</Button>
						</Link>
						<Link
							v-if="props.horse.herd"
							:href="
								route('herds.show', props.horse.herd.id)
							"
							class="block">
							<Button
								variant="outline"
								class="w-full"
								>View Herd</Button
							>
						</Link>
						<!-- <Link
							:href="
								route(
									'users.horses',
									props.horse.owner.id,
								)
							"
							class="block">
							<Button
								variant="outline"
								class="w-full"
								>View {{ props.horse.owner.name }}'s
								Horses</Button
							>
						</Link>
						<Link
							:href="
								route(
									'users.herds',
									props.horse.owner.id,
								)
							"
							class="block">
							<Button
								variant="outline"
								class="w-full"
								>View {{ props.horse.owner.name }}'s
								Herds</Button
							>
						</Link> -->
					</CardContent>
				</Card>
			</div>
		</div>
	</AppLayout>
</template>
