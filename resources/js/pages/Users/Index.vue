<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

interface User {
	id: number;
	name: string;
}

interface Props {
	users: User[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
	{
		title: 'Users',
		href: '/users',
	},
];
</script>

<template>
	<Head title="All Users" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="mx-auto max-w-4xl space-y-6">
			<!-- Header -->
			<div>
				<h1
					class="text-3xl font-bold text-gray-900 dark:text-white">
					All Users
				</h1>
				<p class="mt-2 text-gray-600 dark:text-gray-400">
					Browse all players and their collections
				</p>
			</div>

			<!-- Users List -->
			<Card>
				<CardHeader>
					<CardTitle>Players</CardTitle>
				</CardHeader>
				<CardContent>
					<div
						v-if="props.users.length > 0"
						class="space-y-2">
						<div
							v-for="user in props.users"
							:key="user.id"
							class="flex items-center justify-between rounded-lg border p-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
							<div class="flex items-center gap-3">
								<div
									class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700">
									<span
										class="text-sm font-semibold text-gray-600 dark:text-gray-300">
										{{
											user.name
												.charAt(0)
												.toUpperCase()
										}}
									</span>
								</div>
								<span
									class="font-medium text-gray-900 dark:text-white">
									{{ user.name }}
								</span>
							</div>
							<Link
								:href="`/u/${user.id}`"
								class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
								View Profile →
							</Link>
						</div>
					</div>
					<p
						v-else
						class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
						No users found
					</p>
				</CardContent>
			</Card>
		</div>
	</AppLayout>
</template>
