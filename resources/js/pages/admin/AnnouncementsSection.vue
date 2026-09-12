<script setup lang="ts">
import CmsRichTextField from '@/components/custom/cms/CmsRichTextField.vue';
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
import { router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

interface Announcement {
	id: number;
	title: string;
	/** Sanitized HTML. */
	body: string;
	published_at: string | null;
	author_name: string | null;
}

const props = withDefaults(
	defineProps<{
		announcements?: Announcement[];
	}>(),
	{ announcements: () => [] },
);

/** ISO string to the `YYYY-MM-DDTHH:MM` local value a datetime-local input accepts. */
const toLocalInput = (iso: string | null): string => {
	if (!iso) {
		return '';
	}

	const date = new Date(iso);
	const offsetMs = date.getTimezoneOffset() * 60000;

	return new Date(date.getTime() - offsetMs).toISOString().slice(0, 16);
};

const nowLocalInput = (): string => toLocalInput(new Date().toISOString());

/**
 * `YYYY-MM-DDTHH:MM` local wall-clock back to an absolute ISO instant. Without
 * this the server (UTC) would read the admin's wall clock as UTC and shift
 * publish times by the admin's offset.
 */
const toIsoInstant = (local: string): string | null => {
	if (!local) {
		return null;
	}

	const date = new Date(local);

	return Number.isNaN(date.getTime()) ? null : date.toISOString();
};

const statusLabel = (announcement: Announcement): string => {
	if (!announcement.published_at) {
		return 'Draft';
	}

	return new Date(announcement.published_at) > new Date()
		? 'Scheduled'
		: 'Published';
};

const formatDate = (iso: string | null): string =>
	iso
		? new Date(iso).toLocaleString('default', {
				dateStyle: 'medium',
				timeStyle: 'short',
			})
		: 'Not scheduled';

// One dialog, one editor mounted at a time. The rich-text field is keyed on
// the announcement so opening a different one starts a fresh editor.
const dialogOpen = ref(false);
const saving = ref(false);
const form = reactive({
	id: null as number | null,
	title: '',
	body: '',
	published_at: '',
});

const page = usePage<{ errors: Record<string, string> }>();
const errors = computed(() => page.props.errors ?? {});

// tiptap leaves `<p></p>` behind in an emptied editor, which is not a body.
const bodyIsEmpty = computed(
	() =>
		form.body.replace(/<(?!img)[^>]*>/g, '').trim() === '' &&
		!form.body.includes('<img'),
);

function openCreate() {
	Object.assign(form, {
		id: null,
		title: '',
		body: '',
		published_at: nowLocalInput(),
	});
	dialogOpen.value = true;
}

function openEdit(announcement: Announcement) {
	Object.assign(form, {
		id: announcement.id,
		title: announcement.title,
		body: announcement.body,
		published_at: toLocalInput(announcement.published_at),
	});
	dialogOpen.value = true;
}

function save() {
	const payload = {
		title: form.title,
		body: form.body,
		published_at: toIsoInstant(form.published_at),
	};
	const options = {
		preserveScroll: true,
		onStart: () => (saving.value = true),
		onFinish: () => (saving.value = false),
		onSuccess: () => (dialogOpen.value = false),
	};

	if (form.id === null) {
		router.post(route('admin.announcements.store'), payload, options);
	} else {
		router.put(
			route('admin.announcements.update', form.id),
			payload,
			options,
		);
	}
}

const deleteTarget = ref<Announcement | null>(null);
const deleteOpen = ref(false);

function openDelete(announcement: Announcement) {
	deleteTarget.value = announcement;
	deleteOpen.value = true;
}

function confirmDelete() {
	if (!deleteTarget.value) return;
	router.delete(
		route('admin.announcements.destroy', deleteTarget.value.id),
		{
			preserveScroll: true,
			onSuccess: () => (deleteOpen.value = false),
		},
	);
}
</script>

<template>
	<Card>
		<CardHeader
			class="flex flex-row flex-wrap items-start justify-between gap-2">
			<div>
				<CardTitle>Announcements</CardTitle>
				<p class="text-cape-palliser-600 text-sm">
					The newest published post leads on home. Every
					published post is listed on /news.
				</p>
			</div>
			<Button
				size="sm"
				@click="openCreate"
				>New announcement</Button
			>
		</CardHeader>
		<CardContent>
			<p
				v-if="props.announcements.length === 0"
				class="text-cape-palliser-700 text-sm">
				No announcements yet.
			</p>

			<ul
				v-else
				class="space-y-2"
				data-testid="announcement-list">
				<li
					v-for="announcement in props.announcements"
					:key="announcement.id"
					class="flex flex-wrap items-center gap-3 rounded border border-gray-200 p-3">
					<span class="min-w-40 flex-1 font-medium">{{
						announcement.title
					}}</span>
					<span class="text-cape-palliser-600 text-sm">{{
						formatDate(announcement.published_at)
					}}</span>
					<span
						class="rounded bg-gray-100 px-1.5 py-0.5 text-xs uppercase"
						>{{ statusLabel(announcement) }}</span
					>
					<div class="flex gap-2">
						<Button
							variant="outline"
							size="sm"
							@click="openEdit(announcement)"
							>Edit</Button
						>
						<Button
							variant="destructive"
							size="sm"
							@click="openDelete(announcement)"
							>Delete</Button
						>
					</div>
				</li>
			</ul>
		</CardContent>
	</Card>

	<Dialog v-model:open="dialogOpen">
		<DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
			<DialogHeader>
				<DialogTitle>{{
					form.id === null
						? 'New announcement'
						: 'Edit announcement'
				}}</DialogTitle>
			</DialogHeader>

			<form
				class="space-y-4"
				@submit.prevent="save">
				<div>
					<Label for="announcement-title">Title</Label>
					<Input
						id="announcement-title"
						v-model="form.title"
						class="mt-1"
						required />
					<p
						v-if="errors.title"
						class="mt-1 text-sm text-red-600">
						{{ errors.title }}
					</p>
				</div>

				<div>
					<Label for="announcement-published-at">
						Publish at (leave blank to save as a draft)
					</Label>
					<Input
						id="announcement-published-at"
						v-model="form.published_at"
						type="datetime-local"
						class="mt-1" />
					<p
						v-if="errors.published_at"
						class="mt-1 text-sm text-red-600">
						{{ errors.published_at }}
					</p>
				</div>

				<div>
					<span
						id="announcement-body-label"
						class="text-sm leading-none font-medium"
						>Body</span
					>
					<div class="mt-1">
						<CmsRichTextField
							v-if="dialogOpen"
							:key="form.id ?? 'new'"
							v-model="form.body"
							labelledby="announcement-body-label" />
					</div>
					<p
						v-if="errors.body"
						class="mt-1 text-sm text-red-600">
						{{ errors.body }}
					</p>
				</div>

				<DialogFooter>
					<Button
						type="button"
						variant="outline"
						@click="dialogOpen = false"
						>Cancel</Button
					>
					<Button
						type="submit"
						:disabled="saving || !form.title || bodyIsEmpty"
						>Save announcement</Button
					>
				</DialogFooter>
			</form>
		</DialogContent>
	</Dialog>

	<Dialog v-model:open="deleteOpen">
		<DialogContent>
			<DialogHeader>
				<DialogTitle
					>Delete "{{ deleteTarget?.title }}"?</DialogTitle
				>
			</DialogHeader>
			<p>
				It leaves home and /news straight away. Staff can recover it
				if needed.
			</p>
			<DialogFooter>
				<Button
					variant="outline"
					@click="deleteOpen = false"
					>Cancel</Button
				>
				<Button
					variant="destructive"
					@click="confirmDelete"
					>Delete</Button
				>
			</DialogFooter>
		</DialogContent>
	</Dialog>
</template>
