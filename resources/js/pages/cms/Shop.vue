<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
	Card,
	CardContent,
	CardFooter,
	CardHeader,
	CardTitle,
} from '@/components/ui/card';
import { Select } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Listing {
	id: number;
	item_id: number;
	name: string;
	flavor_text: string | null;
	description: string | null;
	image_path: string | null;
	scorpion_price: number;
	owned_quantity: number;
	max_count: number;
	uses_per_unit: number;
	can_buy_more: boolean;
}

interface PaginationLink {
	url: string | null;
	label: string;
	active: boolean;
}

interface ShopSort {
	sort: 'default' | 'name' | 'price';
	dir: 'asc' | 'desc';
}

interface Props {
	hero: {
		title: string;
		description: string;
	};
	listings: {
		data: Listing[];
		links: PaginationLink[];
		current_page: number;
		last_page: number;
	};
	scorpionBalance: number | null;
	isAuthenticated: boolean;
	shopSort: ShopSort;
}

const props = defineProps<Props>();
const page = usePage();

const sortSelectValue = computed(() => {
	if (props.shopSort.sort === 'default') {
		return 'default';
	}
	return `${props.shopSort.sort}:${props.shopSort.dir}`;
});

const sortOptions = [
	{ value: 'default', label: 'Shop order' },
	{ value: 'name:asc', label: 'Name (A–Z)' },
	{ value: 'name:desc', label: 'Name (Z–A)' },
	{ value: 'price:asc', label: 'Price ($ → $$$)' },
	{ value: 'price:desc', label: 'Price ($$$ → $)' },
] as const;

function applyShopSort(value: string): void {
	if (value === 'default') {
		router.get(route('shop'), {}, { preserveScroll: true });
		return;
	}
	const parts = value.split(':');
	if (parts.length === 2) {
		router.get(
			route('shop'),
			{ sort: parts[0], dir: parts[1] },
			{ preserveScroll: true },
		);
	}
}

const purchase = (listingId: number): void => {
	router.post(
		route('shop.purchase', listingId),
		{ quantity: 1 },
		{ preserveScroll: true },
	);
};
</script>

<template>
	<Head :title="props.hero.title" />

	<AppLayout>
		<section class="space-y-6 p-6">
			<div class="space-y-2">
				<h1 class="text-cape-palliser-950 text-3xl font-bold">
					{{ props.hero.title }}
				</h1>
				<p class="text-cape-palliser-700">
					{{ props.hero.description }}
				</p>
			</div>

			<div
				v-if="(page.props.flash as any)?.success"
				class="rounded-lg bg-green-50 p-4 text-sm text-green-800 dark:bg-green-900/20 dark:text-green-200">
				{{ (page.props.flash as any)?.success }}
			</div>

			<div
				v-if="(page.props.errors as any)?.purchase"
				class="rounded-lg bg-red-50 p-4 text-sm text-red-800 dark:bg-red-900/20 dark:text-red-200">
				{{ (page.props.errors as any)?.purchase }}
			</div>

			<div class="flex flex-wrap items-center gap-4">
				<div
					v-if="props.isAuthenticated"
					class="border-shakespeare-300 bg-shakespeare-50 text-shakespeare-800 w-fit shrink-0 rounded-lg border px-4 py-2 text-sm font-semibold">
					Scorpions: {{ props.scorpionBalance ?? 0 }}
				</div>
				<div
					class="text-cape-palliser-800 ml-auto flex shrink-0 flex-wrap items-center gap-2">
					<span class="text-sm font-medium">Sort</span>
					<Select
						:model-value="sortSelectValue"
						:options="[...sortOptions]"
						class-name="min-w-[12rem]"
						placeholder="Sort"
						size="sm"
						@update:model-value="applyShopSort" />
				</div>
			</div>

			<div
				class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
				<Card
					v-for="listing in props.listings.data"
					:key="listing.id"
					class="h-full gap-4 py-4">
					<CardHeader class="space-y-2 px-4">
						<div
							v-if="listing.image_path"
							class="flex h-36 w-full shrink-0 items-center justify-center overflow-hidden rounded-md">
							<img
								:src="listing.image_path"
								:alt="listing.name"
								class="max-h-full max-w-full object-contain" />
						</div>
						<div class="space-y-0.5 text-center">
							<CardTitle
								class="text-lg font-bold sm:text-xl"
								>{{ listing.name }}</CardTitle
							>
							<p
								class="text-cape-palliser-700 text-sm font-bold sm:text-base">
								{{ listing.scorpion_price }} Scorpions
							</p>
							<p
								v-if="listing.flavor_text"
								class="text-cape-palliser-600 text-sm italic sm:text-base">
								“{{ listing.flavor_text }}”
							</p>
						</div>
					</CardHeader>
					<CardContent class="px-4 text-center text-sm sm:text-base">
						<p class="text-cape-palliser-700">
							{{
								listing.description ||
								'No description provided.'
							}}
						</p>
						<p
							class="text-cape-palliser-500 mt-4 text-sm sm:mt-5">
							Max uses: {{ listing.uses_per_unit }}
						</p>
					</CardContent>
					<CardFooter class="mt-auto px-4">
						<Button
							v-if="props.isAuthenticated"
							class="w-full"
							:disabled="!listing.can_buy_more"
							@click="purchase(listing.id)">
							Buy (1)
						</Button>
						<Button
							v-else
							as-child
							class="w-full">
							<Link :href="route('login')"
								>Sign In To Buy</Link
							>
						</Button>
					</CardFooter>
				</Card>
			</div>

			<div
				v-if="props.listings.data.length === 0"
				class="border-cape-palliser-300 text-cape-palliser-700 rounded-lg border border-dashed px-4 py-6 text-center">
				No items currently visible in shop.
			</div>

			<div
				v-if="props.listings.last_page > 1"
				class="flex flex-wrap gap-2">
				<Link
					v-for="link in props.listings.links"
					:key="`${link.label}-${link.url}`"
					:href="link.url || ''"
					:class="[
						'rounded-md border px-3 py-1.5 text-sm',
						link.active
							? 'border-shakespeare-500 bg-shakespeare-500 text-white'
							: 'border-shakespeare-300 text-shakespeare-700',
						!link.url ? 'pointer-events-none opacity-50' : '',
					]">
					<span v-html="link.label" />
				</Link>
			</div>
		</section>
	</AppLayout>
</template>
