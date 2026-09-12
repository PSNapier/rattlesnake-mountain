<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { formatPublishedAt, type CmsNewsArchive } from './boxes';

/**
 * Inner content of the `news` page's archive slot: every published
 * announcement in full, newest first, paginated server-side.
 */
defineProps<{
	archive: CmsNewsArchive | null;
}>();
</script>

<template>
	<p v-if="!archive || archive.data.length === 0">
		No announcements yet. Check back soon.
	</p>

	<template v-else>
		<article
			v-for="announcement in archive.data"
			:key="announcement.id"
			class="border-new-orleans-500 space-y-2 border-b pb-6 last-of-type:border-b-0"
			data-testid="archive-announcement">
			<h3>{{ announcement.title }}</h3>
			<p
				v-if="announcement.published_at"
				class="text-sm italic">
				{{ formatPublishedAt(announcement.published_at) }}
			</p>
			<div
				class="cms-announcement space-y-2"
				v-html="announcement.body"></div>
		</article>

		<nav
			v-if="archive.last_page > 1"
			class="flex items-center justify-between gap-4 pt-2"
			aria-label="News pages">
			<Link
				v-if="archive.prev_page_url"
				:href="archive.prev_page_url"
				class="font-bold underline"
				>Newer</Link
			>
			<span v-else></span>
			<span class="text-sm"
				>Page {{ archive.current_page }} of
				{{ archive.last_page }}</span
			>
			<Link
				v-if="archive.next_page_url"
				:href="archive.next_page_url"
				class="font-bold underline"
				>Older</Link
			>
			<span v-else></span>
		</nav>
	</template>
</template>
