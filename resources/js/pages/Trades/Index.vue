<script setup lang="ts">
import SearchSelect from '@/components/SearchSelect.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

interface Recipient {
	id: number;
	name: string;
}

interface InventoryItem {
	id: number;
	name: string;
	quantity: number;
	max_count: number;
}

interface TradeLine {
	item_id: number;
	name: string | null;
	quantity: number;
}

interface TradeRow {
	id: number;
	status: 'pending' | 'accepted' | 'declined' | 'cancelled';
	note: string | null;
	direction: 'incoming' | 'outgoing';
	from_user: { id: number; name: string | null };
	to_user: { id: number; name: string | null };
	created_at: string | null;
	resolved_at: string | null;
	items: TradeLine[];
}

const props = withDefaults(
	defineProps<{
		trades?: { data: TradeRow[] };
		inventory?: InventoryItem[];
		recipients?: Recipient[];
		pendingIncoming?: number;
	}>(),
	{
		trades: () => ({ data: [] }),
		inventory: () => [],
		recipients: () => [],
		pendingIncoming: 0,
	},
);

const page = usePage();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Trades', href: '/trades' }];

const offerForm = useForm({
	to_user_id: null as number | null,
	note: '',
	items: [] as { item_id: number; quantity: number }[],
});

/** Draft quantities keyed by item id. Only non-zero lines are sent. */
const draft = reactive<Record<number, number>>({});

const selectedLines = computed(() =>
	props.inventory
		.map((item) => ({
			item_id: item.id,
			quantity: Number(draft[item.id] ?? 0),
		}))
		.filter((line) => line.quantity > 0),
);

const recipientOptions = computed(() =>
	props.recipients.map((recipient) => ({
		value: recipient.id,
		label: recipient.name,
	})),
);

const canSubmit = computed(
	() =>
		Boolean(offerForm.to_user_id) &&
		selectedLines.value.length > 0 &&
		!offerForm.processing,
);

const submitOffer = (): void => {
	offerForm
		.transform((data) => ({ ...data, items: selectedLines.value }))
		.post(route('trades.store'), {
			preserveScroll: true,
			onSuccess: () => {
				offerForm.reset('to_user_id', 'note');
				Object.keys(draft).forEach(
					(key) => delete draft[Number(key)],
				);
			},
		});
};

const act = (action: 'accept' | 'decline' | 'cancel', id: number): void => {
	router.post(route(`trades.${action}`, id), {}, { preserveScroll: true });
};

const statusLabel = (status: TradeRow['status']): string =>
	status.charAt(0).toUpperCase() + status.slice(1);

const formatDate = (value: string | null): string =>
	value ? new Date(value).toLocaleDateString() : '';
</script>

<template>
	<Head title="Trades" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="space-y-6 p-6">
			<div>
				<h1 class="text-cape-palliser-950 text-3xl font-bold">
					Trades
				</h1>
				<p class="text-cape-palliser-700 mt-2">
					Offer items from your inventory to another player.
					Nothing leaves your inventory until they accept.
				</p>
			</div>

			<div
				v-if="(page.props.flash as any)?.success"
				class="rounded-lg bg-green-50 p-4">
				<p class="text-sm font-medium text-green-800">
					{{ (page.props.flash as any)?.success }}
				</p>
			</div>

			<p
				v-if="offerForm.errors.trade || offerForm.errors.items"
				class="text-sm text-red-500">
				{{ offerForm.errors.trade || offerForm.errors.items }}
			</p>

			<Card>
				<CardHeader>
					<CardTitle>Offer Items</CardTitle>
				</CardHeader>
				<CardContent class="space-y-4">
					<p
						v-if="props.inventory.length === 0"
						class="text-cape-palliser-700 text-sm">
						You have no items to trade yet.
					</p>

					<template v-else>
						<div>
							<Label for="to_user_id">Recipient</Label>
							<SearchSelect
								id="to_user_id"
								v-model="offerForm.to_user_id"
								:options="recipientOptions"
								placeholder="Search players by name…"
								class="mt-1" />
							<p
								v-if="offerForm.errors.to_user_id"
								class="mt-1 text-sm text-red-500">
								{{ offerForm.errors.to_user_id }}
							</p>
						</div>

						<div class="space-y-2">
							<Label>Items to send</Label>
							<div
								v-for="item in props.inventory"
								:key="item.id"
								class="flex items-center justify-between gap-3">
								<span class="text-sm">
									{{ item.name }}
									<span
										class="text-cape-palliser-700">
										(you have {{ item.quantity }})
									</span>
								</span>
								<Input
									v-model.number="draft[item.id]"
									type="number"
									min="0"
									:max="item.quantity"
									class="w-24"
									:aria-label="`Quantity of ${item.name} to send`" />
							</div>
						</div>

						<div>
							<Label for="note">Note (optional)</Label>
							<Input
								id="note"
								v-model="offerForm.note"
								class="mt-1" />
						</div>

						<Button
							:disabled="!canSubmit"
							@click="submitOffer">
							Offer Trade
						</Button>
					</template>
				</CardContent>
			</Card>

			<Card>
				<CardHeader>
					<CardTitle>
						Trade History
						<span
							v-if="props.pendingIncoming > 0"
							class="text-shakespeare-600 text-base font-normal">
							— {{ props.pendingIncoming }} waiting on you
						</span>
					</CardTitle>
				</CardHeader>
				<CardContent class="space-y-4">
					<p
						v-if="props.trades.data.length === 0"
						class="text-cape-palliser-700 text-sm">
						No trades yet.
					</p>

					<div
						v-for="trade in props.trades.data"
						:key="trade.id"
						class="border-shakespeare-200 space-y-2 rounded-lg border p-4">
						<div
							class="flex flex-wrap items-center justify-between gap-2">
							<span class="font-semibold">
								{{
									trade.direction === 'outgoing'
										? `To ${trade.to_user.name ?? 'unknown'}`
										: `From ${trade.from_user.name ?? 'unknown'}`
								}}
							</span>
							<span
								class="text-shakespeare-700 text-sm font-semibold">
								{{ statusLabel(trade.status) }}
								<span
									class="text-cape-palliser-700 font-normal">
									{{
										formatDate(
											trade.resolved_at ??
												trade.created_at,
										)
									}}
								</span>
							</span>
						</div>

						<ul class="text-sm">
							<li
								v-for="line in trade.items"
								:key="line.item_id">
								{{ line.quantity }} ×
								{{ line.name ?? 'Unknown item' }}
							</li>
						</ul>

						<p
							v-if="trade.note"
							class="text-cape-palliser-700 text-sm italic">
							{{ trade.note }}
						</p>

						<div
							v-if="trade.status === 'pending'"
							class="flex flex-wrap gap-2">
							<template
								v-if="trade.direction === 'incoming'">
								<Button
									size="sm"
									@click="act('accept', trade.id)">
									Accept
								</Button>
								<Button
									size="sm"
									variant="outline"
									@click="act('decline', trade.id)">
									Decline
								</Button>
							</template>
							<Button
								v-else
								size="sm"
								variant="outline"
								@click="act('cancel', trade.id)">
								Cancel
							</Button>
						</div>
					</div>
				</CardContent>
			</Card>
		</div>
	</AppLayout>
</template>
