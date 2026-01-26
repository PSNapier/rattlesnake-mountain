<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

interface User {
	id: number;
	name: string;
	avatar?: string | null;
}

interface Props {
	users: User[];
}

const props = defineProps<Props>();
const { getInitials } = useInitials();

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
					class="text-cape-palliser-950 dark:text-cape-palliser-50 text-3xl font-bold">
					All Users
				</h1>
				<p
					class="text-cape-palliser-700 dark:text-cape-palliser-300 mt-2">
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
						<Link
							id="UsersListCard"
							v-for="user in props.users"
							:key="user.id"
							:href="`/u/${user.id}`"
							class="border-cape-palliser-200 dark:border-cape-palliser-700 hover:bg-shakespeare-200 dark:hover:bg-shakespeare-200 flex items-center gap-3 border p-4 no-underline transition-colors">
							<Avatar class="h-10 w-10 rounded-md">
								<AvatarImage
									v-if="user.avatar"
									:src="user.avatar"
									:alt="user.name" />
								<AvatarFallback class="bg-shakespeare-200 text-shakespeare-700 dark:bg-shakespeare-800 dark:text-shakespeare-200">
									{{ getInitials(user.name) }}
								</AvatarFallback>
							</Avatar>
							<span
								class="text-shakespeare-600 font-medium">
								{{ user.name }}
							</span>
						</Link>
					</div>
					<p
						v-else
						class="text-cape-palliser-600 dark:text-cape-palliser-400 py-8 text-center text-sm">
						No users found
					</p>
				</CardContent>
			</Card>
		</div>
	</AppLayout>
</template>
