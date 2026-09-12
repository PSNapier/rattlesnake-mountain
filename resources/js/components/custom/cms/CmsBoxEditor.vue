<script setup lang="ts">
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { GripVertical, Trash2 } from 'lucide-vue-next';
import { computed, watch } from 'vue';
import { boxClasses, styleOptions, widthOptions, type CmsBox } from './boxes';
import CmsEditToolbar from './CmsEditToolbar.vue';
import { cmsExtensions } from './tiptapExtensions';

// The model holds the very object the draft array holds, so edits to width,
// style and html land straight in the save payload.
const box = defineModel<CmsBox>({ required: true });

const emit = defineEmits<{ remove: [] }>();

const editor = useEditor({
	content: box.value.html,
	extensions: cmsExtensions(),
	editorProps: {
		attributes: {
			class: 'min-h-24 space-y-2 outline-none',
		},
		// A box drag ends with the pointer over some other box's editable
		// area, and ProseMirror treats that as a drop: it parses the dragged
		// markup and files the toolbar's own text into the document. Nothing
		// in this editor wants a drop, so swallow every one of them.
		handleDrop: () => true,
	},
	onUpdate({ editor }) {
		box.value.html = editor.getHTML();
	},
});

// A restore or a discard swaps the whole draft. If this component is reused for
// the same box id, push the incoming html into the running editor.
watch(
	() => box.value.html,
	(html) => {
		if (!editor.value) return;
		if (editor.value.getHTML() === html) return;
		editor.value.commands.setContent(html, { emitUpdate: false });
	},
);

// Edit mode keeps one flat grid so drag order stays true, so a band box can't
// sit in its strip here. A thick strip-coloured ring stands in for it.
const indicator = computed(() =>
	box.value.style === 'band'
		? 'ring-cape-palliser-500 ring-8'
		: 'ring-shakespeare-300 ring-2',
);
</script>

<template>
	<div :class="[...boxClasses(box), indicator]">
		<div
			class="border-cape-palliser-300 -mt-1 mb-2 flex flex-wrap items-center gap-2 border-b pb-2 text-left">
			<button
				type="button"
				title="Drag to reorder"
				class="drag-handle text-cape-palliser-500 cursor-grab active:cursor-grabbing">
				<GripVertical class="size-4" />
			</button>

			<select
				v-model="box.width"
				title="Box width"
				aria-label="Box width"
				class="border-input rounded-md border bg-white px-2 py-1 text-xs">
				<option
					v-for="option in widthOptions"
					:key="option.value"
					:value="option.value">
					Width {{ option.label }}
				</option>
			</select>

			<select
				v-model="box.style"
				title="Box style"
				aria-label="Box style"
				class="border-input rounded-md border bg-white px-2 py-1 text-xs">
				<option
					v-for="option in styleOptions"
					:key="option.value"
					:value="option.value">
					{{ option.label }}
				</option>
			</select>

			<button
				type="button"
				title="Remove this box"
				class="text-destructive hover:bg-destructive/10 ml-auto flex cursor-pointer items-center gap-1 rounded px-2 py-1 text-xs"
				@click="emit('remove')">
				<Trash2 class="size-4" />
				Remove
			</button>
		</div>

		<CmsEditToolbar :editor="editor" />

		<EditorContent :editor="editor" />
	</div>
</template>
