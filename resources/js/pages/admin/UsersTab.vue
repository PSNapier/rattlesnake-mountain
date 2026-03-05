<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
	Dialog,
	DialogContent,
	DialogFooter,
	DialogHeader,
	DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Link, router } from '@inertiajs/vue3';
import { Ban, Snowflake, Trash2, UserPlus } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const roleOptions = [
	{ value: 'user', label: 'User' },
	{ value: 'admin', label: 'Admin' },
	{ value: 'designer', label: 'Designer' },
	{ value: 'story_admin', label: 'Story Admin' },
	{ value: 'game_master', label: 'Game Master' },
];

interface User {
	id: number;
	name: string;
	role: string;
	created_at: string;
	last_login_at: string | null;
	frozen_at: string | null;
	banned_at: string | null;
}

interface PaginationLink {
	url: string | null;
	label: string;
	active: boolean;
}

interface PaginatedUsers {
	data: User[];
	links: PaginationLink[];
	meta: {
		current_page: number;
		last_page: number;
		per_page: number;
		total: number;
		from: number | null;
		to: number | null;
	};
}

interface Props {
	users?: PaginatedUsers | null;
	userSearch: string;
}

const props = defineProps<Props>();

const usersData = computed(() => props.users?.data ?? []);
const usersMeta = computed(() => props.users?.meta ?? { last_page: 1, from: null, to: null, total: 0 });
const usersLinks = computed(() => props.users?.links ?? []);

const searchQuery = ref(props.userSearch);
const deleteDialogUser = ref<User | null>(null);
const showDeleteDialog = computed({
	get: () => !!deleteDialogUser.value,
	set: (v) => {
		if (!v) deleteDialogUser.value = null;
	},
});

function formatDate(value: string | null): string {
	if (!value) return '—';
	return new Date(value).toLocaleDateString(undefined, {
		year: 'numeric',
		month: 'short',
		day: 'numeric',
	});
}

function submitSearch() {
	router.get(route('admin.index'), { user_search: searchQuery.value, page: 1 });
}

function goToPage(url: string | null) {
	if (url) router.get(url);
}

function freezeUser(user: User) {
	router.post(route('admin.users.freeze', user.id));
}

function banUser(user: User) {
	router.post(route('admin.users.ban', user.id));
}

function unbanUser(user: User) {
	router.post(route('admin.users.unban', user.id));
}

function changeRole(user: User, role: string) {
	router.put(route('admin.users.role.update', user.id), { role });
}

function openDeleteDialog(user: User) {
	deleteDialogUser.value = user;
}

function closeDeleteDialog() {
	deleteDialogUser.value = null;
}

function confirmDelete() {
	if (!deleteDialogUser.value) return;
	router.delete(route('admin.users.destroy', deleteDialogUser.value.id), {
		onSuccess: () => closeDeleteDialog(),
	});
}
</script>

<template>
	<Card>
		<CardContent>
			<!-- Search -->
			<form
				class="mb-6 flex gap-2"
				@submit.prevent="submitSearch">
				<Input
					v-model="searchQuery"
					placeholder="Search by name..."
					class="max-w-sm"
					@keyup.enter="submitSearch" />
				<Button type="submit">Search</Button>
			</form>

			<!-- Table -->
			<div class="overflow-x-auto">
				<table class="w-full border-collapse">
					<thead>
						<tr class="border-b border-gray-200">
							<th class="text-cape-palliser-950 px-4 py-3 text-left text-sm font-semibold">
								Name
							</th>
							<th class="text-cape-palliser-950 px-4 py-3 text-left text-sm font-semibold">
								Created
							</th>
							<th class="text-cape-palliser-950 px-4 py-3 text-left text-sm font-semibold">
								Last Login
							</th>
							<th class="text-cape-palliser-950 px-4 py-3 text-left text-sm font-semibold">
								Role
							</th>
							<th class="text-cape-palliser-950 px-4 py-3 text-left text-sm font-semibold">
								Status
							</th>
							<th class="text-cape-palliser-950 px-4 py-3 text-left text-sm font-semibold">
								Actions
							</th>
						</tr>
					</thead>
					<tbody>
						<tr
							v-if="usersData.length === 0"
							class="border-b border-gray-200">
							<td
								colspan="6"
								class="text-cape-palliser-600 px-4 py-8 text-center">
								No users found
							</td>
						</tr>
						<tr
							v-for="user in usersData"
							:key="user.id"
							class="border-b border-gray-200 hover:bg-gray-50">
							<td class="text-cape-palliser-950 px-4 py-3 text-sm">
								<Link
									:href="route('users.profile', user.id)"
									class="hover:text-shakespeare-600 hover:underline">
									{{ user.name }}
								</Link>
							</td>
							<td class="text-cape-palliser-700 px-4 py-3 text-sm">
								{{ formatDate(user.created_at) }}
							</td>
							<td class="text-cape-palliser-700 px-4 py-3 text-sm">
								{{ formatDate(user.last_login_at) }}
							</td>
							<td class="px-4 py-3">
								<Select
									:model-value="user.role"
									:options="roleOptions"
									@update:model-value="(v: string) => changeRole(user, v)" />
							</td>
							<td class="px-4 py-3">
								<div class="flex gap-1">
									<span
										v-if="user.banned_at"
										class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900/30 dark:text-red-300">
										Banned
									</span>
									<span
										v-if="user.frozen_at"
										class="inline-flex items-center rounded-full bg-sky-100 px-2.5 py-0.5 text-xs font-medium text-sky-800 dark:bg-sky-900/30 dark:text-sky-300">
										Frozen
									</span>
									<span
										v-if="!user.banned_at && !user.frozen_at"
										class="text-cape-palliser-500 text-xs">
										—
									</span>
								</div>
							</td>
							<td class="flex flex-wrap gap-2 px-4 py-3">
								<Button
									v-if="!user.frozen_at && !user.banned_at"
									variant="outline"
									size="sm"
									@click="freezeUser(user)">
									<Snowflake class="mr-1 h-4 w-4" />
									Freeze
								</Button>
								<Button
									v-if="!user.banned_at && user.role !== 'admin'"
									variant="outline"
									size="sm"
									@click="banUser(user)">
									<Ban class="mr-1 h-4 w-4" />
									Ban
								</Button>
								<Button
									v-if="user.banned_at"
									variant="outline"
									size="sm"
									@click="unbanUser(user)">
									<UserPlus class="mr-1 h-4 w-4" />
									Unban
								</Button>
								<Button
									v-if="user.role !== 'admin'"
									variant="outline"
									size="sm"
									class="text-red-600 hover:bg-red-50 hover:text-red-700"
									@click="openDeleteDialog(user)">
									<Trash2 class="mr-1 h-4 w-4" />
									Delete
								</Button>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<!-- Pagination -->
			<nav
				v-if="usersMeta.last_page > 1"
				class="mt-4 flex items-center justify-between border-t border-gray-200 pt-4">
				<p class="text-cape-palliser-600 text-sm">
					Showing
					<span class="font-medium">{{ usersMeta.from ?? 0 }}</span>
					to
					<span class="font-medium">{{ usersMeta.to ?? 0 }}</span>
					of
					<span class="font-medium">{{ usersMeta.total }}</span>
					users
				</p>
				<div class="flex gap-1">
					<button
						v-for="(link, i) in usersLinks"
						:key="i"
						:disabled="!link.url"
						:class="[
							'rounded px-3 py-1 text-sm transition-colors',
							link.active
								? 'bg-shakespeare-500 text-white'
								: link.url
									? 'border border-gray-300 hover:bg-gray-50'
									: 'cursor-not-allowed border border-gray-200 text-gray-400',
						]"
						@click="goToPage(link.url)">
						<span v-html="link.label" />
					</button>
				</div>
			</nav>
		</CardContent>
	</Card>

	<Dialog v-model:open="showDeleteDialog">
		<DialogContent @pointer-down-outside="closeDeleteDialog">
			<DialogHeader>
				<DialogTitle>Delete user</DialogTitle>
			</DialogHeader>
			<p v-if="deleteDialogUser" class="text-sm text-gray-600">
				Delete <strong>{{ deleteDialogUser.name }}</strong>? Their herds and horses will be
				transferred to Sanctuary. This cannot be undone.
			</p>
			<DialogFooter>
				<Button variant="outline" @click="closeDeleteDialog">Cancel</Button>
				<Button
					variant="destructive"
					@click="confirmDelete">
					Delete
				</Button>
			</DialogFooter>
		</DialogContent>
	</Dialog>
</template>
