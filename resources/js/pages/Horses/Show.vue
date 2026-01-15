<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

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
	age: number;
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

interface Props {
	horse: Horse;
	can: {
		update: boolean;
		delete: boolean;
	};
}

const props = defineProps<Props>();

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
									<strong>Age:</strong>
									{{ props.horse.age }}
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

						<div
							v-if="
								props.horse.equipment &&
								props.horse.equipment.length > 0
							">
							<h3 class="font-semibold">Equipment</h3>
							<p class="text-gray-600">
								{{ props.horse.equipment.length }} items
							</p>
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
