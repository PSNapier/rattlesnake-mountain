<script setup lang="ts">
interface Props {
	title: string;
	description?: string;
}

defineProps<Props>();
</script>

<template>
	<header>
		<h3 class="mb-0.5 text-base font-medium">{{ title }}</h3>
		<p
			v-if="description"
			class="text-muted-foreground text-sm"
		>
			{{ description }}
		</p>
	</header>
</template>
