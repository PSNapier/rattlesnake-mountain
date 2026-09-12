<script setup lang="ts">
import { GripVertical, Newspaper } from 'lucide-vue-next';
import {
	boxClasses,
	widthOptions,
	type CmsAnnouncement,
	type CmsBox,
} from './boxes';
import CmsNewsSlot from './CmsNewsSlot.vue';

// The pinned News slot can be dragged and resized, nothing else: its content
// is the live announcement feed, so there is no editor and no remove.
const box = defineModel<CmsBox>({ required: true });

defineProps<{
	announcements: CmsAnnouncement[];
}>();
</script>

<template>
	<div
		:class="[
			...boxClasses(box).filter((c) => c !== 'space-y-2'),
			'ring-shakespeare-300 ring-2',
		]">
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

			<span
				class="text-cape-palliser-700 ml-auto flex items-center gap-1 text-xs">
				<Newspaper class="size-4" />
				Live announcements
			</span>
		</div>

		<CmsNewsSlot :announcements="announcements" />
	</div>
</template>
