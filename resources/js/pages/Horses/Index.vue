<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

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
	created_at: string;
	updated_at: string;
	owner: User;
	bred_by: User;
	herd?: Herd;
}

interface Props {
	horses: Horse[];
	can: {
		create: boolean;
	};
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
	{
		title: 'Horses',
		href: '/horses',
	},
];

// eslint-disable-next-line @typescript-eslint/no-unused-vars
const canEditHorse = (horse: Horse): boolean => {
	// Since we're on the user's own horses page, they can edit all their horses
	return true;
};
</script>

<template>
	<Head title="Horses" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="space-y-6">
			<div class="flex items-center justify-between">
				<h1 class="text-3xl font-bold">My Horses</h1>
				<Link
					v-if="props.can.create"
					:href="route('horses.create')">
					<Button>Add New Horse</Button>
				</Link>
			</div>

			<div
				v-if="props.horses.length === 0"
				class="py-12 text-center">
				<p class="text-lg text-gray-500">No horses found.</p>
				<Link
					:href="route('horses.create')"
					class="mt-4 inline-block">
					<Button>Add Your First Horse</Button>
				</Link>
			</div>

			<div
				v-else
				class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
				<Card
					v-for="horse in props.horses"
					:key="horse.id"
					class="transition-shadow hover:shadow-lg">
					<CardHeader>
						<CardTitle
							class="flex items-start justify-between">
							<Link
								:href="route('horses.show', horse.id)"
								class="hover:text-blue-600">
								{{ horse.name }}
							</Link>
							<div class="flex gap-2">
								<Link
									v-if="canEditHorse(horse)"
									:href="
										route('horses.edit', horse.id)
									">
									<Button
										variant="outline"
										size="sm"
										>Edit</Button
									>
								</Link>
							</div>
						</CardTitle>
					</CardHeader>
					<CardContent>
						<div class="space-y-2">
							<p class="text-sm text-gray-600">
								<strong>Age:</strong> {{ horse.age }}
							</p>
							<p class="text-sm text-gray-600">
								<strong>Geno:</strong> {{ horse.geno }}
							</p>
							<p class="text-sm text-gray-600">
								<strong>Owner:</strong>
								{{ horse.owner.name }}
							</p>
							<p
								v-if="horse.herd"
								class="text-sm text-gray-600">
								<strong>Herd:</strong>
								{{ horse.herd.name }}
							</p>
							<div
								v-if="horse.design_link"
								class="mt-2">
								<img
									:src="horse.design_link"
									:alt="horse.name"
									class="h-32 w-full rounded object-cover" />
							</div>
							<p class="text-sm text-gray-500">
								Created
								{{
									new Date(
										horse.created_at,
									).toLocaleDateString()
								}}
							</p>
						</div>
					</CardContent>
				</Card>
			</div>
		</div>
	</AppLayout>
</template>
