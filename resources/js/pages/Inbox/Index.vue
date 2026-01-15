<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Horse {
	id: number;
	name: string;
	design_link?: string | null;
	public_horse_id?: number | null;
	is_edit?: boolean;
}

interface Admin {
	id: number;
	name: string;
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
	comment_count: number;
	latest_comment_at?: string | null;
}

interface Props {
	messages: Message[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
	{
		title: 'Inbox',
		href: '/inbox',
	},
];

const getStatusBadgeClass = (status: string): string => {
	switch (status) {
		case 'pending':
			return 'bg-yellow-100 text-yellow-800';
		case 'accepted':
			return 'bg-green-100 text-green-800';
		case 'declined':
			return 'bg-red-100 text-red-800';
		default:
			return 'bg-gray-100 text-gray-800';
	}
};

const formatDate = (dateString: string): string => {
	return new Date(dateString).toLocaleDateString('en-US', {
		year: 'numeric',
		month: 'short',
		day: 'numeric',
		hour: '2-digit',
		minute: '2-digit',
	});
};
</script>

<template>
	<Head title="Inbox" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="space-y-6 p-6">
			<div>
				<h1 class="text-cape-palliser-950 text-3xl font-bold">
					Inbox
				</h1>
				<p class="text-cape-palliser-700 mt-2">
					Review admin submissions and communicate with admins.
				</p>
			</div>

			<div
				v-if="props.messages.length === 0"
				class="py-12 text-center">
				<p class="text-lg text-gray-500">No messages found.</p>
			</div>

			<div
				v-else
				class="space-y-4">
				<Card
					v-for="message in props.messages"
					:key="message.id"
					:class="[
						'transition-shadow hover:shadow-lg',
						!message.is_read
							? 'border-l-4 border-l-blue-500'
							: '',
					]">
					<CardHeader>
						<div class="flex items-start justify-between">
							<div class="flex-1">
								<CardTitle
									class="flex items-center gap-2">
									<Link
										:href="route('inbox.show', message.id)"
										:class="[
											'hover:text-shakespeare-600',
											!message.is_read
												? 'font-bold'
												: '',
										]">
										{{ message.subject }}
									</Link>
									<span
										v-if="!message.is_read"
										class="bg-blue-500 size-2 rounded-full"></span>
								</CardTitle>
								<p class="text-cape-palliser-600 mt-1 text-sm">
									From: {{ message.admin.name }}
								</p>
								<p class="text-cape-palliser-500 mt-1 text-xs">
									{{ formatDate(message.created_at) }}
								</p>
							</div>
							<span
								:class="[
									'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
									getStatusBadgeClass(message.status),
								]">
								{{
									message.status.charAt(0).toUpperCase() +
									message.status.slice(1)
								}}
							</span>
						</div>
					</CardHeader>
					<CardContent>
						<div class="space-y-4">
							<div class="flex items-center gap-4">
								<div
									v-if="message.horse.design_link"
									class="flex-shrink-0">
									<img
										:src="message.horse.design_link"
										:alt="message.horse.name"
										class="h-16 w-16 rounded border border-gray-200 object-cover" />
								</div>
								<div class="flex-1">
									<p class="text-sm">
										<strong>Horse:</strong>
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
									<p
										v-if="message.initial_message"
										class="text-cape-palliser-700 mt-2 text-sm">
										{{ message.initial_message }}
									</p>
									<p
										v-if="message.admin_edits"
										class="text-yellow-700 mt-2 text-sm">
										⚠️ Admin has made edits that require
										your review
									</p>
								</div>
							</div>
							<div class="flex items-center justify-between">
								<p
									v-if="message.comment_count > 0"
									class="text-cape-palliser-500 text-xs">
									{{ message.comment_count }}
									{{
										message.comment_count === 1
											? 'comment'
											: 'comments'
									}}
									<span
										v-if="message.latest_comment_at">
										• Last
										{{
											formatDate(
												message.latest_comment_at,
											)
										}}
									</span>
								</p>
								<Link
									:href="route('inbox.show', message.id)">
									<Button variant="outline" size="sm">
										View Message
									</Button>
								</Link>
							</div>
						</div>
					</CardContent>
				</Card>
			</div>
		</div>
	</AppLayout>
</template>
