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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { History, Plus, Save, Undo2 } from 'lucide-vue-next';
import { ref } from 'vue';
import CmsHistoryDialog from './CmsHistoryDialog.vue';
import type { CmsRevision } from './useInlineEditor';

const props = defineProps<{
	dirty: boolean;
	saving: boolean;
	revisions: CmsRevision[];
}>();

const emit = defineEmits<{
	save: [];
	discard: [];
	addBox: [];
	restore: [id: number];
}>();

const title = defineModel<string>('title', { required: true });

const saveOpen = ref(false);
const discardOpen = ref(false);
const historyOpen = ref(false);

function confirmSave() {
	saveOpen.value = false;
	emit('save');
}

function confirmDiscard() {
	discardOpen.value = false;
	emit('discard');
}
</script>

<template>
	<div
		class="border-cape-palliser-500 bg-cape-palliser-100 sticky bottom-0 z-40 border-t-4 shadow-[0_-4px_12px_rgba(0,0,0,0.08)]">
		<div
			class="max-container flex flex-wrap items-end justify-between gap-4 p-4">
			<div class="min-w-64 flex-1">
				<Label for="cms-page-title">
					Page title (browser tab and admin list)
				</Label>
				<Input
					id="cms-page-title"
					v-model="title"
					class="mt-1 w-full bg-white"
					placeholder="Page title" />
			</div>

			<div class="flex flex-wrap items-center gap-2">
				<span
					v-if="props.dirty"
					class="text-cape-palliser-700 text-sm font-medium">
					Unsaved changes
				</span>

				<Button
					variant="outline"
					size="sm"
					@click="emit('addBox')">
					<Plus class="size-4" />
					Add box
				</Button>
				<Button
					variant="outline"
					size="sm"
					@click="historyOpen = true">
					<History class="size-4" />
					History
				</Button>
				<Button
					variant="outline"
					size="sm"
					@click="discardOpen = true">
					<Undo2 class="size-4" />
					Discard
				</Button>
				<Button
					size="sm"
					:disabled="props.saving"
					@click="saveOpen = true">
					<Save class="size-4" />
					{{ props.saving ? 'Saving…' : 'Save' }}
				</Button>
			</div>
		</div>
	</div>

	<Dialog v-model:open="saveOpen">
		<DialogContent>
			<DialogHeader>
				<DialogTitle>Publish these changes?</DialogTitle>
				<DialogDescription>
					Saving publishes the page immediately. The version
					currently live is kept in History and can be restored.
				</DialogDescription>
			</DialogHeader>
			<DialogFooter>
				<Button
					variant="outline"
					@click="saveOpen = false">
					Cancel
				</Button>
				<Button
					:disabled="props.saving"
					@click="confirmSave">
					Save and publish
				</Button>
			</DialogFooter>
		</DialogContent>
	</Dialog>

	<Dialog v-model:open="discardOpen">
		<DialogContent>
			<DialogHeader>
				<DialogTitle>Discard your changes?</DialogTitle>
				<DialogDescription>
					The page goes back to the last saved version. Anything
					you have edited since is lost.
				</DialogDescription>
			</DialogHeader>
			<DialogFooter>
				<Button
					variant="outline"
					@click="discardOpen = false">
					Keep editing
				</Button>
				<Button
					variant="destructive"
					@click="confirmDiscard">
					Discard
				</Button>
			</DialogFooter>
		</DialogContent>
	</Dialog>

	<CmsHistoryDialog
		v-model:open="historyOpen"
		:revisions="props.revisions"
		:saving="props.saving"
		@restore="(id) => emit('restore', id)" />
</template>
