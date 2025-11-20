<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

interface Horse {
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
	owner: {
		id: number;
		name: string;
	};
	herd_leader?: Horse;
}

interface Props {
	herds: Herd[];
	can: {
		create: boolean;
	};
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
	{
		title: 'Herds',
		href: '/herds',
	},
];

// eslint-disable-next-line @typescript-eslint/no-unused-vars
const canEditHerd = (herd: Herd): boolean => {
	// Since we're on the user's own herds page, they can edit all their herds
	return true;
};
</script>

<template>
	<Head title="Herds" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="space-y-6">
			<div class="flex items-center justify-between">
				<h1 class="text-3xl font-bold">My Herds</h1>
				<Link
					v-if="props.can.create"
					:href="route('herds.create')">
					<Button>Create New Herd</Button>
				</Link>
			</div>

			<div
				v-if="props.herds.length === 0"
				class="py-12 text-center">
				<p class="text-lg text-gray-500">No herds found.</p>
				<Link
					:href="route('herds.create')"
					class="mt-4 inline-block">
					<Button>Create Your First Herd</Button>
				</Link>
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
							<div class="flex gap-2">
								<Link
									v-if="canEditHerd(herd)"
									:href="
										route('herds.edit', herd.id)
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
								<strong>Owner:</strong>
								{{ herd.owner.name }}
							</p>
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
