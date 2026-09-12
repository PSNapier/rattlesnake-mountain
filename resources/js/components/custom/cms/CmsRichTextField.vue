<script setup lang="ts">
import { EditorContent, useEditor } from '@tiptap/vue-3';
import CmsEditToolbar from './CmsEditToolbar.vue';
import { cmsExtensions } from './tiptapExtensions';

/**
 * A standalone rich-text field on the CMS editor stack: same extensions and
 * toolbar as page boxes, so it can only produce what the sanitizer keeps.
 * Mount it only while it is visible, since each instance is a full editor.
 */
const html = defineModel<string>({ required: true });

const props = defineProps<{
	/** Id of the visible label, for the editable region's accessible name. */
	labelledby?: string;
}>();

const editor = useEditor({
	content: html.value,
	extensions: cmsExtensions(),
	editorProps: {
		attributes: {
			class: 'min-h-40 space-y-2 outline-none',
			role: 'textbox',
			'aria-multiline': 'true',
			...(props.labelledby
				? { 'aria-labelledby': props.labelledby }
				: {}),
		},
	},
	onUpdate({ editor }) {
		html.value = editor.getHTML();
	},
});
</script>

<template>
	<div
		class="border-input focus-within:border-ring focus-within:ring-ring/50 rounded-md border p-2 focus-within:ring-[3px]">
		<CmsEditToolbar :editor="editor" />
		<EditorContent
			:editor="editor"
			class="max-h-[50vh] overflow-y-auto px-1" />
	</div>
</template>
