<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

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
}

interface User {
	id: number;
	name: string;
}

interface Herd {
	id: number;
	name: string;
	owner_id: number;
	herd_leader_id?: number;
	herd_members: number[];
	inventory: any[];
	equipment: any[];
	created_at: string;
	updated_at: string;
	owner: User;
	created_by: User;
	herd_leader?: Horse;
	horses: Horse[];
}

interface Props {
	herd: Herd;
	can: {
		update: boolean;
		delete: boolean;
	};
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
	{
		title: 'Herds',
		href: '/herds',
	},
	{
		title: props.herd.name,
		href: `/herds/${props.herd.id}`,
	},
];

const deleteHerd = () => {
	if (
		confirm(
			'Are you sure you want to delete this herd? This action cannot be undone.',
		)
	) {
		router.delete(route('herds.destroy', props.herd.id));
	}
};
</script>

<template>
	<Head :title="props.herd.name" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="space-y-6">
			<div class="flex items-center justify-between">
				<div>
					<h1 class="text-3xl font-bold">
						{{ props.herd.name }}
					</h1>
					<p class="text-gray-600">
						Owned by {{ props.herd.owner.name }}
					</p>
				</div>
				<div
					v-if="props.can.update || props.can.delete"
					class="flex gap-2">
					<Link
						v-if="props.can.update"
						:href="route('herds.edit', props.herd.id)">
						<Button variant="outline">Edit Herd</Button>
					</Link>
					<Button
						v-if="props.can.delete"
						variant="destructive"
						@click="deleteHerd"
						>Delete Herd</Button
					>
				</div>
			</div>

			<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
				<!-- Herd Information -->
				<Card class="lg:col-span-2">
					<CardHeader>
						<CardTitle>Herd Information</CardTitle>
					</CardHeader>
					<CardContent class="space-y-4">
						<div>
							<h3 class="font-semibold">Details</h3>
							<p>
								<strong>Name:</strong>
								{{ props.herd.name }}
							</p>
							<p>
								<strong>Owner:</strong>
								{{ props.herd.owner.name }}
							</p>
							<p>
								<strong>Created by:</strong>
								{{ props.herd.created_by.name }}
							</p>
							<p v-if="props.herd.herd_leader">
								<strong>Herd Leader:</strong>
								{{ props.herd.herd_leader.name }}
							</p>
							<p>
								<strong>Total Members:</strong>
								{{ props.herd.herd_members.length }}
							</p>
							<p>
								<strong>Created:</strong>
								{{
									new Date(
										props.herd.created_at,
									).toLocaleDateString()
								}}
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
							:href="route('horses.create')"
							class="block">
							<Button class="w-full">Add New Horse</Button>
						</Link>
						<Link
							v-if="props.can.update"
							:href="route('herds.edit', props.herd.id)"
							class="block">
							<Button
								variant="outline"
								class="w-full"
								>Edit Herd</Button
							>
						</Link>
						<!-- <Link
							:href="
								route(
									'users.herds',
									props.herd.owner.id,
								)
							"
							class="block">
							<Button
								variant="outline"
								class="w-full"
								>View {{ props.herd.owner.name }}'s
								Herds</Button
							>
						</Link> -->
					</CardContent>
				</Card>
			</div>

			<!-- Horses in this Herd -->
			<Card>
				<CardHeader>
					<CardTitle>Horses in this Herd</CardTitle>
				</CardHeader>
				<CardContent>
					<div
						v-if="props.herd.horses.length === 0"
						class="py-8 text-center">
						<p class="text-gray-500">
							No horses in this herd yet.
						</p>
						<Link
							:href="route('horses.create')"
							class="mt-4 inline-block">
							<Button>Add Your First Horse</Button>
						</Link>
					</div>
					<div
						v-else
						class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
						<Card
							v-for="horse in props.herd.horses"
							:key="horse.id"
							class="transition-shadow hover:shadow-lg">
							<CardHeader class="pb-2">
								<CardTitle class="text-lg">
									<Link
										:href="
											route(
												'horses.show',
												horse.id,
											)
										"
										class="hover:text-blue-600">
										{{ horse.name }}
									</Link>
								</CardTitle>
							</CardHeader>
							<CardContent>
								<div class="space-y-1">
									<p class="text-sm text-gray-600">
										Age: {{ horse.age }}
									</p>
									<p class="text-sm text-gray-600">
										Geno: {{ horse.geno }}
									</p>
									<div
										v-if="horse.design_link"
										class="mt-2">
										<img
											:src="horse.design_link"
											:alt="horse.name"
											class="h-32 w-full rounded object-cover" />
									</div>
								</div>
							</CardContent>
						</Card>
					</div>
				</CardContent>
			</Card>
		</div>
	</AppLayout>
</template>
