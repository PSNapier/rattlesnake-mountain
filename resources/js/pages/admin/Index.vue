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
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type Status = 'pending' | 'contacted' | 'approved' | 'archived';
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
	age?: number;
	geno?: string;
	herd_id?: number | null;
}

interface Props {
	submissions: Submission[];
	herds?: Herd[];
}

interface Props {
	submissions: Submission[];
}

const props = defineProps<Props>();

const searchQuery = ref('');
const statusFilter = ref<Status | 'all'>('all');
const sortField = ref<SortField>('date_submitted');
const sortDirection = ref<SortDirection>('desc');

const showReviewModal = ref(false);
const selectedSubmission = ref<Submission | null>(null);
const reviewNotes = ref('');

// Admin-editable form state
const adminForm = ref({
	name: '',
	age: 0,
	geno: '',
	herd_id: null as number | null,
	design_link: '',
});

// Initialize admin form when submission is selected
const initializeAdminForm = (submission: Submission): void => {
	adminForm.value = {
		name: submission.name || '',
		age: submission.age || 0,
		geno: submission.geno || '',
		herd_id: submission.herd_id || null,
		design_link: submission.design_link || '',
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
		isFieldChanged('age') ||
		isFieldChanged('geno') ||
		isFieldChanged('herd_id') ||
		isFieldChanged('design_link')
	);
});

// Computed property to safely access selected submission
const currentSubmission = computed(() => selectedSubmission.value);

const filteredAndSorted = computed(() => {
	let result = [...props.submissions];

	// Filter by search query
	if (searchQuery.value.trim()) {
		const query = searchQuery.value.toLowerCase();
		result = result.filter(
			(item) =>
				item.user_name.toLowerCase().includes(query) ||
				item.name.toLowerCase().includes(query),
		);
	}

	// Filter by status
	if (statusFilter.value !== 'all') {
		result = result.filter((item) => item.status === statusFilter.value);
	}

	// Sort
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

	router.post(
		route('admin.horses.contact', selectedSubmission.value.id),
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

const handleApprove = (): void => {
	if (!selectedSubmission.value) {
		return;
	}

	const formData = {
		name: adminForm.value.name,
		age: adminForm.value.age,
		geno: adminForm.value.geno,
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
</script>

<template>
	<Head title="Admin" />

	<AppLayout>
		<div class="space-y-6 p-6">
			<div>
				<h1 class="text-cape-palliser-950 text-3xl font-bold">
					Admin
				</h1>
				<p class="text-cape-palliser-700 mt-2">
					Welcome to the admin area.
				</p>
			</div>

			<Card>
				<CardHeader>
					<CardTitle>Submissions</CardTitle>
				</CardHeader>
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
									<th
										class="text-cape-palliser-950 cursor-pointer px-4 py-3 text-left text-sm font-semibold hover:bg-gray-50"
										@click="
											handleSort('user_name')
										">
										<div
											class="flex items-center gap-2">
											User Name
											<component
												:is="
													getSortIcon(
														'user_name',
													)
												"
												class="text-cape-palliser-500 h-4 w-4" />
										</div>
									</th>
									<th
										class="text-cape-palliser-950 cursor-pointer px-4 py-3 text-left text-sm font-semibold hover:bg-gray-50"
										@click="handleSort('name')">
										<div
											class="flex items-center gap-2">
											Submission
											<component
												:is="
													getSortIcon(
														'name',
													)
												"
												class="text-cape-palliser-500 h-4 w-4" />
										</div>
									</th>
									<th
										class="text-cape-palliser-950 cursor-pointer px-4 py-3 text-left text-sm font-semibold hover:bg-gray-50"
										@click="
											handleSort(
												'date_submitted',
											)
										">
										<div
											class="flex items-center gap-2">
											Date Submitted
											<component
												:is="
													getSortIcon(
														'date_submitted',
													)
												"
												class="text-cape-palliser-500 h-4 w-4" />
										</div>
									</th>
									<th
										class="text-cape-palliser-950 cursor-pointer px-4 py-3 text-left text-sm font-semibold hover:bg-gray-50"
										@click="handleSort('status')">
										<div
											class="flex items-center gap-2">
											Status
											<component
												:is="
													getSortIcon(
														'status',
													)
												"
												class="text-cape-palliser-500 h-4 w-4" />
										</div>
									</th>
									<th
										class="text-cape-palliser-950 cursor-pointer px-4 py-3 text-left text-sm font-semibold hover:bg-gray-50"
										@click="
											handleSort(
												'last_contact_date',
											)
										">
										<div
											class="flex items-center gap-2">
											Last Contact
											<component
												:is="
													getSortIcon(
														'last_contact_date',
													)
												"
												class="text-cape-palliser-500 h-4 w-4" />
										</div>
									</th>
									<th
										class="text-cape-palliser-950 px-4 py-3 text-left text-sm font-semibold">
										Last Admin
									</th>
									<th
										class="text-cape-palliser-950 px-4 py-3 text-left text-sm font-semibold"></th>
								</tr>
							</thead>
							<tbody>
								<tr
									v-if="
										filteredAndSorted.length === 0
									"
									class="border-b border-gray-200">
									<td
										colspan="7"
										class="text-cape-palliser-600 px-4 py-8 text-center">
										No submissions found
									</td>
								</tr>
								<tr
									v-for="submission in filteredAndSorted"
									:key="submission.id"
									:class="[
										'border-b border-gray-200 hover:bg-gray-50',
										submission.status ===
											'approved' ||
										submission.status ===
											'archived'
											? 'opacity-75'
											: '',
									]">
									<td
										class="text-cape-palliser-950 px-4 py-3 text-sm">
										<Link
											:href="
												route(
													'users.profile',
													submission.user_id,
												)
											"
											class="hover:text-shakespeare-600 hover:underline">
											{{
												submission.user_name
											}}
										</Link>
									</td>
									<td
										class="text-cape-palliser-950 px-4 py-3 text-sm">
										<div
											class="flex items-center gap-3">
											<div
												v-if="
													submission.design_link
												"
												class="flex-shrink-0">
												<img
													:src="
														submission.design_link
													"
													:alt="
														submission.name
													"
													class="h-12 w-12 rounded border border-gray-200 object-cover" />
											</div>
											<Link
												:href="
													submission.is_edit &&
													submission.public_horse_id
														? route(
																'horses.show',
																submission.public_horse_id,
															)
														: route(
																'horses.show',
																submission.id,
															)
												"
												class="hover:text-shakespeare-600 hover:underline">
												{{
													submission.name
												}}
												<span
													v-if="
														submission.is_edit
													"
													class="text-cape-palliser-500 ml-1 text-xs">
													(Edit)
												</span>
											</Link>
										</div>
									</td>
									<td
										class="text-cape-palliser-700 px-4 py-3 text-sm">
										{{
											formatDate(
												submission.date_submitted,
											)
										}}
									</td>
									<td class="px-4 py-3 text-sm">
										<span
											:class="[
												'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
												getStatusBadgeClass(
													submission.status,
												),
												submission.status ===
													'approved' ||
												submission.status ===
													'archived'
													? 'opacity-100'
													: '',
											]">
											{{
												submission.status
													.charAt(0)
													.toUpperCase() +
												submission.status.slice(
													1,
												)
											}}
										</span>
									</td>
									<td
										class="text-cape-palliser-700 px-4 py-3 text-sm">
										{{
											formatDate(
												submission.last_contact_date,
											)
										}}
									</td>
									<td
										class="text-cape-palliser-700 px-4 py-3 text-sm">
										{{
											submission.last_admin_name ||
											'—'
										}}
									</td>
									<td class="px-4 py-3 text-sm">
										<Button
											v-if="
												submission.status !==
												'approved'
											"
											variant="outline"
											size="sm"
											@click="
												openReviewModal(
													submission,
												)
											">
											Review
										</Button>
										<span
											v-else
											class="text-cape-palliser-500 text-sm">
											Approved
										</span>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</CardContent>
			</Card>
		</div>

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
								<h3
									class="text-sm font-semibold text-gray-700">
									As Submitted
								</h3>
								<h3
									class="text-sm font-semibold text-gray-700">
									Admin Edit
								</h3>
							</div>

							<!-- Owner Row (read-only on both sides) -->
							<div class="grid grid-cols-2 gap-4">
								<div>
									<Label
										class="text-xs text-gray-500"
										>Owner</Label
									>
									<p class="mt-1 text-sm">
										<Link
											:href="
												route(
													'users.profile',
													currentSubmission.user_id,
												)
											"
											class="text-shakespeare-600 hover:underline">
											{{
												currentSubmission.user_name
											}}
										</Link>
									</p>
								</div>
								<div>
									<Label
										class="text-xs text-gray-500"
										>Owner</Label
									>
									<p
										class="mt-1 text-sm text-gray-400">
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
									<Label
										class="text-xs text-gray-500"
										>Horse Name</Label
									>
									<p class="mt-1 text-sm">
										{{ currentSubmission.name }}
									</p>
								</div>
								<div>
									<Label
										for="admin-name"
										class="text-xs text-gray-500"
										>Horse Name</Label
									>
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
									isFieldChanged('age')
										? 'border-red-200 bg-red-50'
										: 'border-transparent bg-transparent',
								]">
								<div>
									<Label
										class="text-xs text-gray-500"
										>Age</Label
									>
									<p class="mt-1 text-sm">
										{{
											currentSubmission.age ??
											'—'
										}}
									</p>
								</div>
								<div>
									<Label
										for="admin-age"
										class="text-xs text-gray-500"
										>Age</Label
									>
									<div class="mt-1">
										<Input
											id="admin-age"
											v-model.number="
												adminForm.age
											"
											type="number"
											min="0"
											max="50"
											class="w-full" />
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
									<Label
										class="text-xs text-gray-500"
										>Geno</Label
									>
									<p class="mt-1 font-mono text-xs">
										{{
											currentSubmission.geno ??
											'—'
										}}
									</p>
								</div>
								<div>
									<Label
										for="admin-geno"
										class="text-xs text-gray-500"
										>Geno</Label
									>
									<div class="mt-1">
										<Input
											id="admin-geno"
											v-model="adminForm.geno"
											type="text"
											class="w-full font-mono text-xs" />
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
									<Label
										class="text-xs text-gray-500"
										>Herd</Label
									>
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
										class="text-xs text-gray-500"
										>Herd</Label
									>
									<div class="mt-1">
										<Select
											id="admin-herd"
											v-model="
												adminForm.herd_id
											"
											:options="[
												{
													value: null,
													label: 'No herd',
												},
												...(
													props.herds ||
													[]
												).map((herd) => ({
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
									<Label
										class="text-xs text-gray-500"
										>Design Link</Label
									>
									<p class="mt-1 text-sm break-all">
										{{
											currentSubmission.design_link ??
											'—'
										}}
									</p>
								</div>
								<div>
									<Label
										for="admin-design-link"
										class="text-xs text-gray-500"
										>Design Link</Label
									>
									<div class="mt-1">
										<Input
											id="admin-design-link"
											v-model="
												adminForm.design_link
											"
											type="url"
											class="w-full text-xs" />
									</div>
								</div>
							</div>

							<!-- Type Row (read-only) -->
							<div class="grid grid-cols-2 gap-4">
								<div>
									<Label
										class="text-xs text-gray-500"
										>Type</Label
									>
									<p class="mt-1 text-sm">
										{{
											currentSubmission.is_edit
												? 'Edit to existing horse'
												: 'New horse'
										}}
									</p>
								</div>
								<div>
									<Label
										class="text-xs text-gray-500"
										>Type</Label
									>
									<p
										class="mt-1 text-sm text-gray-400">
										(Not editable)
									</p>
								</div>
							</div>

							<!-- Submitted Row (read-only) -->
							<div class="grid grid-cols-2 gap-4">
								<div>
									<Label
										class="text-xs text-gray-500"
										>Submitted</Label
									>
									<p class="mt-1 text-sm">
										{{
											formatDate(
												currentSubmission.date_submitted,
											)
										}}
									</p>
								</div>
								<div>
									<Label
										class="text-xs text-gray-500"
										>Submitted</Label
									>
									<p
										class="mt-1 text-sm text-gray-400">
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
												currentSubmission?.id ||
													0,
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
								<h3
									class="text-sm font-medium text-yellow-800">
									Edits Require Owner Acceptance
								</h3>
								<div
									class="mt-2 text-sm text-yellow-700">
									<p>
										You have made edits to this
										submission. The owner must
										accept these changes before
										the horse can be published.
										Use "Contact Owner" to notify
										them of the edits.
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
	</AppLayout>
</template>
