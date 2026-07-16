<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

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

const DEFAULT_CAPABILITY_AREAS = [
	'submissions',
	'rollers',
	'lifecycle',
	'users',
	'items',
	'shop',
	'cms',
] as const;

const page = usePage<{
	canManageRoleMatrix?: boolean;
	roleCapabilityMatrix?: Record<string, string[]> | null;
	capabilityAreas?: string[];
}>();

const capabilityAreas = computed(() =>
	page.props.capabilityAreas?.length
		? page.props.capabilityAreas
		: [...DEFAULT_CAPABILITY_AREAS],
);

const roleCapabilityMatrix = computed(
	() => page.props.roleCapabilityMatrix ?? null,
);

const matrixForm = ref<Record<string, string[]>>({});

watch(
	roleCapabilityMatrix,
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
</script>

<template>
	<Card>
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
</template>
