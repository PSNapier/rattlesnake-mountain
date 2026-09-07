<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface Item {
	id: number;
	name: string;
	description?: string | null;
	quantity: number;
	max_count: number;
}

interface VoucherChoice {
	value: string;
	label: string;
}

interface VoucherOption {
	name: string;
	choices: VoucherChoice[];
}

interface Props {
	items: Item[];
	vouchers?: VoucherOption[];
	user?: {
		id: number;
		name: string;
	};
}

const props = withDefaults(defineProps<Props>(), {
	vouchers: () => [],
});

const isPublicView = computed(() => !!props.user);

const redeemableVouchers = computed(() =>
	props.vouchers.filter((voucher) => {
		const item = props.items.find((entry) => entry.name === voucher.name);
		return item && item.quantity > 0;
	}),
);

const selectedVoucherName = ref(redeemableVouchers.value[0]?.name ?? '');
const selectedChoice = ref(
	redeemableVouchers.value[0]?.choices[0]?.value ?? '',
);

watch(
	redeemableVouchers,
	(vouchers) => {
		if (
			!vouchers.find(
				(voucher) => voucher.name === selectedVoucherName.value,
			)
		) {
			selectedVoucherName.value = vouchers[0]?.name ?? '';
		}

		const current = vouchers.find(
			(voucher) => voucher.name === selectedVoucherName.value,
		);
		if (
			!current?.choices.find(
				(choice) => choice.value === selectedChoice.value,
			)
		) {
			selectedChoice.value = current?.choices[0]?.value ?? '';
		}
	},
	{ immediate: true },
);

const activeVoucher = computed(() =>
	redeemableVouchers.value.find(
		(voucher) => voucher.name === selectedVoucherName.value,
	),
);

const activeQuantity = computed(() => {
	const item = props.items.find(
		(entry) => entry.name === selectedVoucherName.value,
	);
	return item?.quantity ?? 0;
});

const redeemForm = useForm({
	voucher: '',
	choice: '',
});

function redeemVoucher() {
	if (!selectedVoucherName.value || !selectedChoice.value) {
		return;
	}

	redeemForm.voucher = selectedVoucherName.value;
	redeemForm.choice = selectedChoice.value;
	redeemForm.post(route('inventory.redeem-voucher'), {
		preserveScroll: true,
	});
}

const breadcrumbs: BreadcrumbItem[] = props.user
	? [
			{
				title: 'Users',
				href: '/',
			},
			{
				title: props.user.name,
				href: `/u/${props.user.id}`,
			},
			{
				title: 'Inventory',
				href: `/u/${props.user.id}/inventory`,
			},
		]
	: [
			{
				title: 'Inventory',
				href: '/inventory',
			},
		];
</script>

<template>
	<Head
		:title="
			isPublicView ? `${props.user?.name}'s Inventory` : 'My Inventory'
		" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="space-y-6">
			<div class="flex items-center justify-between">
				<h1 class="text-3xl font-bold">
					{{
						isPublicView
							? `${props.user?.name}'s Inventory`
							: 'My Inventory'
					}}
				</h1>
			</div>

			<div
				v-if="!isPublicView && redeemableVouchers.length > 0"
				class="border-cape-palliser-200 bg-cape-palliser-50 rounded-lg border p-4">
				<p class="text-cape-palliser-950 mb-3 text-sm font-medium">
					You have redeemable vouchers. Choose a voucher and item
					to redeem.
				</p>
				<div class="flex flex-wrap items-end gap-4">
					<label class="flex flex-col gap-1 text-sm">
						<span class="text-cape-palliser-700"
							>Voucher</span
						>
						<select
							v-model="selectedVoucherName"
							class="rounded-md border border-gray-300 px-3 py-2 text-sm">
							<option
								v-for="voucher in redeemableVouchers"
								:key="voucher.name"
								:value="voucher.name">
								{{ voucher.name }} ({{
									props.items.find(
										(item) =>
											item.name ===
											voucher.name,
									)?.quantity ?? 0
								}})
							</option>
						</select>
					</label>
					<label class="flex flex-col gap-1 text-sm">
						<span class="text-cape-palliser-700">Choice</span>
						<select
							v-model="selectedChoice"
							class="rounded-md border border-gray-300 px-3 py-2 text-sm">
							<option
								v-for="choice in activeVoucher?.choices ??
								[]"
								:key="choice.value"
								:value="choice.value">
								{{ choice.label }}
							</option>
						</select>
					</label>
					<Button
						type="button"
						:disabled="
							redeemForm.processing || activeQuantity < 1
						"
						@click="redeemVoucher">
						Redeem voucher
					</Button>
				</div>
				<p
					v-if="
						redeemForm.errors.choice ||
						redeemForm.errors.voucher
					"
					class="mt-2 text-sm text-red-600">
					{{
						redeemForm.errors.choice ||
						redeemForm.errors.voucher
					}}
				</p>
			</div>

			<div
				v-if="props.items.length === 0"
				class="py-12 text-center">
				<p class="text-lg text-gray-500">
					{{
						isPublicView
							? `No items in ${props.user?.name}'s inventory yet.`
							: 'No items in your inventory yet.'
					}}
				</p>
			</div>

			<Card v-else>
				<CardContent class="p-0">
					<div class="overflow-x-auto">
						<table class="w-full border-collapse">
							<thead>
								<tr class="border-b border-gray-200">
									<th
										class="text-cape-palliser-950 px-4 py-3 text-left text-sm font-semibold">
										Item Name
									</th>
									<th
										class="text-cape-palliser-950 px-4 py-3 text-left text-sm font-semibold">
										Description
									</th>
									<th
										class="text-cape-palliser-950 px-4 py-3 text-left text-sm font-semibold">
										Quantity
									</th>
									<th
										class="text-cape-palliser-950 px-4 py-3 text-left text-sm font-semibold">
										Max Count
									</th>
									<th
										class="text-cape-palliser-950 px-4 py-3 text-left text-sm font-semibold">
										Progress
									</th>
								</tr>
							</thead>
							<tbody>
								<tr
									v-for="item in props.items"
									:key="item.id"
									class="border-b border-gray-200 hover:bg-gray-50">
									<td
										class="text-cape-palliser-950 px-4 py-3 text-sm font-medium">
										{{ item.name }}
									</td>
									<td
										class="text-cape-palliser-700 px-4 py-3 text-sm">
										{{ item.description || '—' }}
									</td>
									<td
										class="text-cape-palliser-700 px-4 py-3 text-sm">
										{{ item.quantity }}
									</td>
									<td
										class="text-cape-palliser-700 px-4 py-3 text-sm">
										{{ item.max_count }}
									</td>
									<td class="px-4 py-3 text-sm">
										<div
											class="flex items-center gap-2">
											<div
												class="h-2 w-32 overflow-hidden rounded-full bg-gray-200">
												<div
													class="h-full bg-blue-600 transition-all"
													:style="{
														width: `${
															(item.quantity /
																item.max_count) *
															100
														}%`,
													}"></div>
											</div>
											<span
												class="text-xs text-gray-500">
												{{
													Math.round(
														(item.quantity /
															item.max_count) *
															100,
													)
												}}%
											</span>
										</div>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</CardContent>
			</Card>
		</div>
	</AppLayout>
</template>
