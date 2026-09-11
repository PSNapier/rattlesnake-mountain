<script setup lang="ts">
import DynamicInfo from '@/components/custom/DynamicInfo.vue';

interface CmsBox {
	id: string;
	span: 1 | 2 | 3;
	style: 'box' | 'box-alt' | 'box-centered';
	html: string;
}

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
}

defineProps<{
	page: CmsPage;
}>();
</script>

<template>
	<DynamicInfo
		:hero="page.hero"
		:coming-soon="page.coming_soon"
		:not-public="page.not_public"
		:content="page.content" />
</template>
