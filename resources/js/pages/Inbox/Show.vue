<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
	Dialog,
	DialogContent,
	DialogFooter,
	DialogHeader,
	DialogTitle,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Horse {
	id: number;
	name: string;
	age?: number | null;
	geno?: string | null;
	herd_id?: number | null;
	design_link?: string | null;
	public_horse_id?: number | null;
	is_edit?: boolean;
}

interface Admin {
	id: number;
	name: string;
}

interface User {
	id: number;
	name: string;
	is_admin: boolean;
}

interface Comment {
	id: number;
	body: string;
	created_at: string;
	user: User;
}

interface Message {
	id: number;
	subject: string;
	initial_message?: string | null;
	is_read: boolean;
	status: 'pending' | 'accepted' | 'declined';
	created_at: string;
	horse: Horse;
	admin: Admin;
	admin_edits?: Record<string, unknown> | null;
	comments: Comment[];
}

interface Props {
	message: Message;
	herds?: Array<{ id: number; name: string }>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
	{
		title: 'Inbox',
		href: '/inbox',
	},
	{
		title: props.message.subject,
		href: `/inbox/${props.message.id}`,
	},
];

const commentBody = ref('');
const showDeclineDialog = ref(false);
const declineReason = ref('');

const isPending = computed(() => props.message.status === 'pending');
const hasAdminEdits = computed(
	() => props.message.admin_edits !== null && props.message.admin_edits !== undefined,
);

const formatDate = (dateString: string): string => {
	return new Date(dateString).toLocaleDateString('en-US', {
		year: 'numeric',
		month: 'short',
		day: 'numeric',
		hour: '2-digit',
		minute: '2-digit',
	});
};

const submitComment = (): void => {
	if (!commentBody.value.trim()) {
		return;
	}

	router.post(
		route('inbox.comments.store', props.message.id),
		{
			body: commentBody.value,
		},
		{
			onSuccess: () => {
				commentBody.value = '';
			},
		},
	);
};

const acceptEdits = (): void => {
	router.post(route('inbox.accept', props.message.id), {}, {
		onSuccess: () => {
			router.reload();
		},
	});
};

const declineEdits = (): void => {
	if (!declineReason.value.trim()) {
		return;
	}

	router.post(
		route('inbox.decline', props.message.id),
		{
			reason: declineReason.value,
		},
		{
			onSuccess: () => {
				showDeclineDialog.value = false;
				declineReason.value = '';
				router.reload();
			},
		},
	);
};

const getFieldValue = (field: string): string => {
	if (!props.message.admin_edits) {
		return '—';
	}
	const value = props.message.admin_edits[field];
	return value !== null && value !== undefined ? String(value) : '—';
};
</script>

<template>
	<Head :title="message.subject" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="space-y-6 p-6">
			<Card>
				<CardHeader>
					<CardTitle>{{ message.subject }}</CardTitle>
					<div class="mt-2 flex items-center gap-4 text-sm text-gray-600">
						<p>From: {{ message.admin.name }}</p>
						<p>•</p>
						<p>{{ formatDate(message.created_at) }}</p>
						<span
							:class="[
								'ml-auto inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
								message.status === 'pending'
									? 'bg-yellow-100 text-yellow-800'
									: message.status === 'accepted'
										? 'bg-green-100 text-green-800'
										: 'bg-red-100 text-red-800',
							]">
							{{
								message.status.charAt(0).toUpperCase() +
								message.status.slice(1)
							}}
						</span>
					</div>
				</CardHeader>
				<CardContent class="space-y-6">
					<!-- Horse Info -->
					<div class="flex items-start gap-4">
						<div
							v-if="message.horse.design_link"
							class="flex-shrink-0">
							<img
								:src="message.horse.design_link"
								:alt="message.horse.name"
								class="h-32 w-32 rounded border border-gray-200 object-cover" />
						</div>
						<div class="flex-1">
							<p class="text-lg font-semibold">
								<Link
									:href="
										message.horse.is_edit &&
										message.horse.public_horse_id
											? route(
													'horses.show',
													message.horse
														.public_horse_id,
												)
											: route(
													'horses.show',
													message.horse.id,
												)
									"
									class="text-shakespeare-600 hover:underline">
									{{ message.horse.name }}
								</Link>
								<span
									v-if="message.horse.is_edit"
									class="text-cape-palliser-500 ml-1 text-xs">
									(Edit)
								</span>
							</p>
						</div>
					</div>

					<!-- Initial Message -->
					<div
						v-if="message.initial_message"
						class="rounded-md border border-gray-200 bg-gray-50 p-4">
						<p class="text-sm font-medium text-gray-700">
							Message:
						</p>
						<p class="mt-1 text-sm text-gray-600">
							{{ message.initial_message }}
						</p>
					</div>

					<!-- Admin Edits Comparison -->
					<div
						v-if="hasAdminEdits"
						class="rounded-md border border-yellow-200 bg-yellow-50 p-4">
						<h3 class="mb-4 text-sm font-semibold text-yellow-900">
							Admin Edits
						</h3>
						<div class="grid grid-cols-2 gap-4 text-sm">
							<div>
								<Label class="text-xs text-gray-500">
									Original Name
								</Label>
								<p class="mt-1">{{ message.horse.name }}</p>
							</div>
							<div>
								<Label class="text-xs text-gray-500">
									Edited Name
								</Label>
								<p
									:class="[
										'mt-1',
										getFieldValue('name') !==
											message.horse.name
											? 'font-semibold text-red-600'
											: '',
									]">
									{{ getFieldValue('name') }}
								</p>
							</div>
							<div>
								<Label class="text-xs text-gray-500">
									Original Age
								</Label>
								<p class="mt-1">
									{{ message.horse.age ?? '—' }}
								</p>
							</div>
							<div>
								<Label class="text-xs text-gray-500">
									Edited Age
								</Label>
								<p
									:class="[
										'mt-1',
										getFieldValue('age') !==
											String(message.horse.age ?? '')
											? 'font-semibold text-red-600'
											: '',
									]">
									{{ getFieldValue('age') }}
								</p>
							</div>
							<div>
								<Label class="text-xs text-gray-500">
									Original Geno
								</Label>
								<p class="mt-1 font-mono text-xs">
									{{ message.horse.geno ?? '—' }}
								</p>
							</div>
							<div>
								<Label class="text-xs text-gray-500">
									Edited Geno
								</Label>
								<p
									:class="[
										'mt-1 font-mono text-xs',
										getFieldValue('geno') !==
											(message.horse.geno ?? '')
											? 'font-semibold text-red-600'
											: '',
									]">
									{{ getFieldValue('geno') }}
								</p>
							</div>
							<div>
								<Label class="text-xs text-gray-500">
									Original Design Link
								</Label>
								<p class="mt-1 break-all text-xs">
									{{ message.horse.design_link ?? '—' }}
								</p>
							</div>
							<div>
								<Label class="text-xs text-gray-500">
									Edited Design Link
								</Label>
								<p
									:class="[
										'mt-1 break-all text-xs',
										getFieldValue('design_link') !==
											(message.horse.design_link ?? '')
											? 'font-semibold text-red-600'
											: '',
									]">
									{{ getFieldValue('design_link') }}
								</p>
							</div>
						</div>
					</div>

					<!-- Comments Section -->
					<div class="space-y-4">
						<h3 class="text-sm font-semibold">
							Comments
							<span
								v-if="message.comments.length > 0"
								class="text-cape-palliser-500 font-normal">
								({{ message.comments.length }})
							</span>
						</h3>

						<div
							v-if="message.comments.length === 0"
							class="rounded-md border border-gray-200 bg-gray-50 p-4 text-center text-sm text-gray-500">
							No comments yet.
						</div>

						<div
							v-else
							class="space-y-4">
							<div
								v-for="comment in message.comments"
								:key="comment.id"
								:class="[
									'rounded-md border p-4',
									comment.user.is_admin
										? 'border-blue-200 bg-blue-50'
										: 'border-gray-200 bg-gray-50',
								]">
								<div class="flex items-start justify-between">
									<div class="flex-1">
										<p class="text-sm font-medium">
											{{ comment.user.name }}
											<span
												v-if="comment.user.is_admin"
												class="text-blue-600 text-xs">
												(Admin)
											</span>
										</p>
										<p class="mt-1 text-sm text-gray-700">
											{{ comment.body }}
										</p>
									</div>
									<p class="text-cape-palliser-500 ml-4 text-xs">
										{{ formatDate(comment.created_at) }}
									</p>
								</div>
							</div>
						</div>

						<!-- Add Comment Form -->
						<div class="space-y-2">
							<Label for="comment">Add a comment</Label>
							<div class="flex gap-2">
								<Input
									id="comment"
									v-model="commentBody"
									type="text"
									placeholder="Type your comment..."
									@keyup.enter="submitComment" />
								<Button
									@click="submitComment"
									:disabled="!commentBody.trim()">
									Send
								</Button>
							</div>
						</div>
					</div>

					<!-- Action Buttons -->
					<div
						v-if="isPending && hasAdminEdits"
						class="flex gap-4 border-t pt-4">
						<Button @click="acceptEdits" variant="default">
							Accept Edits
						</Button>
						<Button
							@click="showDeclineDialog = true"
							variant="outline">
							Decline Edits
						</Button>
					</div>
				</CardContent>
			</Card>
		</div>

		<!-- Decline Dialog -->
		<Dialog v-model:open="showDeclineDialog">
			<DialogContent>
				<DialogHeader>
					<DialogTitle>Decline Admin Edits</DialogTitle>
				</DialogHeader>
				<div class="space-y-4">
					<p class="text-sm text-gray-600">
						Please provide a reason for declining these edits
						(optional):
					</p>
					<Label for="decline-reason">Reason</Label>
					<Input
						id="decline-reason"
						v-model="declineReason"
						type="text"
						placeholder="Optional reason..." />
				</div>
				<DialogFooter>
					<Button
						variant="outline"
						@click="showDeclineDialog = false">
						Cancel
					</Button>
					<Button @click="declineEdits">Decline</Button>
				</DialogFooter>
			</DialogContent>
		</Dialog>
	</AppLayout>
</template>
