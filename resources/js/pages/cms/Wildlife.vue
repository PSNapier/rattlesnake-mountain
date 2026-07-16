<script setup lang="ts">
import DynamicInfo from '@/components/custom/DynamicInfo.vue';
import { linkDict } from '@/composables/useLinkDictionary';

const hero = {
	title: 'Wildlife',
	description: 'Take a walk on the wild side.',
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
		`# [Lifespans](${linkDict.LIFESPANS.path})\n\n# [Story Progression](${linkDict.STORY_PROGRESSION.path})\n\n# [Claiming NPCs](${linkDict.CLAIMING_NPCS.path})\n\n# [Herd Unity](${linkDict.HERD_UNITY.path})\n\n# [Breeding and Foaling](${linkDict.BREEDING_FOALING.path})\n\n# [Player vs. Player](${linkDict.PLAYER_VS_PLAYER.path})`,
	],
};
</script>

<template>
	<DynamicInfo
		:hero="hero"
		:images="images"
		:content="content" />
</template>
