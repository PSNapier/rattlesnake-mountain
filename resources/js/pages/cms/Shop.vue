<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';

interface Listing {
	id: number;
	item_id: number;
	name: string;
	description: string | null;
	image_path: string | null;
	scorpion_price: number;
	owned_quantity: number;
	max_count: number;
	can_buy_more: boolean;
}

interface PaginationLink {
	url: string | null;
	label: string;
	active: boolean;
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
}

const props = defineProps<Props>();
const page = usePage();

const purchase = (listingId: number): void => {
	router.post(route('shop.purchase', listingId), { quantity: 1 }, { preserveScroll: true });
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

			<div
				v-if="props.isAuthenticated"
				class="rounded-lg border border-shakespeare-300 bg-shakespeare-50 px-4 py-2 text-sm font-semibold text-shakespeare-800">
				Scorpions: {{ props.scorpionBalance ?? 0 }}
			</div>

			<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
				<Card
					v-for="listing in props.listings.data"
					:key="listing.id"
					class="h-full">
					<CardHeader class="space-y-3">
						<img
							v-if="listing.image_path"
							:src="listing.image_path"
							:alt="listing.name"
							class="h-44 w-full rounded-md object-cover" />
						<div class="space-y-1">
							<CardTitle>{{ listing.name }}</CardTitle>
							<p class="text-cape-palliser-700 text-sm">
								{{ listing.scorpion_price }} Scorpions
							</p>
						</div>
					</CardHeader>
					<CardContent class="space-y-2 text-sm">
						<p class="text-cape-palliser-700">
							{{ listing.description || 'No description provided.' }}
						</p>
						<p class="text-cape-palliser-600">
							Owned: {{ listing.owned_quantity }} / {{ listing.max_count }}
						</p>
					</CardContent>
					<CardFooter>
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
							<Link :href="route('login')">Sign In To Buy</Link>
						</Button>
					</CardFooter>
				</Card>
			</div>

			<div
				v-if="props.listings.data.length === 0"
				class="rounded-lg border border-dashed border-cape-palliser-300 px-4 py-6 text-center text-cape-palliser-700">
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
					]"
					v-html="link.label" />
			</div>
		</section>
	</AppLayout>
</template>
