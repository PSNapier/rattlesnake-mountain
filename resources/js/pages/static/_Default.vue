<script setup lang="ts">
import DynamicInfo from '@/components/custom/DynamicInfo.vue';

const hero = {
	title: 'Title',
	description: 'Description.',
};
const images = [
	{
		name: 'Name',
		link: 'https://www.deviantart.com/name',
		path: '/images/image.png',
	},
];
const content = {
	box1: [
		``,
	],
};
</script>

<template>
	<DynamicInfo
		:hero="hero"
		:images="images"
		:content="content" />
</template>
