<template>
	<div
		role="progressbar"
		:aria-valuenow="value"
		:aria-valuemin="min"
		:aria-valuemax="max"
		class="relative h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700"
	>
		<div
			class="h-full w-full flex-1 bg-blue-600 transition-all dark:bg-blue-500"
			:style="{ transform: `translateX(-${100 - (value / max) * 100}%)` }"
		/>
	</div>
</template>

<script setup lang="ts">
interface Props {
	value: number;
	min?: number;
	max?: number;
}

withDefaults(defineProps<Props>(), {
	min: 0,
	max: 100,
});
</script>
