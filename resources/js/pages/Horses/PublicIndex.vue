<script setup lang="ts">
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
	user: User;
	horses: Horse[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
	{
		title: props.user.name,
		href: `/users/${props.user.id}`,
	},
	{
		title: 'Horses',
		href: `/users/${props.user.id}/horses`,
	},
];
</script>

<template>
	<Head :title="`${props.user.name}'s Horses`" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="space-y-6">
			<div class="text-center">
				<h1 class="text-3xl font-bold">
					{{ props.user.name }}'s Horses
				</h1>
				<p class="mt-2 text-gray-600">
					Public collection of horses
				</p>
				<div class="mt-4 flex justify-center gap-4">
					<Link
						:href="route('users.herds', props.user.id)"
						class="text-blue-600 hover:text-blue-800">
						View {{ props.user.name }}'s Herds
					</Link>
				</div>
			</div>

			<div
				v-if="props.horses.length === 0"
				class="py-12 text-center">
				<p class="text-lg text-gray-500">
					{{ props.user.name }} has no public horses yet.
				</p>
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
