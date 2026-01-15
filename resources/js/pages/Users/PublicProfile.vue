<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

interface User {
	id: number;
	name: string;
}

interface Props {
	user: User;
	herdCount: number;
	horseCount: number;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
	{
		title: 'Users',
		href: '/',
	},
	{
		title: props.user.name,
		href: `/users/${props.user.id}`,
	},
];
</script>

<template>
	<Head :title="`${props.user.name}'s Profile`" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="mx-auto max-w-4xl space-y-6">
			<!-- User Header -->
			<div class="text-center">
				<div
					class="mx-auto mb-4 flex h-24 w-24 items-center justify-center rounded-full bg-gray-200">
					<span class="text-2xl font-bold text-gray-600">
						{{ props.user.name.charAt(0).toUpperCase() }}
					</span>
				</div>
				<h1 class="text-3xl font-bold">{{ props.user.name }}</h1>
				<p class="mt-2 text-gray-600">
					Rattlesnake Mountain Player
				</p>
			</div>

			<!-- Collection Stats -->
			<div class="grid grid-cols-1 gap-6 md:grid-cols-2">
				<Card>
					<CardHeader>
						<CardTitle
							class="flex items-center justify-between">
							<span>Herd Collection</span>
							<span class="text-2xl font-bold">{{
								props.herdCount
							}}</span>
						</CardTitle>
					</CardHeader>
					<CardContent>
						<p class="mb-4 text-gray-600">
							View {{ props.user.name }}'s collection of
							herds and their horses.
						</p>
						<Link
							:href="route('users.herds', props.user.id)"
							class="block">
							<Button class="w-full">View Herds</Button>
						</Link>
					</CardContent>
				</Card>

				<Card>
					<CardHeader>
						<CardTitle
							class="flex items-center justify-between">
							<span>Horse Collection</span>
							<span class="text-2xl font-bold">{{
								props.horseCount
							}}</span>
						</CardTitle>
					</CardHeader>
					<CardContent>
						<p class="mb-4 text-gray-600">
							Browse {{ props.user.name }}'s individual
							horses and their details.
						</p>
						<Link
							:href="route('users.horses', props.user.id)"
							class="block">
							<Button class="w-full">View Horses</Button>
						</Link>
					</CardContent>
				</Card>
			</div>

			<!-- Quick Links -->
			<Card>
				<CardHeader>
					<CardTitle>Quick Links</CardTitle>
				</CardHeader>
				<CardContent>
					<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
						<Link
							:href="route('users.herds', props.user.id)"
							class="block rounded-lg border p-4 transition-colors hover:bg-gray-50">
							<h3 class="font-semibold">All Herds</h3>
							<p class="text-sm text-gray-600">
								{{ props.herdCount }} herd{{
									props.herdCount !== 1 ? 's' : ''
								}}
							</p>
						</Link>
						<Link
							:href="route('users.horses', props.user.id)"
							class="block rounded-lg border p-4 transition-colors hover:bg-gray-50">
							<h3 class="font-semibold">All Horses</h3>
							<p class="text-sm text-gray-600">
								{{ props.horseCount }} horse{{
									props.horseCount !== 1 ? 's' : ''
								}}
							</p>
						</Link>
					</div>
				</CardContent>
			</Card>
		</div>
	</AppLayout>
</template>
