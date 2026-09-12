<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';

/**
 * A contenteditable bound to a flat string.
 *
 * `title`, `hero_title` and `hero_description` are plain columns rendered
 * through text interpolation, not HTML, so this strips every trace of markup:
 * `plaintext-only` where the browser supports it, a paste handler that inserts
 * the text/plain flavour, and Enter suppressed so no <div> or <br> can appear.
 */
const props = withDefaults(
	defineProps<{
		tag?: string;
		placeholder?: string;
	}>(),
	{ tag: 'span', placeholder: '' },
);

const model = defineModel<string>({ required: true });

const el = ref<HTMLElement | null>(null);

function syncFromModel() {
	// Only write when the value genuinely differs, otherwise every keystroke
	// rewrites the node and throws the caret to the start.
	if (el.value && el.value.innerText !== model.value) {
		el.value.innerText = model.value;
	}
}

onMounted(syncFromModel);
watch(model, syncFromModel);

function onInput() {
	model.value = el.value?.innerText ?? '';
}

function onPaste(event: ClipboardEvent) {
	event.preventDefault();
	const text = (event.clipboardData?.getData('text/plain') ?? '')
		.replace(/\s+/g, ' ')
		.trim();
	if (!text) return;
	// execCommand is the only cross-browser way to insert at the caret and keep
	// the undo stack intact.
	document.execCommand('insertText', false, text);
}

function onKeydown(event: KeyboardEvent) {
	if (event.key === 'Enter') event.preventDefault();
}
</script>

<template>
	<component
		:is="props.tag"
		ref="el"
		contenteditable="plaintext-only"
		role="textbox"
		:aria-label="props.placeholder || undefined"
		class="border-shakespeare-400 focus:border-shakespeare-600 block w-full rounded-md border-2 border-dashed bg-white/60 px-2 py-1 outline-none"
		@input="onInput"
		@paste="onPaste"
		@keydown="onKeydown"></component>
</template>
