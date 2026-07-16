<script setup lang="ts">
import DynamicInfo from '@/components/custom/DynamicInfo.vue';

const hero = {
	title: `Contact Us`,
	description: `Feel free to get in touch.`,
};
const images = [
	{
		name: 'Nikkayla',
		link: 'https://www.deviantart.com/Nikkayla',
		path: '/images/lore-art-Nikkayla.png',
	},
];
const content = {
	box1: [
		`# [Send us an email!](mailto:abaturestudio@gmail.com?subject=Rattlesnake%20Mountain%20Contact%20Us%20Inquiry)`,
	],
};
</script>

<template>
	<DynamicInfo
		:hero="hero"
		:images="images"
		:content="content" />
</template>
