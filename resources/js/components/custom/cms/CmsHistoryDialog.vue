<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
	Dialog,
	DialogContent,
	DialogDescription,
	DialogFooter,
	DialogHeader,
	DialogTitle,
} from '@/components/ui/dialog';
import { ref } from 'vue';
import type { CmsRevision } from './useInlineEditor';

const props = defineProps<{
	revisions: CmsRevision[];
	saving: boolean;
}>();

const emit = defineEmits<{ restore: [id: number] }>();

const open = defineModel<boolean>('open', { required: true });

const pending = ref<CmsRevision | null>(null);
const confirmOpen = ref(false);

function formatWhen(iso: string): string {
	const date = new Date(iso);
	if (Number.isNaN(date.getTime())) return iso;
	return date.toLocaleString();
}

function askRestore(revision: CmsRevision) {
	pending.value = revision;
	confirmOpen.value = true;
}

function closeConfirm() {
	confirmOpen.value = false;
	setTimeout(() => {
		pending.value = null;
	}, 250);
}

function confirmRestore() {
	if (!pending.value) return;
	emit('restore', pending.value.id);
	closeConfirm();
	open.value = false;
}
</script>

<template>
	<Dialog v-model:open="open">
		<DialogContent class="max-h-[80vh] max-w-xl overflow-y-auto">
			<DialogHeader>
				<DialogTitle>Page history</DialogTitle>
				<DialogDescription>
					The last {{ props.revisions.length }} saves. Restoring
					one publishes it immediately and files the current
					version as a new entry.
				</DialogDescription>
			</DialogHeader>

			<p
				v-if="!props.revisions.length"
				class="text-cape-palliser-600 text-sm">
				No saved versions yet. The first inline save creates one.
			</p>

			<ul
				v-else
				class="space-y-2">
				<li
					v-for="revision in props.revisions"
					:key="revision.id"
					class="border-cape-palliser-200 flex items-center gap-3 rounded border p-3">
					<div class="flex-1">
						<p class="font-medium">{{ revision.title }}</p>
						<p class="text-cape-palliser-600 text-sm">
							{{ formatWhen(revision.created_at) }} ·
							{{ revision.author ?? 'Unknown' }}
						</p>
					</div>
					<Button
						variant="outline"
						size="sm"
						:disabled="props.saving"
						@click="askRestore(revision)">
						Restore
					</Button>
				</li>
			</ul>

			<DialogFooter>
				<Button
					variant="outline"
					@click="open = false">
					Close
				</Button>
			</DialogFooter>
		</DialogContent>
	</Dialog>

	<Dialog v-model:open="confirmOpen">
		<DialogContent>
			<DialogHeader>
				<DialogTitle>Restore this version?</DialogTitle>
				<DialogDescription>
					Saved
					{{ pending ? formatWhen(pending.created_at) : '' }} by
					{{ pending?.author ?? 'Unknown' }}. This publishes
					straight away and discards any unsaved edits on screen.
				</DialogDescription>
			</DialogHeader>
			<DialogFooter>
				<Button
					variant="outline"
					@click="closeConfirm">
					Cancel
				</Button>
				<Button
					:disabled="props.saving"
					@click="confirmRestore">
					Restore
				</Button>
			</DialogFooter>
		</DialogContent>
	</Dialog>
</template>
