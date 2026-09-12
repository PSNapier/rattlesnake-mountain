<script setup lang="ts">
import type { CmsAnnouncement } from './boxes';

/**
 * Inner content of the home page's pinned News slot. The box wrapper and its
 * width come from the caller, so view and edit mode share this markup.
 */
defineProps<{
	announcements: CmsAnnouncement[];
}>();

function formatPublishedAt(published: string | null): string {
	if (!published) return '';
	return new Date(published).toLocaleString('default', {
		month: 'long',
		day: 'numeric',
		year: 'numeric',
	});
}
</script>

<template>
	<h2>News</h2>

	<p v-if="announcements.length === 0">
		No announcements right now. Check back soon.
	</p>

	<div
		v-for="announcement in announcements"
		:key="announcement.id"
		class="mb-4 last:mb-0">
		<h5 class="border-new-orleans-500 mb-2 border-b-1">
			{{ announcement.title }}
		</h5>
		<p class="whitespace-pre-line">{{ announcement.body }}</p>
		<p
			v-if="announcement.published_at"
			class="mt-1 text-sm italic">
			{{ formatPublishedAt(announcement.published_at) }}
		</p>
	</div>
</template>
