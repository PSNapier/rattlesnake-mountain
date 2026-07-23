<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import SearchSelect from '@/components/SearchSelect.vue';
import RequestCard from '@/pages/Breedings/RequestCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface HorseOption {
	id: number;
	name: string;
	sex: string;
	geno: string;
	age_months: number;
}

interface HorseSummary {
	id: number;
	name: string;
	sex?: string | null;
	geno?: string;
	age_months?: number;
	state?: string;
}

interface BreedingRequestRow {
	id: number;
	status: string;
	evidence_url: string;
	notes?: string | null;
	result_options?: { geno: string; phenotype: string }[] | null;
	selected_option_index?: number | null;
	sire?: HorseSummary | null;
	dam?: HorseSummary | null;
	foal?: HorseSummary | null;
	created_at: string;
}

interface Herd {
	id: number;
	name: string;
}

interface SlotRow {
	id: number;
	sequence: number;
	status: string;
	horse?: { id: number; name: string; sex?: string | null } | null;
}

interface TransferRow {
	id: number;
	notes?: string | null;
	slot?: { id: number; horse?: { id: number; name: string } | null } | null;
	from_user?: { id: number; name: string } | null;
	to_user?: { id: number; name: string } | null;
}

interface PaginatedRequests {
	data: BreedingRequestRow[];
	links: { url: string | null; label: string; active: boolean }[];
}

interface Props {
	requests: PaginatedRequests;
	herds: Herd[];
	slots: SlotRow[];
	incomingTransfers: TransferRow[];
	outgoingTransfers: TransferRow[];
	eligibleSires: HorseOption[];
	eligibleDams: HorseOption[];
	phenotypePlaceholder: string;
}

const props = defineProps<Props>();
const page = usePage<SharedData>();
const isStaff = computed(() => {
	const role = page.props.auth.user?.role;
	return !!role && role !== 'user';
});
const maxFileSize = computed(() => (isStaff.value ? 10 : 2) * 1024 * 1024);

const breadcrumbs: BreadcrumbItem[] = [
	{ title: 'Breeding', href: '/breedings' },
];

const sireOptions = computed(() =>
	props.eligibleSires.map((horse) => ({
		value: horse.id,
		label: `${horse.name} (${horse.geno})`,
	})),
);

const damOptions = computed(() =>
	props.eligibleDams.map((horse) => ({
		value: horse.id,
		label: `${horse.name} (${horse.geno})`,
	})),
);

interface SlotHorseGroup {
	horseId: number;
	horseName: string;
	slots: SlotRow[];
	availableCount: number;
	reservedCount: number;
}

const slotGroups = computed<SlotHorseGroup[]>(() => {
	const groups = new Map<number, SlotHorseGroup>();

	for (const slot of props.slots) {
		const horseId = slot.horse?.id ?? 0;
		const existing = groups.get(horseId);
		if (existing) {
			existing.slots.push(slot);
		} else {
			groups.set(horseId, {
				horseId,
				horseName: slot.horse?.name ?? 'Unknown horse',
				slots: [slot],
				availableCount: 0,
				reservedCount: 0,
			});
		}
	}

	for (const group of groups.values()) {
		group.availableCount = group.slots.filter((slot) => slot.status === 'available').length;
		group.reservedCount = group.slots.filter((slot) => slot.status !== 'available').length;
	}

	return Array.from(groups.values());
});

const slotsPerPage = 5;
const slotsPage = ref(1);

const slotGroupsTotalPages = computed(() =>
	Math.max(1, Math.ceil(slotGroups.value.length / slotsPerPage)),
);

const paginatedSlotGroups = computed(() => {
	const start = (slotsPage.value - 1) * slotsPerPage;
	return slotGroups.value.slice(start, start + slotsPerPage);
});

const slotPageNumbers = computed(() =>
	Array.from({ length: slotGroupsTotalPages.value }, (_, index) => index + 1),
);

watch(slotGroupsTotalPages, (totalPages) => {
	if (slotsPage.value > totalPages) {
		slotsPage.value = totalPages;
	}
});

const requestForm = useForm({
	sire_id: null as number | null,
	dam_id: null as number | null,
	evidence_url: '',
	notes: '',
});

const transferForm = useForm({
	breeding_slot_id: null as number | null,
	to_user_id: null as number | null,
	notes: '',
});

const submitRequest = (): void => {
	requestForm.post(route('breedings.store'), { preserveScroll: true });
};

const submitTransfer = (): void => {
	transferForm.post(route('breeding-slot-transfers.store'), {
		preserveScroll: true,
		onSuccess: () => transferForm.reset('to_user_id', 'notes'),
	});
};

const acceptTransfer = (id: number): void => {
	router.post(route('breeding-slot-transfers.accept', id), {}, { preserveScroll: true });
};

const declineTransfer = (id: number): void => {
	router.post(route('breeding-slot-transfers.decline', id), {}, { preserveScroll: true });
};

const cancelTransfer = (id: number): void => {
	router.post(route('breeding-slot-transfers.cancel', id), {}, { preserveScroll: true });
};
</script>

<template>
	<Head title="Breeding" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="space-y-6">
			<div class="flex items-center justify-between gap-4">
				<div>
					<h1 class="text-3xl font-bold">Breeding</h1>
					<p class="text-gray-600">
						Submit breeding requests, manage slots, and create foals from staff rolls.
					</p>
				</div>
				<Link :href="route('horses.index')">
					<Button variant="outline">My Horses</Button>
				</Link>
			</div>

			<Card>
				<CardHeader>
					<CardTitle>New Breeding Request</CardTitle>
				</CardHeader>
				<CardContent>
					<form
						class="space-y-4"
						@submit.prevent="submitRequest">
						<div class="grid gap-4 md:grid-cols-2">
							<div>
								<Label for="sire_id">Sire</Label>
								<SearchSelect
									id="sire_id"
									v-model="requestForm.sire_id"
									:options="sireOptions"
									placeholder="Search stallion…" />
								<p
									v-if="requestForm.errors.sire_id"
									class="mt-1 text-sm text-red-500">
									{{ requestForm.errors.sire_id }}
								</p>
							</div>
							<div>
								<Label for="dam_id">Dam</Label>
								<SearchSelect
									id="dam_id"
									v-model="requestForm.dam_id"
									:options="damOptions"
									placeholder="Search mare…" />
								<p
									v-if="requestForm.errors.dam_id"
									class="mt-1 text-sm text-red-500">
									{{ requestForm.errors.dam_id }}
								</p>
							</div>
						</div>
						<div>
							<Label for="evidence_url">Art / Story URL</Label>
							<Input
								id="evidence_url"
								v-model="requestForm.evidence_url"
								type="url"
								required
								placeholder="https://" />
							<p
								v-if="requestForm.errors.evidence_url"
								class="mt-1 text-sm text-red-500">
								{{ requestForm.errors.evidence_url }}
							</p>
						</div>
						<div>
							<Label for="notes">Notes (optional)</Label>
							<Input
								id="notes"
								v-model="requestForm.notes"
								type="text"
								placeholder="Optional notes for staff" />
						</div>
						<p
							v-if="requestForm.errors.breeding"
							class="text-sm text-red-500">
							{{ requestForm.errors.breeding }}
						</p>
						<Button
							type="submit"
							:disabled="requestForm.processing">
							{{ requestForm.processing ? 'Submitting…' : 'Submit Request' }}
						</Button>
					</form>
				</CardContent>
			</Card>

			<Card>
				<CardHeader>
					<CardTitle>My Requests</CardTitle>
				</CardHeader>
				<CardContent class="space-y-3">
					<div
						v-if="props.requests.data.length === 0"
						class="text-sm text-gray-500">
						No breeding requests yet.
					</div>
					<RequestCard
						v-for="item in props.requests.data"
						:key="item.id"
						:request="item"
						:herds="props.herds"
						:phenotype-placeholder="props.phenotypePlaceholder"
						:max-file-size="maxFileSize" />
				</CardContent>
			</Card>

			<Card>
				<CardHeader>
					<CardTitle>Slots I Hold</CardTitle>
				</CardHeader>
				<CardContent class="space-y-3">
					<div
						v-if="props.slots.length === 0"
						class="text-sm text-gray-500">
						No available or reserved slots.
					</div>
					<div
						v-for="group in paginatedSlotGroups"
						:key="group.horseId"
						class="flex items-center justify-between rounded border p-3 text-sm">
						<p class="font-medium">
							<Link
								:href="route('horses.show', group.horseId)"
								class="hover:underline">
								{{ group.horseName }}
							</Link>
							- {{ group.slots.length }}
							{{ group.slots.length === 1 ? 'slot' : 'slots' }}
						</p>
						<p
							v-if="group.reservedCount > 0"
							class="text-xs text-gray-500">
							{{ group.reservedCount }} reserved
						</p>
					</div>
					<div
						v-if="slotGroupsTotalPages > 1"
						class="flex flex-wrap items-center justify-center gap-2 pt-1">
						<button
							type="button"
							:disabled="slotsPage === 1"
							:class="[
								'rounded-md border px-3 py-1.5 text-sm',
								'border-cape-palliser-500 text-cape-palliser-700',
								slotsPage === 1 ? 'pointer-events-none opacity-50' : '',
							]"
							@click="slotsPage--">
							« Previous
						</button>
						<button
							v-for="page in slotPageNumbers"
							:key="page"
							type="button"
							:class="[
								'rounded-md border px-3 py-1.5 text-sm',
								page === slotsPage
									? 'border-cape-palliser-500 bg-cape-palliser-500 text-white'
									: 'border-cape-palliser-500 text-cape-palliser-700',
							]"
							@click="slotsPage = page">
							{{ page }}
						</button>
						<button
							type="button"
							:disabled="slotsPage === slotGroupsTotalPages"
							:class="[
								'rounded-md border px-3 py-1.5 text-sm',
								'border-cape-palliser-500 text-cape-palliser-700',
								slotsPage === slotGroupsTotalPages ? 'pointer-events-none opacity-50' : '',
							]"
							@click="slotsPage++">
							Next »
						</button>
					</div>

					<form
						class="space-y-3 border-t pt-4"
						@submit.prevent="submitTransfer">
						<p class="font-medium">Offer a slot transfer</p>
						<div>
							<Label for="breeding_slot_id">Slot</Label>
							<Select
								id="breeding_slot_id"
								v-model="transferForm.breeding_slot_id"
								:options="[
									{ value: null, label: 'Select available slot' },
									...props.slots
										.filter((slot) => slot.status === 'available')
										.map((slot) => ({
											value: slot.id,
											label: `${slot.horse?.name} #${slot.sequence}`,
										})),
								]" />
						</div>
						<div>
							<Label for="to_user_id">Recipient user ID</Label>
							<Input
								id="to_user_id"
								v-model.number="transferForm.to_user_id"
								type="number"
								min="1"
								required />
							<p
								v-if="transferForm.errors.to_user_id"
								class="mt-1 text-sm text-red-500">
								{{ transferForm.errors.to_user_id }}
							</p>
						</div>
						<p
							v-if="transferForm.errors.breeding_slot_id"
							class="text-sm text-red-500">
							{{ transferForm.errors.breeding_slot_id }}
						</p>
						<Button
							type="submit"
							size="sm"
							:disabled="transferForm.processing">
							Offer Transfer
						</Button>
					</form>
				</CardContent>
			</Card>

			<Card>
				<CardHeader>
					<CardTitle>Slot Offers</CardTitle>
				</CardHeader>
				<CardContent class="space-y-6">
					<div class="space-y-3">
						<p class="text-sm font-medium text-gray-700">Incoming</p>
						<div
							v-if="props.incomingTransfers.length === 0"
							class="text-sm text-gray-500">
							No incoming offers.
						</div>
						<div
							v-for="transfer in props.incomingTransfers"
							:key="`in-${transfer.id}`"
							class="flex items-center justify-between gap-2 rounded border p-3">
							<div class="text-sm">
								<p class="font-medium">
									{{ transfer.slot?.horse?.name }} from
									{{ transfer.from_user?.name }}
								</p>
							</div>
							<div class="flex gap-2">
								<Button
									size="sm"
									@click="acceptTransfer(transfer.id)">
									Accept
								</Button>
								<Button
									size="sm"
									variant="outline"
									@click="declineTransfer(transfer.id)">
									Decline
								</Button>
							</div>
						</div>
					</div>

					<div class="space-y-3 border-t pt-4">
						<p class="text-sm font-medium text-gray-700">Outgoing</p>
						<div
							v-if="props.outgoingTransfers.length === 0"
							class="text-sm text-gray-500">
							No outgoing offers.
						</div>
						<div
							v-for="transfer in props.outgoingTransfers"
							:key="`out-${transfer.id}`"
							class="flex items-center justify-between gap-2 rounded border p-3">
							<div class="text-sm">
								<p class="font-medium">
									{{ transfer.slot?.horse?.name }} to
									{{ transfer.to_user?.name }}
								</p>
							</div>
							<Button
								size="sm"
								variant="outline"
								@click="cancelTransfer(transfer.id)">
								Cancel
							</Button>
						</div>
					</div>
				</CardContent>
			</Card>
		</div>
	</AppLayout>
</template>
