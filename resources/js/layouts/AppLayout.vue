<script setup lang="ts">
import AppLayout from '@/layouts/app/AppHeaderLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import HeaderNav from '../components/custom/HeaderNav.vue';

interface Props {
	breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
	breadcrumbs: () => [],
});
</script>

<template>
	<HeaderNav />

	<AppLayout :breadcrumbs="breadcrumbs">
		<slot />
	</AppLayout>
</template>
