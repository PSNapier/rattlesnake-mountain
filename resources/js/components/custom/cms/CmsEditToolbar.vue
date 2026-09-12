<script setup lang="ts">
import type { Editor } from '@tiptap/vue-3';
import {
	Bold,
	Image as ImageIcon,
	Italic,
	Link as LinkIcon,
	List,
	ListOrdered,
	Minus,
	Quote,
	Unlink,
} from 'lucide-vue-next';

const props = defineProps<{ editor: Editor | undefined }>();

const headingLevels = [
	1,
	2,
	3,
	4,
] as const;

const toolClass =
	'text-cape-palliser-700 hover:bg-cape-palliser-100 flex size-7 cursor-pointer items-center justify-center rounded';
const toolActiveClass = 'bg-cape-palliser-200 text-cape-palliser-950';

function isActive(name: string, attrs?: Record<string, unknown>): boolean {
	return props.editor?.isActive(name, attrs) ?? false;
}

function setLink() {
	const editor = props.editor;
	if (!editor) return;
	const previous = (editor.getAttributes('link').href as string) ?? '';
	const href = window.prompt('Link URL', previous);
	if (href === null) return;
	if (href.trim() === '') {
		editor.chain().focus().extendMarkRange('link').unsetLink().run();
		return;
	}
	editor
		.chain()
		.focus()
		.extendMarkRange('link')
		.setLink({ href: href.trim() })
		.run();
}

function unsetLink() {
	props.editor?.chain().focus().extendMarkRange('link').unsetLink().run();
}

function addImage() {
	const editor = props.editor;
	if (!editor) return;
	// Picking from a media library is item [038]; for now the admin pastes a URL.
	const src = window.prompt('Image URL');
	if (src === null || src.trim() === '') return;
	const alt = window.prompt('Alt text (describe the image)') ?? '';
	editor.chain().focus().setImage({ src: src.trim(), alt }).run();
}
</script>

<template>
	<div
		v-if="editor"
		class="border-cape-palliser-300 mb-2 flex flex-wrap items-center gap-1 rounded-md border bg-white/80 p-1">
		<button
			type="button"
			title="Bold"
			:class="[toolClass, isActive('bold') ? toolActiveClass : '']"
			@click="editor.chain().focus().toggleBold().run()">
			<Bold class="size-4" />
		</button>
		<button
			type="button"
			title="Italic"
			:class="[toolClass, isActive('italic') ? toolActiveClass : '']"
			@click="editor.chain().focus().toggleItalic().run()">
			<Italic class="size-4" />
		</button>

		<span class="bg-cape-palliser-300 mx-1 h-5 w-px"></span>

		<button
			v-for="level in headingLevels"
			:key="level"
			type="button"
			:title="`Heading ${level}`"
			:class="[
				toolClass,
				'text-xs font-bold',
				isActive('heading', { level }) ? toolActiveClass : '',
			]"
			@click="editor.chain().focus().toggleHeading({ level }).run()">
			H{{ level }}
		</button>

		<span class="bg-cape-palliser-300 mx-1 h-5 w-px"></span>

		<button
			type="button"
			title="Bullet list"
			:class="[
				toolClass,
				isActive('bulletList') ? toolActiveClass : '',
			]"
			@click="editor.chain().focus().toggleBulletList().run()">
			<List class="size-4" />
		</button>
		<button
			type="button"
			title="Numbered list"
			:class="[
				toolClass,
				isActive('orderedList') ? toolActiveClass : '',
			]"
			@click="editor.chain().focus().toggleOrderedList().run()">
			<ListOrdered class="size-4" />
		</button>
		<button
			type="button"
			title="Quote"
			:class="[
				toolClass,
				isActive('blockquote') ? toolActiveClass : '',
			]"
			@click="editor.chain().focus().toggleBlockquote().run()">
			<Quote class="size-4" />
		</button>
		<button
			type="button"
			title="Divider"
			:class="toolClass"
			@click="editor.chain().focus().setHorizontalRule().run()">
			<Minus class="size-4" />
		</button>

		<span class="bg-cape-palliser-300 mx-1 h-5 w-px"></span>

		<button
			type="button"
			title="Link"
			:class="[toolClass, isActive('link') ? toolActiveClass : '']"
			@click="setLink">
			<LinkIcon class="size-4" />
		</button>
		<button
			v-if="isActive('link')"
			type="button"
			title="Remove link"
			:class="toolClass"
			@click="unsetLink">
			<Unlink class="size-4" />
		</button>
		<button
			type="button"
			title="Image by URL"
			:class="toolClass"
			@click="addImage">
			<ImageIcon class="size-4" />
		</button>
	</div>
</template>
