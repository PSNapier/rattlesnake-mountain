<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
	Dialog,
	DialogContent,
	DialogFooter,
	DialogHeader,
	DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface Recipient {
	id: number;
	name: string;
	is_sanctuary: boolean;
}

interface HorseSearchResult {
	id: number;
	name: string;
	owner_id: number;
	owner_name: string;
	state: string;
	is_dead: boolean;
}

interface Props {
	recipients?: Recipient[];
}

const props = withDefaults(defineProps<Props>(), {
	recipients: () => [],
});

const searchQuery = ref('');
const searchResults = ref<HorseSearchResult[]>([]);
const isSearching = ref(false);
const selectedHorse = ref<HorseSearchResult | null>(null);
const recipientId = ref<number | null>(null);
const reason = ref('');
const isSubmitting = ref(false);
const showSanctuaryDialog = ref(false);

const recipientOptions = computed(() =>
	props.recipients.map((recipient) => ({
		value: recipient.id,
		label: recipient.is_sanctuary
			? `${recipient.name} (Sanctuary)`
			: recipient.name,
	})),
);

const selectedRecipient = computed(
	() =>
		props.recipients.find(
			(recipient) => recipient.id === recipientId.value,
		) ?? null,
);

const isSanctuaryRecipient = computed(
	() => selectedRecipient.value?.is_sanctuary === true,
);

const canSubmit = computed(
	() =>
		selectedHorse.value !== null &&
		recipientId.value !== null &&
		reason.value.trim().length > 0 &&
		!isSubmitting.value,
);

// Search horses with debounce
let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(searchQuery, (query) => {
	if (searchTimeout) {
		clearTimeout(searchTimeout);
	}

	if (selectedHorse.value && query === selectedHorse.value.name) {
		searchResults.value = [];
		return;
	}

	if (query.trim().length < 2) {
		searchResults.value = [];
		isSearching.value = false;
		return;
	}

	isSearching.value = true;

	searchTimeout = setTimeout(async () => {
		try {
			const response = await fetch(
				route('admin.horses.search', { q: query }),
			);
			searchResults.value = await response.json();
		} catch (error) {
			console.error('Error searching horses:', error);
			searchResults.value = [];
		} finally {
			isSearching.value = false;
		}
	}, 300);
});

const selectHorse = (horse: HorseSearchResult): void => {
	if (searchTimeout) {
		clearTimeout(searchTimeout);
		searchTimeout = null;
	}

	selectedHorse.value = horse;
	searchQuery.value = horse.name;
	searchResults.value = [];
	isSearching.value = false;
};

const clearHorse = (): void => {
	selectedHorse.value = null;
	searchQuery.value = '';
	searchResults.value = [];
};

const postTransfer = (): void => {
	if (!selectedHorse.value || recipientId.value === null) {
		return;
	}

	isSubmitting.value = true;

	router.post(
		route('admin.horses.transfer', selectedHorse.value.id),
		{ to_user_id: recipientId.value, reason: reason.value },
		{
			preserveScroll: true,
			onSuccess: () => {
				selectedHorse.value = null;
				searchQuery.value = '';
				searchResults.value = [];
				recipientId.value = null;
				reason.value = '';
			},
			onFinish: () => {
				isSubmitting.value = false;
			},
		},
	);
};

const submitTransfer = (): void => {
	if (!canSubmit.value) {
		return;
	}

	if (isSanctuaryRecipient.value) {
		showSanctuaryDialog.value = true;
		return;
	}

	postTransfer();
};

const confirmSanctuaryTransfer = (): void => {
	showSanctuaryDialog.value = false;
	postTransfer();
};
</script>

<template>
	<div class="space-y-6">
		<Card>
			<CardHeader>
				<CardTitle>Transfer a horse</CardTitle>
				<p class="text-cape-palliser-600 mt-1 text-sm">
					Move any horse to any owner immediately. This skips the
					submissions queue, so a reason is required.
				</p>
			</CardHeader>
			<CardContent class="space-y-4">
				<!-- Horse search -->
				<div class="relative">
					<Label for="horse-search">Horse</Label>
					<div class="mt-1 flex gap-2">
						<Input
							id="horse-search"
							v-model="searchQuery"
							type="text"
							placeholder="Type a horse name..."
							class="w-full max-w-sm" />
						<Button
							v-if="selectedHorse"
							variant="outline"
							@click="clearHorse">
							Clear
						</Button>
					</div>

					<p
						v-if="isSearching"
						class="text-cape-palliser-500 mt-1 text-xs">
						Searching…
					</p>

					<div
						v-if="searchResults.length > 0"
						class="absolute z-10 mt-1 w-full max-w-sm rounded-md border border-gray-200 bg-white shadow-lg">
						<div
							v-for="horse in searchResults"
							:key="horse.id"
							class="cursor-pointer px-4 py-2 hover:bg-gray-100"
							@mousedown.prevent
							@click="selectHorse(horse)">
							<div
								class="flex items-center gap-2 font-medium">
								{{ horse.name }}
								<span
									v-if="horse.is_dead"
									class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">
									Dead
								</span>
								<span
									v-if="horse.state !== 'public'"
									class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-800">
									Unapproved
								</span>
							</div>
							<div class="text-cape-palliser-600 text-xs">
								Owned by {{ horse.owner_name }}
							</div>
						</div>
					</div>
				</div>

				<!-- Selected horse -->
				<div
					v-if="selectedHorse"
					class="border-shakespeare-200 bg-shakespeare-50/30 rounded-md border p-4">
					<p class="text-cape-palliser-700 text-sm font-medium">
						{{ selectedHorse.name }}
					</p>
					<p class="text-cape-palliser-600 mt-0.5 text-sm">
						Currently owned by
						{{ selectedHorse.owner_name }}
					</p>
					<div
						v-if="selectedHorse.is_dead"
						class="mt-2">
						<span
							class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">
							Dead
						</span>
					</div>
				</div>

				<!-- Recipient -->
				<div>
					<Label for="horse-transfer-recipient">New owner</Label>
					<Select
						id="horse-transfer-recipient"
						v-model="recipientId"
						:options="recipientOptions"
						placeholder="Select a player"
						class="mt-1 w-64" />
					<p
						v-if="isSanctuaryRecipient"
						class="mt-1 text-xs text-amber-700">
						Transferring to Sanctuary turns this horse into an
						NPC that any player can claim.
					</p>
				</div>

				<!-- Reason -->
				<div>
					<Label for="horse-transfer-reason">Reason</Label>
					<textarea
						id="horse-transfer-reason"
						v-model="reason"
						rows="3"
						class="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 mt-1 flex w-full max-w-xl min-w-0 rounded-md border bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
						placeholder="Why is this horse being moved?" />
				</div>

				<Button
					:disabled="!canSubmit"
					@click="submitTransfer">
					Transfer horse
				</Button>
			</CardContent>
		</Card>

		<Dialog v-model:open="showSanctuaryDialog">
			<DialogContent>
				<DialogHeader>
					<DialogTitle>Transfer to Sanctuary?</DialogTitle>
				</DialogHeader>
				<p class="text-sm text-gray-600">
					Moving
					<strong>{{ selectedHorse?.name }}</strong> to Sanctuary
					turns it into an NPC and makes it claimable by any
					player. This is not the same as a normal ownership
					change.
				</p>
				<DialogFooter>
					<Button
						variant="outline"
						@click="showSanctuaryDialog = false">
						Cancel
					</Button>
					<Button
						variant="destructive"
						@click="confirmSanctuaryTransfer">
						Confirm
					</Button>
				</DialogFooter>
			</DialogContent>
		</Dialog>
	</div>
</template>
