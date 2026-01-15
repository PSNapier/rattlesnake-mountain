<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import Footer from '@/components/custom/Footer.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
	breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
	breadcrumbs: () => [],
});
</script>

<template>
	<AppShell class="flex-col p-6">
		<!-- <AppHeader :breadcrumbs="breadcrumbs" /> -->
		<AppContent>
			<slot />
		</AppContent>
		<Footer />
	</AppShell>
</template>
