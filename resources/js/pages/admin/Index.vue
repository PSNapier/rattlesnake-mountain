<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';
import CmsTab from './CmsTab.vue';
import ItemsTab from './ItemsTab.vue';
import LifecycleTab from './LifecycleTab.vue';
import SubmissionsTab from './SubmissionsTab.vue';
import UsersTab from './UsersTab.vue';

interface Herd {
	id: number;
	name: string;
}

interface Comment {
	id: number;
	body: string;
	created_at: string;
	user: {
		id: number;
		name: string;
		is_admin: boolean;
	};
}

interface Message {
	id: number;
	subject: string;
	initial_message?: string | null;
	admin_edits?: Record<string, unknown> | null;
}

interface Submission {
	id: number;
	user_id: number;
	user_name: string;
	name: string;
	name_type: 'herd' | 'horse';
	date_submitted: string;
	status: 'pending' | 'contacted' | 'approved' | 'archived';
	last_contact_date: string | null;
	last_admin_name?: string | null;
	public_horse_id?: number | null;
	is_edit?: boolean;
	design_link?: string | null;
	age?: number;
	geno?: string;
	herd_id?: number | null;
	message?: Message | null;
	comments?: Comment[];
}

interface Item {
	id: number;
	name: string;
	max_count: number;
	description: string | null;
	is_active: boolean;
}

interface AdminUser {
	id: number;
	name: string;
	role: string;
	created_at: string;
	last_login_at: string | null;
	frozen_at: string | null;
	banned_at: string | null;
}

interface PaginatedUsers {
	data: AdminUser[];
	links: { url: string | null; label: string; active: boolean }[];
	meta: {
		current_page: number;
		last_page: number;
		per_page: number;
		total: number;
		from: number | null;
		to: number | null;
	};
}

interface LifecycleSettings {
	horse_auto_age_next_update: string;
	horse_auto_age_frequency_unit: 'weeks' | 'months';
	horse_auto_age_frequency_value: number;
	horse_auto_age_game_years: number;
	horse_auto_health_roll_min: number;
	horse_auto_health_roll_max: number;
}

interface Props {
	submissions: Submission[];
	herds?: Herd[];
	items: Item[];
	users: PaginatedUsers;
	userSearch: string;
	cmsPages: unknown[];
	menuItems: unknown[];
	lifecycleSettings?: LifecycleSettings | null;
}

type AdminTab = 'submissions' | 'users' | 'items' | 'lifecycle' | 'cms';

const props = defineProps<Props>();
const page = usePage();

const activeTab = ref<AdminTab>('submissions');
const activeTabStorageKey = 'admin.activeTab';

onMounted(() => {
	if (typeof window === 'undefined') {
		return;
	}

	const storedTab = window.localStorage.getItem(activeTabStorageKey) as AdminTab | null;

	if (storedTab === 'submissions' || storedTab === 'users' || storedTab === 'items' || storedTab === 'lifecycle' || storedTab === 'cms') {
		activeTab.value = storedTab;
	}

	watch(
		activeTab,
		(value) => {
			window.localStorage.setItem(activeTabStorageKey, value);
		},
		{ immediate: true },
	);
});
</script>

<template>
	<Head title="Admin" />

	<AppLayout>
		<div class="space-y-2 p-6">
			<div class="mb-6">
				<h1 class="text-cape-palliser-950 text-3xl font-bold">
					Admin
				</h1>
				<p class="text-cape-palliser-700 mt-2">
					Welcome to the admin area.
				</p>
			</div>

			<div
				v-if="(page.props.flash as any)?.success"
				class="mb-4 rounded-lg bg-green-50 p-4 dark:bg-green-900/20">
				<p class="text-sm font-medium text-green-800 dark:text-green-200">
					{{ (page.props.flash as any)?.success }}
				</p>
			</div>

			<!-- Tab Navigation -->
			<div class="inline-flex gap-1 rounded-lg font-semibold">
				<button
					@click="activeTab = 'submissions'"
					:class="[
						'flex items-center rounded-md px-3.5 py-1.5 transition-colors',
						activeTab === 'submissions'
							? 'bg-shakespeare-500 text-white shadow-xs'
							: 'border border-shakespeare-300 text-shakespeare-600 hover:bg-shakespeare-50 hover:text-shakespeare-700',
					]">
					<span class="text-base">Submissions</span>
				</button>
				<button
					@click="activeTab = 'users'"
					:class="[
						'flex items-center rounded-md px-3.5 py-1.5 transition-colors',
						activeTab === 'users'
							? 'bg-shakespeare-500 text-white shadow-xs'
							: 'border border-shakespeare-300 text-shakespeare-600 hover:bg-shakespeare-50 hover:text-shakespeare-700',
					]">
					<span class="text-base">Users</span>
				</button>
				<button
					@click="activeTab = 'items'"
					:class="[
						'flex items-center rounded-md px-3.5 py-1.5 transition-colors',
						activeTab === 'items'
							? 'bg-shakespeare-500 text-white shadow-xs'
							: 'border border-shakespeare-300 text-shakespeare-600 hover:bg-shakespeare-50 hover:text-shakespeare-700',
					]">
					<span class="text-base">Items</span>
				</button>
				<button
					@click="activeTab = 'lifecycle'"
					:class="[
						'flex items-center rounded-md px-3.5 py-1.5 transition-colors',
						activeTab === 'lifecycle'
							? 'bg-shakespeare-500 text-white shadow-xs'
							: 'border border-shakespeare-300 text-shakespeare-600 hover:bg-shakespeare-50 hover:text-shakespeare-700',
					]">
					<span class="text-base">Lifecycle</span>
				</button>
				<button
					@click="activeTab = 'cms'"
					:class="[
						'flex items-center rounded-md px-3.5 py-1.5 transition-colors',
						activeTab === 'cms'
							? 'bg-shakespeare-500 text-white shadow-xs'
							: 'border border-shakespeare-300 text-shakespeare-600 hover:bg-shakespeare-50 hover:text-shakespeare-700',
					]">
					<span class="text-base">CMS</span>
				</button>
			</div>

			<SubmissionsTab
				v-if="activeTab === 'submissions'"
				:submissions="props.submissions"
				:herds="props.herds" />

			<UsersTab
				v-if="activeTab === 'users'"
				:users="props.users"
				:user-search="props.userSearch" />

			<ItemsTab
				v-if="activeTab === 'items'"
				:items="props.items" />

			<LifecycleTab
				v-if="activeTab === 'lifecycle'"
				:settings="props.lifecycleSettings ?? undefined" />

			<CmsTab
				v-if="activeTab === 'cms'"
				:cms-pages="props.cmsPages"
				:menu-items="props.menuItems" />
		</div>
	</AppLayout>
</template>
