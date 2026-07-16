<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Item {
	id: number;
	name: string;
	description?: string | null;
	quantity: number;
	max_count: number;
}

interface Props {
	items: Item[];
	user?: {
		id: number;
		name: string;
	};
}

const VOUCHER_NAME = 'Cream/Pearl Stone Voucher';

const props = defineProps<Props>();

const isPublicView = computed(() => !!props.user);

const voucherItem = computed(() =>
	props.items.find((item) => item.name === VOUCHER_NAME && item.quantity > 0),
);

const redeemChoice = ref<'cream' | 'pearl'>('cream');

const redeemForm = useForm({
	choice: 'cream' as 'cream' | 'pearl',
});

function redeemVoucher() {
	redeemForm.choice = redeemChoice.value;
	redeemForm.post(route('inventory.redeem-cream-pearl-voucher'), {
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
			isPublicView
				? `${props.user?.name}'s Inventory`
				: 'My Inventory'
		" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="space-y-6">
			<div class="flex items-center justify-between">
				<h1 class="text-3xl font-bold">
					{{ isPublicView ? `${props.user?.name}'s Inventory` : 'My Inventory' }}
				</h1>
			</div>

			<div
				v-if="!isPublicView && voucherItem"
				class="rounded-lg border border-cape-palliser-200 bg-cape-palliser-50 p-4">
				<p class="text-cape-palliser-950 mb-3 text-sm font-medium">
					You have {{ voucherItem.quantity }} Cream/Pearl Stone Voucher{{
						voucherItem.quantity === 1 ? '' : 's'
					}}. Redeem for a Cream Stone or Pearl Stone.
				</p>
				<div class="flex flex-wrap items-end gap-4">
					<label class="flex flex-col gap-1 text-sm">
						<span class="text-cape-palliser-700">Choice</span>
						<select
							v-model="redeemChoice"
							class="rounded-md border border-gray-300 px-3 py-2 text-sm">
							<option value="cream">Cream Stone</option>
							<option value="pearl">Pearl Stone</option>
						</select>
					</label>
					<Button
						type="button"
						:disabled="redeemForm.processing"
						@click="redeemVoucher">
						Redeem voucher
					</Button>
				</div>
				<p
					v-if="redeemForm.errors.choice"
					class="mt-2 text-sm text-red-600">
					{{ redeemForm.errors.choice }}
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
										Progress
									</th>
								</tr>
							</thead>
							<tbody>
								<tr
									v-for="item in props.items"
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
											<div class="h-2 w-32 overflow-hidden rounded-full bg-gray-200">
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
											<span class="text-xs text-gray-500">
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
