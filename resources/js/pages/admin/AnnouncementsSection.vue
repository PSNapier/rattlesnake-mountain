<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { router } from '@inertiajs/vue3';
import { reactive } from 'vue';

interface Announcement {
	id: number;
	title: string;
	body: string;
	published_at: string | null;
	author_name: string | null;
}

/** Editable row: published_at coerced to the datetime-local string the input needs. */
type AnnouncementEditable = {
	title: string;
	body: string;
	published_at: string;
};

const props = withDefaults(
	defineProps<{
		announcements?: Announcement[];
	}>(),
	{ announcements: () => [] },
);

const textareaClass =
	'border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 mt-1 flex w-full min-w-0 rounded-md border bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50 md:text-sm';

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

const createForm = reactive<AnnouncementEditable>({
	title: '',
	body: '',
	published_at: nowLocalInput(),
});

const editableRows = reactive<Record<number, AnnouncementEditable>>({});

const getRowState = (announcement: Announcement): AnnouncementEditable => {
	if (!editableRows[announcement.id]) {
		editableRows[announcement.id] = {
			title: announcement.title,
			body: announcement.body,
			published_at: toLocalInput(announcement.published_at),
		};
	}

	return editableRows[announcement.id];
};

const createAnnouncement = (): void => {
	router.post(
		route('admin.announcements.store'),
		{
			title: createForm.title,
			body: createForm.body,
			published_at: toIsoInstant(createForm.published_at),
		},
		{
			onSuccess: () => {
				createForm.title = '';
				createForm.body = '';
				createForm.published_at = nowLocalInput();
			},
		},
	);
};

const saveAnnouncement = (
	announcement: Announcement,
	overrides: Partial<AnnouncementEditable> = {},
): void => {
	const row = getRowState(announcement);
	const merged = { ...row, ...overrides };
	Object.assign(row, merged);

	router.put(route('admin.announcements.update', announcement.id), {
		title: merged.title,
		body: merged.body,
		published_at: toIsoInstant(merged.published_at),
	});
};

const publishNow = (announcement: Announcement): void =>
	saveAnnouncement(announcement, { published_at: nowLocalInput() });

const unpublish = (announcement: Announcement): void =>
	saveAnnouncement(announcement, { published_at: '' });

const removeAnnouncement = (announcement: Announcement): void => {
	router.delete(route('admin.announcements.destroy', announcement.id));
};

const statusLabel = (announcement: Announcement): string => {
	if (!announcement.published_at) {
		return 'Draft';
	}

	return new Date(announcement.published_at) > new Date()
		? 'Scheduled'
		: 'Published';
};
</script>

<template>
	<div>
		<Card class="mb-6">
			<CardHeader>
				<CardTitle>Create Announcement</CardTitle>
			</CardHeader>
			<CardContent class="space-y-4">
				<div>
					<Label for="announcement-create-title">Title</Label>
					<Input
						id="announcement-create-title"
						v-model="createForm.title"
						class="mt-1" />
				</div>

				<div>
					<Label for="announcement-create-body">Body</Label>
					<textarea
						id="announcement-create-body"
						v-model="createForm.body"
						rows="4"
						:class="textareaClass" />
				</div>

				<div>
					<Label for="announcement-create-published-at">
						Publish At (leave blank to save as a draft)
					</Label>
					<Input
						id="announcement-create-published-at"
						v-model="createForm.published_at"
						type="datetime-local"
						class="mt-1" />
				</div>

				<Button
					:disabled="!createForm.title || !createForm.body"
					@click="createAnnouncement">
					Create Announcement
				</Button>
			</CardContent>
		</Card>

		<Card>
			<CardHeader>
				<CardTitle>Announcements</CardTitle>
			</CardHeader>
			<CardContent class="space-y-6">
				<p
					v-if="props.announcements.length === 0"
					class="text-cape-palliser-700 text-sm">
					No announcements yet.
				</p>

				<div
					v-for="announcement in props.announcements"
					:key="announcement.id"
					class="border-shakespeare-200 space-y-3 rounded-lg border p-4">
					<div class="flex items-center justify-between gap-2">
						<span
							class="text-shakespeare-700 text-sm font-semibold">
							{{ statusLabel(announcement) }}
						</span>
						<span
							v-if="announcement.author_name"
							class="text-cape-palliser-700 text-sm">
							by {{ announcement.author_name }}
						</span>
					</div>

					<div>
						<Label
							:for="`announcement-title-${announcement.id}`"
							>Title</Label
						>
						<Input
							:id="`announcement-title-${announcement.id}`"
							v-model="getRowState(announcement).title"
							class="mt-1" />
					</div>

					<div>
						<Label
							:for="`announcement-body-${announcement.id}`"
							>Body</Label
						>
						<textarea
							:id="`announcement-body-${announcement.id}`"
							v-model="getRowState(announcement).body"
							rows="4"
							:class="textareaClass" />
					</div>

					<div>
						<Label
							:for="`announcement-published-at-${announcement.id}`"
							>Publish At</Label
						>
						<Input
							:id="`announcement-published-at-${announcement.id}`"
							v-model="
								getRowState(announcement).published_at
							"
							type="datetime-local"
							class="mt-1" />
					</div>

					<div class="flex flex-wrap gap-2">
						<Button @click="saveAnnouncement(announcement)"
							>Save</Button
						>
						<Button
							variant="outline"
							@click="publishNow(announcement)">
							Publish Now
						</Button>
						<Button
							variant="outline"
							:disabled="!announcement.published_at"
							@click="unpublish(announcement)">
							Unpublish
						</Button>
						<Button
							variant="destructive"
							@click="removeAnnouncement(announcement)">
							Delete
						</Button>
					</div>
				</div>
			</CardContent>
		</Card>
	</div>
</template>
