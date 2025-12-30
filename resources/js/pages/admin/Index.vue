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
import { Head, Link } from '@inertiajs/vue3';
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
	user_name: string;
	name: string;
	name_type: 'herd' | 'horse';
	date_submitted: string;
	status: Status;
	last_contact_date: string | null;
}

const mockSubmissions: Submission[] = [
	{
		id: 1,
		user_name: 'HorseLover123',
		name: 'Thunder Herd',
		name_type: 'herd',
		date_submitted: '2024-01-15T10:30:00Z',
		status: 'pending',
		last_contact_date: null,
	},
	{
		id: 2,
		user_name: 'WildRider99',
		name: 'Storm Runner',
		name_type: 'horse',
		date_submitted: '2024-01-14T14:20:00Z',
		status: 'contacted',
		last_contact_date: '2024-01-16T09:15:00Z',
	},
	{
		id: 3,
		user_name: 'PrairieDreams',
		name: 'Wild Breeze',
		name_type: 'herd',
		date_submitted: '2024-01-13T08:45:00Z',
		status: 'approved',
		last_contact_date: '2024-01-18T11:30:00Z',
	},
	{
		id: 4,
		user_name: 'MidnightStallion',
		name: 'Midnight Shadow',
		name_type: 'horse',
		date_submitted: '2024-01-12T16:00:00Z',
		status: 'pending',
		last_contact_date: null,
	},
	{
		id: 5,
		user_name: 'DesertWind456',
		name: 'Prairie Wind',
		name_type: 'herd',
		date_submitted: '2024-01-11T12:30:00Z',
		status: 'contacted',
		last_contact_date: '2024-01-17T10:00:00Z',
	},
	{
		id: 6,
		user_name: 'ThunderHooves',
		name: 'Lightning Strike',
		name_type: 'horse',
		date_submitted: '2024-01-10T09:15:00Z',
		status: 'archived',
		last_contact_date: '2024-01-20T14:00:00Z',
	},
	{
		id: 7,
		user_name: 'MountainView87',
		name: 'Highland Herd',
		name_type: 'herd',
		date_submitted: '2024-01-09T11:20:00Z',
		status: 'archived',
		last_contact_date: '2024-01-19T16:30:00Z',
	},
];

const searchQuery = ref('');
const statusFilter = ref<Status | 'all'>('all');
const sortField = ref<SortField>('date_submitted');
const sortDirection = ref<SortDirection>('desc');

const showReviewModal = ref(false);
const selectedSubmission = ref<Submission | null>(null);
const reviewNotes = ref('');

const filteredAndSorted = computed(() => {
	let result = [...mockSubmissions];

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
	// TODO: Implement archive functionality
	closeReviewModal();
};

const handleContactOwner = (): void => {
	// TODO: Implement contact owner functionality
	closeReviewModal();
};

const handleApprove = (): void => {
	// TODO: Implement approve functionality
	closeReviewModal();
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
										colspan="6"
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
											? 'opacity-50'
											: '',
									]">
									<td
										class="text-cape-palliser-950 px-4 py-3 text-sm">
										<Link
											href="#"
											class="hover:text-shakespeare-600 hover:underline">
											{{
												submission.user_name
											}}
										</Link>
									</td>
									<td
										class="text-cape-palliser-950 px-4 py-3 text-sm">
										<Link
											href="#"
											class="hover:text-shakespeare-600 hover:underline">
											{{ submission.name }}
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
									<td class="px-4 py-3 text-sm">
										<Button
											variant="outline"
											size="sm"
											@click="
												openReviewModal(
													submission,
												)
											">
											Review
										</Button>
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
						Review Submission
						<span
							v-if="selectedSubmission"
							class="text-cape-palliser-700 text-sm font-normal">
							— {{ selectedSubmission.user_name }}
						</span>
					</DialogTitle>
				</DialogHeader>

				<div class="space-y-4">
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
						variant="outline"
						@click="handleArchive">
						Archive
					</Button>
					<Button
						variant="outline"
						@click="handleContactOwner">
						Contact Owner
					</Button>
					<Button @click="handleApprove"> Approve </Button>
				</DialogFooter>
			</DialogContent>
		</Dialog>
	</AppLayout>
</template>
