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

interface Herd {
	id: number;
	name: string;
	horses_count: number;
	owner: User;
}

interface Props {
	mostHorses: (User & { horses_count: number })[];
	largestHerds: Herd[];
	mostScorpions: (User & { scorpions_sum: number })[];
}

const props = defineProps<Props>();
const { getInitials } = useInitials();

const breadcrumbs: BreadcrumbItem[] = [
	{
		title: 'Leaderboard',
		href: '/leaderboard',
	},
];
</script>

<template>
	<Head title="Leaderboard" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="mx-auto max-w-4xl space-y-8">
			<div>
				<h1
					class="text-cape-palliser-950 dark:text-cape-palliser-50 flex items-center gap-2 text-3xl font-bold">
					Leaderboard
				</h1>
				<p
					class="text-cape-palliser-700 dark:text-cape-palliser-300 mt-2">
					Top players by horses, herd size, and scorpions
				</p>
			</div>

			<div class="grid gap-8 lg:grid-cols-1">
				<Card>
					<CardHeader>
						<CardTitle>Most Horses</CardTitle>
					</CardHeader>
					<CardContent>
						<div
							v-if="props.mostHorses.length > 0"
							class="space-y-2">
							<Link
								v-for="(
									user, index
								) in props.mostHorses"
								:key="user.id"
								:href="`/u/${user.id}`"
								class="border-cape-palliser-200 dark:border-cape-palliser-700 hover:bg-shakespeare-200 dark:hover:bg-shakespeare-200 flex items-center gap-3 border p-4 no-underline transition-colors">
								<span
									class="text-cape-palliser-500 shrink-0 text-sm font-medium">
									#{{ index + 1 }}
								</span>
								<Avatar
									class="size-10 shrink-0 rounded-md">
									<AvatarImage
										v-if="user.avatar"
										:src="user.avatar"
										:alt="user.name" />
									<AvatarFallback
										class="bg-shakespeare-200 text-shakespeare-700 dark:bg-shakespeare-800 dark:text-shakespeare-200">
										{{ getInitials(user.name) }}
									</AvatarFallback>
								</Avatar>
								<span
									class="text-shakespeare-600 grow font-medium">
									{{ user.name }}
								</span>
								<span
									class="text-cape-palliser-500 shrink-0 text-sm">
									{{ user.horses_count }} horses
								</span>
							</Link>
						</div>
						<p
							v-else
							class="text-cape-palliser-600 dark:text-cape-palliser-400 py-8 text-center text-sm">
							No data yet
						</p>
					</CardContent>
				</Card>

				<Card>
					<CardHeader>
						<CardTitle>Largest Herds</CardTitle>
					</CardHeader>
					<CardContent>
						<div
							v-if="props.largestHerds.length > 0"
							class="space-y-2">
							<div
								v-for="(
									herd, index
								) in props.largestHerds"
								:key="herd.id"
								class="border-cape-palliser-200 dark:border-cape-palliser-700 flex items-center gap-3 border p-4">
								<span
									class="text-cape-palliser-500 shrink-0 text-sm font-medium">
									#{{ index + 1 }}
								</span>
								<span
									class="text-shakespeare-600 grow font-medium">
									{{ herd.name }}
								</span>
								<Link
									:href="`/u/${herd.owner.id}`"
									class="text-shakespeare-500 hover:text-shakespeare-700 hover:underline">
									{{ herd.owner.name }}
								</Link>
								<span
									class="text-cape-palliser-500 shrink-0 text-sm">
									{{ herd.horses_count }} horses
								</span>
							</div>
						</div>
						<p
							v-else
							class="text-cape-palliser-600 dark:text-cape-palliser-400 py-8 text-center text-sm">
							No data yet
						</p>
					</CardContent>
				</Card>

				<Card>
					<CardHeader>
						<CardTitle>Most Scorpions</CardTitle>
					</CardHeader>
					<CardContent>
						<div
							v-if="props.mostScorpions.length > 0"
							class="space-y-2">
							<Link
								v-for="(
									user, index
								) in props.mostScorpions"
								:key="user.id"
								:href="`/u/${user.id}`"
								class="border-cape-palliser-200 dark:border-cape-palliser-700 hover:bg-shakespeare-200 dark:hover:bg-shakespeare-200 flex items-center gap-3 border p-4 no-underline transition-colors">
								<span
									class="text-cape-palliser-500 shrink-0 text-sm font-medium">
									#{{ index + 1 }}
								</span>
								<Avatar
									class="size-10 shrink-0 rounded-md">
									<AvatarImage
										v-if="user.avatar"
										:src="user.avatar"
										:alt="user.name" />
									<AvatarFallback
										class="bg-shakespeare-200 text-shakespeare-700 dark:bg-shakespeare-800 dark:text-shakespeare-200">
										{{ getInitials(user.name) }}
									</AvatarFallback>
								</Avatar>
								<span
									class="text-shakespeare-600 grow font-medium">
									{{ user.name }}
								</span>
								<span
									class="text-cape-palliser-500 shrink-0 text-sm">
									{{ user.scorpions_sum }} scorpions
								</span>
							</Link>
						</div>
						<p
							v-else
							class="text-cape-palliser-600 dark:text-cape-palliser-400 py-8 text-center text-sm">
							No data yet
						</p>
					</CardContent>
				</Card>
			</div>
		</div>
	</AppLayout>
</template>
