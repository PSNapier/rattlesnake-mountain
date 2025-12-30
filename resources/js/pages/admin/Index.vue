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

	// If it's an edit (has public_horse_id), use the approve endpoint
	if (
		selectedSubmission.value.is_edit &&
		selectedSubmission.value.public_horse_id
	) {
		router.post(
			route('horses.approve', selectedSubmission.value.id),
			{},
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
			{},
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
											{{ submission.name }}
											<span
												v-if="
													submission.is_edit
												"
												class="text-cape-palliser-500 ml-1 text-xs">
												(Edit)
											</span>
										</Link>
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
			<DialogContent>
				<DialogHeader>
					<DialogTitle>
						Review Horse Submission
						<span
							v-if="selectedSubmission"
							class="text-cape-palliser-700 text-sm font-normal">
							— {{ selectedSubmission.name }}
						</span>
					</DialogTitle>
				</DialogHeader>

				<div class="space-y-4">
					<div v-if="selectedSubmission">
						<div class="mb-4 space-y-2 text-sm">
							<p>
								<strong>Owner:</strong>
								<Link
									:href="
										route(
											'users.profile',
											selectedSubmission.user_id,
										)
									"
									class="text-shakespeare-600 hover:underline">
									{{ selectedSubmission.user_name }}
								</Link>
							</p>
							<p>
								<strong>Horse Name:</strong>
								{{ selectedSubmission.name }}
							</p>
							<p>
								<strong>Type:</strong>
								{{
									selectedSubmission.is_edit
										? 'Edit to existing horse'
										: 'New horse'
								}}
							</p>
							<p>
								<strong>Submitted:</strong>
								{{
									formatDate(
										selectedSubmission.date_submitted,
									)
								}}
							</p>
							<div class="pt-2">
								<Link
									:href="
										selectedSubmission.is_edit &&
										selectedSubmission.public_horse_id
											? route(
													'horses.show',
													selectedSubmission.public_horse_id,
												)
											: route(
													'horses.show',
													selectedSubmission.id,
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
