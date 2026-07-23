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
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { Link, router } from '@inertiajs/vue3';
import { ArchiveRestore, ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type Status = 'pending' | 'contacted' | 'approved' | 'archived';
type SubmissionKind = 'horse' | 'breeding';
type TypeFilter = 'all' | SubmissionKind;
type SortField =
	| 'user_name'
	| 'name'
	| 'date_submitted'
	| 'status'
	| 'last_contact_date';
type SortDirection = 'asc' | 'desc' | null;

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
		is_staff: boolean;
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
	status: Status;
	last_contact_date: string | null;
	last_admin_name?: string | null;
	public_horse_id?: number | null;
	is_edit?: boolean;
	design_link?: string | null;
	age_years?: number;
	age_months?: number;
	formatted_age?: string;
	geno?: string;
	sex?: string | null;
	herd_id?: number | null;
	message?: Message | null;
	comments?: Comment[];
}

interface BreedingRequestRow {
	id: number;
	requester_name?: string | null;
	sire_id: number;
	sire_name?: string | null;
	sire_sex?: string | null;
	sire_geno?: string | null;
	dam_id: number;
	dam_name?: string | null;
	dam_sex?: string | null;
	dam_geno?: string | null;
	evidence_url: string;
	notes?: string | null;
	created_at?: string | null;
}

interface PaginatedBreedingRequests {
	data: BreedingRequestRow[];
}

interface UnifiedRow {
	key: string;
	kind: SubmissionKind;
	id: number;
	user_id: number | null;
	user_name: string;
	name: string;
	date_submitted: string;
	status: Status;
	last_contact_date: string | null;
	last_admin_name?: string | null;
	submission?: Submission;
	breeding?: BreedingRequestRow;
}

interface Props {
	submissions: Submission[];
	herds?: Herd[];
	breedingRequests?: PaginatedBreedingRequests | null;
}

const props = withDefaults(defineProps<Props>(), {
	herds: () => [],
	breedingRequests: null,
});

const searchQuery = ref('');
const statusFilter = ref<Status | 'all'>('all');
const typeFilter = ref<TypeFilter>('all');
const sortField = ref<SortField>('date_submitted');
const sortDirection = ref<SortDirection>('desc');

const showReviewModal = ref(false);
const selectedSubmission = ref<Submission | null>(null);
const reviewNotes = ref('');

// Admin-editable form state
const adminForm = ref({
	name: '',
	age_years: 0,
	age_months: 0,
	geno: '',
	sex: '' as string,
	herd_id: null as number | null,
	design_link: '',
});

// Initialize admin form when submission is selected
const initializeAdminForm = (submission: Submission): void => {
	const adminEdits = submission.message?.admin_edits;

	adminForm.value = {
		name: (adminEdits?.name as string) ?? submission.name ?? '',
		age_years: (adminEdits?.age_years as number) ?? submission.age_years ?? 0,
		age_months: (adminEdits?.age_months as number) ?? submission.age_months ?? 0,
		geno: (adminEdits?.geno as string) ?? submission.geno ?? '',
		sex: (adminEdits?.sex as string) ?? submission.sex ?? '',
		herd_id:
			(adminEdits?.herd_id as number) ?? submission.herd_id ?? null,
		design_link:
			(adminEdits?.design_link as string) ??
			submission.design_link ??
			'',
	};
};

// Check if a field has been changed
const isFieldChanged = (field: keyof typeof adminForm.value): boolean => {
	if (!selectedSubmission.value) {
		return false;
	}
	const original = selectedSubmission.value[field] ?? null;
	const edited = adminForm.value[field] ?? null;
	return String(original || '') !== String(edited || '');
};

// Check if any fields have been changed
const hasAnyEdits = computed((): boolean => {
	if (!selectedSubmission.value) {
		return false;
	}
	return (
		isFieldChanged('name') ||
		isFieldChanged('age_years') ||
		isFieldChanged('age_months') ||
		isFieldChanged('geno') ||
		isFieldChanged('sex') ||
		isFieldChanged('herd_id') ||
		isFieldChanged('design_link')
	);
});

// Computed property to safely access selected submission
const currentSubmission = computed(() => selectedSubmission.value);

const unifiedRows = computed((): UnifiedRow[] => {
	const horseRows: UnifiedRow[] = props.submissions.map((submission) => ({
		key: `horse-${submission.id}`,
		kind: 'horse' as const,
		id: submission.id,
		user_id: submission.user_id,
		user_name: submission.user_name,
		name: submission.name,
		date_submitted: submission.date_submitted,
		status: submission.status,
		last_contact_date: submission.last_contact_date,
		last_admin_name: submission.last_admin_name,
		submission,
	}));

	const breedingRows: UnifiedRow[] = (props.breedingRequests?.data || []).map(
		(breeding) => ({
			key: `breeding-${breeding.id}`,
			kind: 'breeding' as const,
			id: breeding.id,
			user_id: null,
			user_name: breeding.requester_name ?? 'Unknown',
			name: `${breeding.sire_name ?? 'Sire'} × ${breeding.dam_name ?? 'Dam'}`,
			date_submitted: breeding.created_at ?? '',
			status: 'pending' as const,
			last_contact_date: null,
			last_admin_name: null,
			breeding,
		}),
	);

	return [...horseRows, ...breedingRows];
});

const filteredAndSorted = computed(() => {
	let result = [...unifiedRows.value];

	if (typeFilter.value !== 'all') {
		result = result.filter((item) => item.kind === typeFilter.value);
	}

	if (searchQuery.value.trim()) {
		const query = searchQuery.value.toLowerCase();
		result = result.filter(
			(item) =>
				item.user_name.toLowerCase().includes(query) ||
				item.name.toLowerCase().includes(query),
		);
	}

	if (statusFilter.value !== 'all') {
		result = result.filter((item) => item.status === statusFilter.value);
	}

	if (sortField.value && sortDirection.value) {
		result.sort((a, b) => {
			let aValue: string | Date;
			let bValue: string | Date;

			if (
				sortField.value === 'date_submitted' ||
				sortField.value === 'last_contact_date'
			) {
				aValue = new Date(a[sortField.value] || 0);
				bValue = new Date(b[sortField.value] || 0);
			} else {
				aValue = String(a[sortField.value]).toLowerCase();
				bValue = String(b[sortField.value]).toLowerCase();
			}

			if (aValue < bValue) {
				return sortDirection.value === 'asc' ? -1 : 1;
			}
			if (aValue > bValue) {
				return sortDirection.value === 'asc' ? 1 : -1;
			}
			return 0;
		});
	}

	return result;
});

const handleSort = (field: SortField): void => {
	if (sortField.value === field) {
		if (sortDirection.value === 'asc') {
			sortDirection.value = 'desc';
		} else if (sortDirection.value === 'desc') {
			sortDirection.value = null;
			sortField.value = 'date_submitted';
			sortDirection.value = 'desc';
		}
	} else {
		sortField.value = field;
		sortDirection.value = 'asc';
	}
};

const getSortIcon = (field: SortField) => {
	if (sortField.value !== field) {
		return ArrowUpDown;
	}
	if (sortDirection.value === 'asc') {
		return ArrowUp;
	}
	if (sortDirection.value === 'desc') {
		return ArrowDown;
	}
	return ArrowUpDown;
};

const formatDate = (dateString: string | null): string => {
	if (!dateString) {
		return '—';
	}
	return new Date(dateString).toLocaleDateString();
};

const formatDateTime = (dateString: string): string => {
	return new Date(dateString).toLocaleDateString('en-US', {
		year: 'numeric',
		month: 'short',
		day: 'numeric',
		hour: '2-digit',
		minute: '2-digit',
	});
};

const getStatusBadgeClass = (status: Status): string => {
	switch (status) {
		case 'pending':
			return 'bg-yellow-100 text-yellow-800';
		case 'contacted':
			return 'bg-blue-100 text-blue-800';
		case 'approved':
			return 'bg-green-100 text-green-800';
		case 'archived':
			return 'bg-gray-100 text-gray-800';
		default:
			return 'bg-gray-100 text-gray-800';
	}
};

const openReviewModal = (submission: Submission): void => {
	selectedSubmission.value = submission;
	reviewNotes.value = '';
	initializeAdminForm(submission);
	showReviewModal.value = true;
};

const closeReviewModal = (): void => {
	showReviewModal.value = false;
	selectedSubmission.value = null;
	reviewNotes.value = '';
};

const handleUnarchive = (submission: Submission): void => {
	router.post(route('admin.horses.unarchive', submission.id), {}, {
		onSuccess: () => router.reload(),
	});
};

const handleArchive = (): void => {
	if (!selectedSubmission.value) {
		return;
	}

	router.post(
		route('admin.horses.archive', selectedSubmission.value.id),
		{
			notes: reviewNotes.value,
		},
		{
			onSuccess: () => {
				closeReviewModal();
				router.reload();
			},
		},
	);
};

const handleContactOwner = (): void => {
	if (!selectedSubmission.value) {
		return;
	}

	const formData = {
		notes: reviewNotes.value,
		name: adminForm.value.name,
		age_years: adminForm.value.age_years,
		age_months: adminForm.value.age_months,
		geno: adminForm.value.geno,
		sex: adminForm.value.sex || null,
		herd_id: adminForm.value.herd_id,
		design_link: adminForm.value.design_link,
	};

	router.post(
		route('admin.horses.contact', selectedSubmission.value.id),
		formData,
		{
			onSuccess: () => {
				closeReviewModal();
				router.reload();
			},
		},
	);
};

const handleApprove = (): void => {
	if (!selectedSubmission.value) {
		return;
	}

	const formData = {
		name: adminForm.value.name,
		age_years: adminForm.value.age_years,
		age_months: adminForm.value.age_months,
		geno: adminForm.value.geno,
		sex: adminForm.value.sex || null,
		herd_id: adminForm.value.herd_id,
		design_link: adminForm.value.design_link,
	};

	// If it's an edit (has public_horse_id), use the approve endpoint
	if (
		selectedSubmission.value.is_edit &&
		selectedSubmission.value.public_horse_id
	) {
		router.post(
			route('horses.approve', selectedSubmission.value.id),
			formData,
			{
				onSuccess: () => {
					closeReviewModal();
					router.reload();
				},
			},
		);
	} else {
		// For new horses, publish them to make them public
		router.post(
			route('horses.publish', selectedSubmission.value.id),
			formData,
			{
				onSuccess: () => {
					closeReviewModal();
					router.reload();
				},
			},
		);
	}
};

const rollBreeding = (id: number): void => {
	router.post(route('admin.breeding-requests.roll', id), {}, { preserveScroll: true });
};

const rejectBreeding = (id: number): void => {
	router.post(route('admin.breeding-requests.reject', id), {}, { preserveScroll: true });
};
</script>

<template>
	<Card>
		<CardContent>
			<!-- Filters -->
			<div class="mb-6 flex flex-col gap-4 sm:flex-row">
				<div class="flex-1">
					<Input
						v-model="searchQuery"
						placeholder="Search by user name or herd/horse name..."
						class="w-full" />
				</div>
				<div class="w-full sm:w-48">
					<Select
						v-model="typeFilter"
						:options="[
							{
								value: 'all',
								label: 'All Types',
							},
							{
								value: 'horse',
								label: 'Horse',
							},
							{
								value: 'breeding',
								label: 'Breeding',
							},
						]"
						placeholder="Filter by type" />
				</div>
				<div class="w-full sm:w-48">
					<Select
						v-model="statusFilter"
						:options="[
							{
								value: 'all',
								label: 'All Statuses',
							},
							{
								value: 'pending',
								label: 'Pending',
							},
							{
								value: 'contacted',
								label: 'Contacted',
							},
							{
								value: 'approved',
								label: 'Approved',
							},
							{
								value: 'archived',
								label: 'Archived',
							},
						]"
						placeholder="Filter by status" />
				</div>
			</div>

			<!-- Table -->
			<div class="overflow-x-auto">
				<table class="w-full border-collapse">
					<thead>
						<tr class="border-b border-gray-200">
							<th class="text-cape-palliser-950 px-4 py-3 text-left text-sm font-semibold">
								Type
							</th>
							<th
								class="text-cape-palliser-950 cursor-pointer px-4 py-3 text-left text-sm font-semibold hover:bg-gray-50"
								@click="handleSort('user_name')">
								<div class="flex items-center gap-2">
									User Name
									<component
										:is="getSortIcon('user_name')"
										class="text-cape-palliser-500 h-4 w-4" />
								</div>
							</th>
							<th
								class="text-cape-palliser-950 cursor-pointer px-4 py-3 text-left text-sm font-semibold hover:bg-gray-50"
								@click="handleSort('name')">
								<div class="flex items-center gap-2">
									Submission
									<component
										:is="getSortIcon('name')"
										class="text-cape-palliser-500 h-4 w-4" />
								</div>
							</th>
							<th
								class="text-cape-palliser-950 cursor-pointer px-4 py-3 text-left text-sm font-semibold hover:bg-gray-50"
								@click="handleSort('date_submitted')">
								<div class="flex items-center gap-2">
									Date Submitted
									<component
										:is="getSortIcon('date_submitted')"
										class="text-cape-palliser-500 h-4 w-4" />
								</div>
							</th>
							<th
								class="text-cape-palliser-950 cursor-pointer px-4 py-3 text-left text-sm font-semibold hover:bg-gray-50"
								@click="handleSort('status')">
								<div class="flex items-center gap-2">
									Status
									<component
										:is="getSortIcon('status')"
										class="text-cape-palliser-500 h-4 w-4" />
								</div>
							</th>
							<th
								class="text-cape-palliser-950 cursor-pointer px-4 py-3 text-left text-sm font-semibold hover:bg-gray-50"
								@click="handleSort('last_contact_date')">
								<div class="flex items-center gap-2">
									Last Contact
									<component
										:is="getSortIcon('last_contact_date')"
										class="text-cape-palliser-500 h-4 w-4" />
								</div>
							</th>
							<th class="text-cape-palliser-950 px-4 py-3 text-left text-sm font-semibold">
								Last Admin
							</th>
							<th class="text-cape-palliser-950 px-4 py-3 text-left text-sm font-semibold"></th>
						</tr>
					</thead>
					<tbody>
						<tr
							v-if="filteredAndSorted.length === 0"
							class="border-b border-gray-200">
							<td
								colspan="8"
								class="text-cape-palliser-600 px-4 py-8 text-center">
								No submissions found
							</td>
						</tr>
						<template
							v-for="row in filteredAndSorted"
							:key="row.key">
							<tr
								v-if="row.kind === 'horse' && row.submission"
								:class="[
									'border-b border-gray-200 hover:bg-gray-50',
									row.submission.status === 'approved' ||
									row.submission.status === 'archived'
										? 'opacity-75'
										: '',
								]">
								<td class="text-cape-palliser-700 px-4 py-3 text-sm">
									<span class="inline-flex items-center rounded-full bg-shakespeare-50 px-2.5 py-0.5 text-xs font-medium text-shakespeare-700">
										Horse
									</span>
								</td>
								<td class="text-cape-palliser-950 px-4 py-3 text-sm">
									<Link
										:href="route('users.profile', row.submission.user_id)"
										class="hover:text-shakespeare-600 hover:underline">
										{{ row.submission.user_name }}
									</Link>
								</td>
								<td class="text-cape-palliser-950 px-4 py-3 text-sm">
									<div class="flex items-center gap-3">
										<div
											v-if="row.submission.design_link"
											class="flex-shrink-0">
											<img
												:src="row.submission.design_link"
												:alt="row.submission.name"
												class="h-12 w-12 rounded border border-gray-200 object-cover" />
										</div>
										<Link
											:href="
												row.submission.is_edit &&
												row.submission.public_horse_id
													? route(
															'horses.show',
															row.submission.public_horse_id,
														)
													: route('horses.show', row.submission.id)
											"
											class="hover:text-shakespeare-600 hover:underline">
											{{ row.submission.name }}
											<span
												v-if="row.submission.is_edit"
												class="text-cape-palliser-500 ml-1 text-xs">
												(Edit)
											</span>
										</Link>
									</div>
								</td>
								<td class="text-cape-palliser-700 px-4 py-3 text-sm">
									{{ formatDate(row.submission.date_submitted) }}
								</td>
								<td class="px-4 py-3 text-sm">
									<span
										:class="[
											'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
											getStatusBadgeClass(row.submission.status),
											row.submission.status === 'approved' ||
											row.submission.status === 'archived'
												? 'opacity-100'
												: '',
										]">
										{{
											row.submission.status
												.charAt(0)
												.toUpperCase() +
											row.submission.status.slice(1)
										}}
									</span>
								</td>
								<td class="text-cape-palliser-700 px-4 py-3 text-sm">
									{{ formatDate(row.submission.last_contact_date) }}
								</td>
								<td class="text-cape-palliser-700 px-4 py-3 text-sm">
									{{ row.submission.last_admin_name || '—' }}
								</td>
								<td class="flex gap-2 px-4 py-3 text-sm">
									<Button
										v-if="row.submission.status !== 'approved' && row.submission.status !== 'archived'"
										variant="outline"
										size="sm"
										@click="openReviewModal(row.submission)">
										Review
									</Button>
									<Button
										v-if="row.submission.status === 'archived'"
										variant="outline"
										size="sm"
										@click="handleUnarchive(row.submission)">
										<ArchiveRestore class="mr-1 h-4 w-4" />
										Unarchive
									</Button>
									<Button
										v-if="row.submission.status === 'archived'"
										variant="outline"
										size="sm"
										@click="openReviewModal(row.submission)">
										Review
									</Button>
									<span
										v-if="row.submission.status === 'approved'"
										class="text-cape-palliser-500 text-sm">
										Approved
									</span>
								</td>
							</tr>
							<tr
								v-else-if="row.kind === 'breeding' && row.breeding"
								class="border-b border-gray-200 hover:bg-gray-50">
								<td class="text-cape-palliser-700 px-4 py-3 text-sm">
									<span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-800">
										Breeding
									</span>
								</td>
								<td class="text-cape-palliser-950 px-4 py-3 text-sm">
									{{ row.breeding.requester_name }}
								</td>
								<td class="text-cape-palliser-950 px-4 py-3 text-sm">
									<div class="space-y-1">
										<p class="font-medium">
											{{ row.breeding.sire_name }}
											({{ row.breeding.sire_sex }}) ×
											{{ row.breeding.dam_name }}
											({{ row.breeding.dam_sex }})
										</p>
										<p class="font-mono text-xs text-cape-palliser-600">
											{{ row.breeding.sire_geno }} ·
											{{ row.breeding.dam_geno }}
										</p>
										<a
											:href="row.breeding.evidence_url"
											class="text-shakespeare-600 underline"
											target="_blank"
											rel="noopener">
											Evidence
										</a>
										<p
											v-if="row.breeding.notes"
											class="text-cape-palliser-600 text-xs">
											{{ row.breeding.notes }}
										</p>
									</div>
								</td>
								<td class="text-cape-palliser-700 px-4 py-3 text-sm">
									{{ formatDate(row.breeding.created_at ?? null) }}
								</td>
								<td class="px-4 py-3 text-sm">
									<span
										:class="[
											'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
											getStatusBadgeClass('pending'),
										]">
										Pending
									</span>
								</td>
								<td class="text-cape-palliser-700 px-4 py-3 text-sm">
									—
								</td>
								<td class="text-cape-palliser-700 px-4 py-3 text-sm">
									—
								</td>
								<td class="flex gap-2 px-4 py-3 text-sm">
									<Button
										size="sm"
										@click="rollBreeding(row.breeding.id)">
										Roll
									</Button>
									<Button
										size="sm"
										variant="outline"
										@click="rejectBreeding(row.breeding.id)">
										Reject
									</Button>
								</td>
							</tr>
						</template>
					</tbody>
				</table>
			</div>
		</CardContent>
	</Card>

	<!-- Review Modal -->
	<Dialog v-model:open="showReviewModal">
		<DialogContent class="flex max-h-[90vh] flex-col">
			<DialogHeader>
				<DialogTitle>
					Review Horse Submission
					<span
						v-if="currentSubmission"
						class="text-cape-palliser-700 text-sm font-normal">
						— {{ currentSubmission.name }}
					</span>
				</DialogTitle>
			</DialogHeader>

			<div class="flex-1 space-y-4 overflow-y-auto pr-2">
				<div v-if="currentSubmission">
					<!-- Horse Image Preview -->
					<div
						v-if="currentSubmission.design_link"
						class="mb-4 flex justify-center">
						<img
							:src="currentSubmission.design_link"
							:alt="currentSubmission.name"
							class="max-h-48 max-w-full rounded-lg border object-contain" />
					</div>

					<!-- Two Column Layout -->
					<div class="space-y-4">
						<!-- Headers -->
						<div class="grid grid-cols-2 gap-4">
							<h3 class="text-sm font-semibold text-gray-700">
								As Submitted
							</h3>
							<h3 class="text-sm font-semibold text-gray-700">
								Admin Edit
							</h3>
						</div>

						<!-- Owner Row (read-only on both sides) -->
						<div class="grid grid-cols-2 gap-4">
							<div>
								<Label class="text-xs text-gray-500">Owner</Label>
								<p class="mt-1 text-sm">
									<Link
										:href="
											route(
												'users.profile',
												currentSubmission.user_id,
											)
										"
										class="text-shakespeare-600 hover:underline">
										{{ currentSubmission.user_name }}
									</Link>
								</p>
							</div>
							<div>
								<Label class="text-xs text-gray-500">Owner</Label>
								<p class="mt-1 text-sm text-gray-400">
									(Not editable)
								</p>
							</div>
						</div>

						<!-- Horse Name Row -->
						<div
							:class="[
								'-m-1 grid grid-cols-2 gap-4 rounded border p-3 transition-colors',
								isFieldChanged('name')
									? 'border-red-200 bg-red-50'
									: 'border-transparent bg-transparent',
							]">
							<div>
								<Label class="text-xs text-gray-500">Horse Name</Label>
								<p class="mt-1 text-sm">
									{{ currentSubmission.name }}
								</p>
							</div>
							<div>
								<Label
									for="admin-name"
									class="text-xs text-gray-500">
									Horse Name</Label>
								<div class="mt-1">
									<Input
										id="admin-name"
										v-model="adminForm.name"
										type="text"
										class="w-full" />
								</div>
							</div>
						</div>

						<!-- Age Row -->
						<div
							:class="[
								'-m-1 grid grid-cols-2 gap-4 rounded border p-3 transition-colors',
								isFieldChanged('age_years') || isFieldChanged('age_months')
									? 'border-red-200 bg-red-50'
									: 'border-transparent bg-transparent',
							]">
							<div>
								<Label class="text-xs text-gray-500">Age</Label>
								<p class="mt-1 text-sm">
									{{ currentSubmission.formatted_age ?? '—' }}
								</p>
							</div>
							<div class="grid grid-cols-2 gap-2">
								<div>
									<Label
										for="admin-age-years"
										class="text-xs text-gray-500">
										Years</Label>
									<div class="mt-1">
										<Input
											id="admin-age-years"
											v-model.number="adminForm.age_years"
											type="number"
											min="0"
											max="50"
											class="w-full" />
									</div>
								</div>
								<div>
									<Label
										for="admin-age-months"
										class="text-xs text-gray-500">
										Months</Label>
									<div class="mt-1">
										<Input
											id="admin-age-months"
											v-model.number="adminForm.age_months"
											type="number"
											min="0"
											max="11"
											class="w-full" />
									</div>
								</div>
							</div>
						</div>

						<!-- Geno Row -->
						<div
							:class="[
								'-m-1 grid grid-cols-2 gap-4 rounded border p-3 transition-colors',
								isFieldChanged('geno')
									? 'border-red-200 bg-red-50'
									: 'border-transparent bg-transparent',
							]">
							<div>
								<Label class="text-xs text-gray-500">Geno</Label>
								<p class="mt-1 font-mono text-xs">
									{{ currentSubmission.geno ?? '—' }}
								</p>
							</div>
							<div>
								<Label
									for="admin-geno"
									class="text-xs text-gray-500">
									Geno</Label>
								<div class="mt-1">
									<Input
										id="admin-geno"
										v-model="adminForm.geno"
										type="text"
										class="w-full font-mono text-xs" />
								</div>
							</div>
						</div>

						<!-- Sex Row -->
						<div
							:class="[
								'-m-1 grid grid-cols-2 gap-4 rounded border p-3 transition-colors',
								isFieldChanged('sex')
									? 'border-red-200 bg-red-50'
									: 'border-transparent bg-transparent',
							]">
							<div>
								<Label class="text-xs text-gray-500">Sex</Label>
								<p class="mt-1 text-sm">
									{{ currentSubmission.sex ?? '—' }}
								</p>
							</div>
							<div>
								<Label
									for="admin-sex"
									class="text-xs text-gray-500">
									Sex</Label>
								<div class="mt-1">
									<select
										id="admin-sex"
										v-model="adminForm.sex"
										class="w-full rounded border px-2 py-1 text-sm">
										<option value="">Unset</option>
										<option value="mare">Mare</option>
										<option value="stallion">Stallion</option>
									</select>
								</div>
							</div>
						</div>

						<!-- Herd Row -->
						<div
							:class="[
								'-m-1 grid grid-cols-2 gap-4 rounded border p-3 transition-colors',
								isFieldChanged('herd_id')
									? 'border-red-200 bg-red-50'
									: 'border-transparent bg-transparent',
							]">
							<div>
								<Label class="text-xs text-gray-500">Herd</Label>
								<p class="mt-1 text-sm">
									{{
										currentSubmission?.herd_id
											? props.herds?.find(
													(h) =>
														h.id ===
														currentSubmission?.herd_id,
												)?.name || '—'
											: '—'
									}}
								</p>
							</div>
							<div>
								<Label
									for="admin-herd"
									class="text-xs text-gray-500">
									Herd</Label>
								<div class="mt-1">
									<Select
										id="admin-herd"
										v-model="adminForm.herd_id"
										:options="[
											{
												value: null,
												label: 'No herd',
											},
											...(props.herds || []).map((herd) => ({
												value: herd.id,
												label: herd.name,
											})),
										]"
										placeholder="Select a herd"
										class="w-full" />
								</div>
							</div>
						</div>

						<!-- Design Link Row -->
						<div
							:class="[
								'-m-1 grid grid-cols-2 gap-4 rounded border p-3 transition-colors',
								isFieldChanged('design_link')
									? 'border-red-200 bg-red-50'
									: 'border-transparent bg-transparent',
							]">
							<div>
								<Label class="text-xs text-gray-500">Design Link</Label>
								<p class="mt-1 text-sm break-all">
									{{ currentSubmission.design_link ?? '—' }}
								</p>
							</div>
							<div>
								<Label
									for="admin-design-link"
									class="text-xs text-gray-500">
									Design Link</Label>
								<div class="mt-1">
									<Input
										id="admin-design-link"
										v-model="adminForm.design_link"
										type="url"
										class="w-full text-xs" />
								</div>
							</div>
						</div>

						<!-- Type Row (read-only) -->
						<div class="grid grid-cols-2 gap-4">
							<div>
								<Label class="text-xs text-gray-500">Type</Label>
								<p class="mt-1 text-sm">
									{{
										currentSubmission.is_edit
											? 'Edit to existing horse'
											: 'New horse'
									}}
								</p>
							</div>
							<div>
								<Label class="text-xs text-gray-500">Type</Label>
								<p class="mt-1 text-sm text-gray-400">
									(Not editable)
								</p>
							</div>
						</div>

						<!-- Submitted Row (read-only) -->
						<div class="grid grid-cols-2 gap-4">
							<div>
								<Label class="text-xs text-gray-500">Submitted</Label>
								<p class="mt-1 text-sm">
									{{
										formatDate(
											currentSubmission.date_submitted,
										)
									}}
								</p>
							</div>
							<div>
								<Label class="text-xs text-gray-500">Submitted</Label>
								<p class="mt-1 text-sm text-gray-400">
									(Not editable)
								</p>
							</div>
						</div>
					</div>

					<div class="pt-4">
						<Link
							:href="
								currentSubmission?.is_edit &&
								currentSubmission?.public_horse_id
									? route(
											'horses.show',
											currentSubmission.public_horse_id,
										)
									: route(
											'horses.show',
											currentSubmission?.id || 0,
										)
							"
							target="_blank">
							<Button
								variant="outline"
								size="sm">
								View Horse
							</Button>
						</Link>
					</div>
				</div>
				<div>
					<Label for="review-notes">Admin Notes</Label>
					<textarea
						id="review-notes"
						v-model="reviewNotes"
						rows="6"
						class="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 mt-1 flex w-full min-w-0 rounded-md border bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
						placeholder="Add notes about this submission..." />
				</div>

				<!-- Comments Section -->
				<div
					v-if="
						currentSubmission?.message ||
						(currentSubmission?.comments &&
							currentSubmission.comments.length > 0)
					"
					class="space-y-4">
					<h3 class="text-sm font-semibold">
						Conversation
						<span
							v-if="
								currentSubmission?.comments &&
								currentSubmission.comments.length > 0
							"
							class="text-cape-palliser-500 font-normal">
							({{ currentSubmission.comments.length }})
						</span>
					</h3>

					<!-- Initial Message -->
					<div
						v-if="
							currentSubmission?.message?.initial_message
						"
						class="rounded-md border border-blue-200 bg-blue-50 p-4">
						<p class="text-sm font-medium text-gray-700">
							Initial Message:
						</p>
						<p class="mt-1 text-sm text-gray-600">
							{{ currentSubmission.message.initial_message }}
						</p>
					</div>

					<!-- Comments -->
					<div
						v-if="
							currentSubmission?.comments &&
							currentSubmission.comments.length > 0
						"
						class="space-y-3">
						<div
							v-for="comment in currentSubmission.comments"
							:key="comment.id"
							:class="[
								'rounded-md border p-4',
								comment.user.is_staff
									? 'border-blue-200 bg-blue-50'
									: 'border-gray-200 bg-gray-50',
							]">
							<div class="flex items-start justify-between">
								<div class="flex-1">
									<p class="text-sm font-medium">
										{{ comment.user.name }}
										<span
											v-if="comment.user.is_staff"
											class="text-xs text-blue-600">
											(Staff)
										</span>
										<span
											v-else
											class="text-xs text-gray-600">
											(Owner)
										</span>
									</p>
									<p class="mt-1 text-sm text-gray-700">
										{{ comment.body }}
									</p>
								</div>
								<p class="text-cape-palliser-500 ml-4 text-xs">
									{{ formatDateTime(comment.created_at) }}
								</p>
							</div>
						</div>
					</div>

					<div
						v-else-if="currentSubmission?.message"
						class="rounded-md border border-gray-200 bg-gray-50 p-4 text-center text-sm text-gray-500">
						No comments yet.
					</div>
				</div>

				<!-- Warning if edits have been made -->
				<div
					v-if="hasAnyEdits"
					class="rounded-md border border-yellow-200 bg-yellow-50 p-4">
					<div class="flex">
						<div class="flex-shrink-0">
							<svg
								class="h-5 w-5 text-yellow-400"
								viewBox="0 0 20 20"
								fill="currentColor">
								<path
									fill-rule="evenodd"
									d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
									clip-rule="evenodd" />
							</svg>
						</div>
						<div class="ml-3">
							<h3 class="text-sm font-medium text-yellow-800">
								Edits Require Owner Acceptance
							</h3>
							<div class="mt-2 text-sm text-yellow-700">
								<p>
									You have made edits to this submission. The
									owner must accept these changes before the
									horse can be published. Use "Contact Owner"
									to notify them of the edits.
								</p>
							</div>
						</div>
					</div>
				</div>
			</div>

			<DialogFooter class="gap-2">
				<Button
					v-if="selectedSubmission?.status !== 'approved'"
					variant="outline"
					@click="handleArchive">
					Archive
				</Button>
				<Button
					v-if="selectedSubmission?.status !== 'approved'"
					variant="outline"
					@click="handleContactOwner">
					Contact Owner
				</Button>
				<Button
					v-if="selectedSubmission?.status !== 'approved'"
					:disabled="hasAnyEdits"
					@click="handleApprove">
					{{
						selectedSubmission?.is_edit
							? 'Approve Changes'
							: 'Publish Horse'
					}}
				</Button>
				<Button
					v-else
					variant="outline"
					@click="closeReviewModal">
					Close
				</Button>
			</DialogFooter>
		</DialogContent>
	</Dialog>
</template>
