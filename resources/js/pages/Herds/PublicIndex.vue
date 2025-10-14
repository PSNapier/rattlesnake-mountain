<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

interface Horse {
	id: number;
	name: string;
}

interface User {
	id: number;
	name: string;
}

interface Herd {
	id: number;
	name: string;
	owner_id: number;
	created_by: number;
	herd_leader_id?: number;
	herd_members: number[];
	inventory: any[];
	equipment: any[];
	created_at: string;
	updated_at: string;
	owner: User;
	herd_leader?: Horse;
}

interface Props {
	user: User;
	herds: Herd[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
	{
		title: props.user.name,
		href: `/users/${props.user.id}`,
	},
	{
		title: 'Herds',
		href: `/users/${props.user.id}/herds`,
	},
];
</script>

<template>
	<Head :title="`${props.user.name}'s Herds`" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="space-y-6">
			<div class="text-center">
				<h1 class="text-3xl font-bold">
					{{ props.user.name }}'s Herds
				</h1>
				<p class="mt-2 text-gray-600">Public collection of herds</p>
				<div class="mt-4 flex justify-center gap-4">
					<Link
						:href="route('users.horses', props.user.id)"
						class="text-blue-600 hover:text-blue-800">
						View {{ props.user.name }}'s Horses
					</Link>
				</div>
			</div>

			<div
				v-if="props.herds.length === 0"
				class="py-12 text-center">
				<p class="text-lg text-gray-500">
					{{ props.user.name }} has no public herds yet.
				</p>
			</div>

			<div
				v-else
				class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
				<Card
					v-for="herd in props.herds"
					:key="herd.id"
					class="transition-shadow hover:shadow-lg">
					<CardHeader>
						<CardTitle
							class="flex items-start justify-between">
							<Link
								:href="route('herds.show', herd.id)"
								class="hover:text-blue-600">
								{{ herd.name }}
							</Link>
						</CardTitle>
					</CardHeader>
					<CardContent>
						<div class="space-y-2">
							<p
								v-if="herd.herd_leader"
								class="text-sm text-gray-600">
								<strong>Leader:</strong>
								{{ herd.herd_leader.name }}
							</p>
							<p class="text-sm text-gray-600">
								<strong>Members:</strong>
								{{ herd.herd_members.length }}
							</p>
							<p class="text-sm text-gray-500">
								Created
								{{
									new Date(
										herd.created_at,
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
