<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { Ban, Snowflake, Sun, Trash2, UserPlus } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const roleOptions = [
	{ value: 'user', label: 'User' },
	{ value: 'admin', label: 'Admin' },
	{ value: 'designer', label: 'Designer' },
	{ value: 'story_admin', label: 'Story Admin' },
	{ value: 'game_master', label: 'Game Master' },
];

const matrixRoleOrder = ['user', 'admin', 'designer', 'story_admin', 'game_master'] as const;

const roleLabels: Record<string, string> = {
	user: 'User',
	admin: 'Admin',
	designer: 'Designer',
	story_admin: 'Story Admin',
	game_master: 'Game Master',
};

const areaLabels: Record<string, string> = {
	submissions: 'Submissions',
	rollers: 'Rollers',
	lifecycle: 'Lifecycle',
	users: 'Users',
	items: 'Items',
	shop: 'Shop',
	cms: 'CMS',
};

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
	canManageRoleMatrix?: boolean;
	roleCapabilityMatrix?: Record<string, string[]> | null;
	capabilityAreas?: string[];
}

const props = withDefaults(defineProps<Props>(), {
	canManageRoleMatrix: false,
	roleCapabilityMatrix: null,
	capabilityAreas: () => [],
});

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

const matrixForm = ref<Record<string, string[]>>({});

watch(
	() => props.roleCapabilityMatrix,
	(matrix) => {
		if (!matrix) {
			matrixForm.value = {};
			return;
		}

		matrixForm.value = Object.fromEntries(
			matrixRoleOrder.map((role) => [role, [...(matrix[role] ?? [])]]),
		);
	},
	{ immediate: true },
);

function hasCapability(role: string, area: string): boolean {
	return (matrixForm.value[role] ?? []).includes(area);
}

function isCapabilityLocked(role: string, area: string): boolean {
	return role === 'user' || (role === 'admin' && area === 'users');
}

function toggleCapability(role: string, area: string): void {
	if (isCapabilityLocked(role, area)) {
		return;
	}

	const current = matrixForm.value[role] ?? [];
	if (current.includes(area)) {
		matrixForm.value[role] = current.filter((cap) => cap !== area);
	} else {
		matrixForm.value[role] = [...current, area];
	}
}

function saveRoleMatrix(): void {
	router.put(
		route('admin.role-capabilities.update'),
		{ matrix: matrixForm.value },
		{ preserveScroll: true },
	);
}

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

function unfreezeUser(user: User) {
	router.post(route('admin.users.unfreeze', user.id));
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
	<div class="space-y-6">
		<Card v-if="canManageRoleMatrix">
			<CardHeader>
				<CardTitle>Role capabilities</CardTitle>
			</CardHeader>
			<CardContent>
				<p class="text-cape-palliser-600 mb-4 text-sm">
					Toggle which admin areas each staff role can access. The User role cannot hold
					capabilities. Admin must keep Users access.
				</p>
				<div class="overflow-x-auto">
					<table class="w-full border-collapse">
						<thead>
							<tr class="border-b border-gray-200">
								<th class="text-cape-palliser-950 px-3 py-2 text-left text-sm font-semibold">
									Role
								</th>
								<th
									v-for="area in capabilityAreas"
									:key="area"
									class="text-cape-palliser-950 px-3 py-2 text-center text-sm font-semibold">
									{{ areaLabels[area] ?? area }}
								</th>
							</tr>
						</thead>
						<tbody>
							<tr
								v-for="role in matrixRoleOrder"
								:key="role"
								class="border-b border-gray-200">
								<td class="text-cape-palliser-950 px-3 py-2 text-sm font-medium">
									{{ roleLabels[role] ?? role }}
								</td>
								<td
									v-for="area in capabilityAreas"
									:key="`${role}-${area}`"
									class="px-3 py-2 text-center">
									<input
										type="checkbox"
										class="h-4 w-4 accent-shakespeare-500"
										:checked="hasCapability(role, area)"
										:disabled="isCapabilityLocked(role, area)"
										:aria-label="`${roleLabels[role] ?? role} ${areaLabels[area] ?? area}`"
										@change="toggleCapability(role, area)" />
								</td>
							</tr>
						</tbody>
					</table>
				</div>
				<div class="mt-4">
					<Button type="button" @click="saveRoleMatrix">Save capabilities</Button>
				</div>
			</CardContent>
		</Card>

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
											class="inline-flex items-center rounded-full border border-red-300 px-2.5 py-0.5 text-xs font-medium text-red-700 dark:border-red-700 dark:text-red-300">
											Banned
										</span>
										<span
											v-if="user.frozen_at"
											class="inline-flex items-center rounded-full border border-sky-300 px-2.5 py-0.5 text-xs font-medium text-sky-700 dark:border-sky-700 dark:text-sky-300">
											Frozen
										</span>
										<span
											v-if="!user.banned_at && !user.frozen_at"
											class="inline-flex items-center rounded-full border border-gray-200 px-2.5 py-0.5 text-xs text-cape-palliser-500 dark:border-gray-600">
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
										v-if="user.frozen_at"
										variant="outline"
										size="sm"
										@click="unfreezeUser(user)">
										<Sun class="mr-1 h-4 w-4" />
										Unfreeze
									</Button>
									<Button
										v-if="!user.banned_at && user.role === 'user'"
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
										v-if="user.role === 'user'"
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
	</div>
</template>
