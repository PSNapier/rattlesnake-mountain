<script setup lang="ts">
import type { CmsAnnouncement, CmsBox } from '@/components/custom/cms/boxes';
import {
	useInlineEditor,
	type CmsRevision,
} from '@/components/custom/cms/useInlineEditor';
import DynamicInfo from '@/components/custom/DynamicInfo.vue';
import { computed } from 'vue';

interface CmsPage {
	id: number;
	slug: string;
	title: string;
	description: string | null;
	hero: {
		title: string;
		description: string | null;
	};
	coming_soon: boolean;
	visibility: 'live' | 'hidden';
	not_public: boolean;
	content: CmsBox[];
	can_edit?: boolean;
	/** Newest first, max 10. Empty when can_edit is false. */
	revisions?: CmsRevision[];
}

const props = withDefaults(
	defineProps<{
		page: CmsPage;
		/** Live feed for the home page's News slot. */
		announcements?: CmsAnnouncement[];
		isHome?: boolean;
	}>(),
	{ announcements: () => [], isHome: false },
);

// Always constructed: this component instance is reused across Inertia visits,
// so the lifecycle hooks inside must be registered on every page, editable or
// not. Permission is enforced by what gets handed to DynamicInfo.
const inline = useInlineEditor(() => props.page);

const canEdit = computed(() => props.page.can_edit === true);

// While editing, the hero shown by the read-only path should already be the
// draft, so leaving edit mode never flashes stale text.
const hero = computed(() =>
	inline.editing.value
		? {
				title: inline.draft.value.hero_title,
				description: inline.draft.value.hero_description,
			}
		: props.page.hero,
);
</script>

<template>
	<DynamicInfo
		:hero="hero"
		:coming-soon="page.coming_soon"
		:not-public="page.not_public"
		:content="page.content"
		:title="isHome ? page.title : null"
		:announcements="announcements"
		:is-home="isHome"
		:inline="canEdit ? inline : null" />
</template>
