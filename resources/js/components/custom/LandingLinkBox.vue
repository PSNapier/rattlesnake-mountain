<script setup lang="ts">
defineProps<{
	title: string;
	href: string;
	imageSrc: string;
	imageAlt: string;
	description: string;
}>();
</script>

<template>
	<div class="box-alt">
		<h3>
			<a :href="href">
				{{ title }}
				<img
					class="mx-auto my-2 max-h-[100px] max-w-[100px]"
					:src="imageSrc"
					:alt="imageAlt" />
			</a>
		</h3>
		<p>{{ description }}</p>
	</div>
</template>
