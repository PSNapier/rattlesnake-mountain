<script setup lang="ts">
import { GripVertical, Newspaper } from 'lucide-vue-next';
import {
	boxClasses,
	widthOptions,
	type CmsAnnouncement,
	type CmsBox,
	type CmsNewsArchive as CmsNewsArchiveData,
} from './boxes';
import CmsNewsArchive from './CmsNewsArchive.vue';
import CmsNewsSlot from './CmsNewsSlot.vue';

// A pinned slot (home's News, or the news page's archive) can be dragged and
// resized, nothing else: its content is live announcements, so there is no
// editor and no remove.
const box = defineModel<CmsBox>({ required: true });

defineProps<{
	announcements: CmsAnnouncement[];
	archive?: CmsNewsArchiveData | null;
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
				{{
					box.kind === 'news-archive'
						? 'Announcement archive'
						: 'Live announcements'
				}}
			</span>
		</div>

		<div
			v-if="box.kind === 'news-archive'"
			class="space-y-6">
			<CmsNewsArchive :archive="archive ?? null" />
		</div>
		<CmsNewsSlot
			v-else
			:announcements="announcements" />
	</div>
</template>
