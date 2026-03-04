<script setup lang="ts">
import DynamicInfo from '@/components/custom/DynamicInfo.vue';

interface CmsImage {
	name: string;
	link: string | null;
	path: string;
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
	images: CmsImage[];
	content: Record<string, string[]>;
}

defineProps<{
	page: CmsPage;
}>();
</script>

<template>
	<DynamicInfo
		:hero="page.hero"
		:images="page.images"
		:content="page.content" />
</template>

