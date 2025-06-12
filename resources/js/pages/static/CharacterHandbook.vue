<script setup lang="ts">
import Layout from '@/components/custom/Layout.vue';
</script>

<template>
	<Layout title="Home">
		<template #hero>
			<h1>Rattlesnake Mountain</h1>
			<p>Character handbook page.</p>
		</template>

		<!-- Main content for this page -->
		<div class="max-container grid grid-cols-1 gap-4 p-4"></div>
	</Layout>
</template>
