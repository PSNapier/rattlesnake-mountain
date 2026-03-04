<script setup lang="ts">
import DynamicInfo from '@/components/custom/DynamicInfo.vue';
import { linkDict } from '@/composables/useLinkDictionary';

const hero = {
	title: `Herd Unity`,
	description: `A measure of how close your herd is.`,
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
		`![](/images/herdunity-banner-unity.png)`,
		`*Herd Unity is the deciding factor when it comes to keeping your herd together. Individual health and experience are useful when it comes to defending your herd from threats, but if your Herd Unity is low, your herd is more easily scattered or predated upon. Therefore, it's always a good idea to try and keep your Herd Unity high. Herd Unity has a maximum of 100%, which indicates that the herd is very close-knit; and a minimum of 0%, which indicates the opposite.*`,
	],
	box2: [
		`![](/images/herdunity-banner-decreases.png)`,
		`a. New herd members always have a detrimental effect on Herd Unity.\n- Herd Unity is decreased each time a new character joins your herd via Individual Bonds (that is, the adoration felt toward your Herd Leader). You may work to increase it after the fact throughout your herd's Story Progression, noting that it is easier to do so during Checkpoints.\n- The amount of Unity lost per claim is dependent on the newcomer's personality, history, social rank, detriments, benefits, etc. For example, an older mare who was stolen from a stallion she has spent many years with will decrease your herd's Unity much more than a two-year-old filly who just left her natal herd--in fact, it's highly possible that older mare will try leave at the earliest opportunity!\n- This decrease is rolled in the first submission with the new horse in your herd.\n\nb. Story arcs with negative interactions decrease Herd Unity via Individual Bonds.\n- This includes, but is not limited to: failing to keep the herd together during events, negative human interactions, injury from predators, steals, and challenges.\n- Once a horse's Individual Bond has reached 100%, it will be harder to lose that bond. Individual Bonds that have reached 100% can only be decreased during game-wide events or story arcs in which something drastic negatively affects the horse.\n\nc. Conflicting personalities, some detriments, items, social status, etc may decrease Herd Unity via Individual Bonds.\n- Keep an eye on those individual bonds! While some horses won't ever be friends, they can at least develop working relationships... sometimes. Otherwise, horses with conflicting personalities just won't help with herd harmony.\n- Detriments and items may have an impact on Herd Unity. To apply detriments or items to your herd, head on over to the #requests-and-updates channel in our Discord.\n- It's important to keep in mind your herd members' social status. Two mares who have the Lead Mare social status will often be in a struggle for dominance with one another until one gives in or, more often than not, leaves the herd.`,
	],
	box3: [
		`![](/images/herdunity-banner-increase.png)`,
		`a. Herd Unity is naturally increased or decreased over time, however increases are buffed during Checkpoints and debuffed during Travel Checkpoints.\n- To read up on the two kinds of Checkpoints, check out our page on [Story Progression](${linkDict.STORY_PROGRESSION.path}).\n\nb. Herd Unity may also be increased through positive events in story arcs.\n- This includes, but is not limited to: keeping the herd together during events; keeping your herd safe from predators and humans; successfully defending your herd during steals and challenges; and positive herd member interactions\n\nc. Personalities that mesh well, some benefits, items, social status, etc.\n- Some horses are just more easy-going or willing to make and keep friends. Horses that get along with one another tend to stay together.\n- Some benefits and items will increase Herd Unity. To apply benefits or items to your herd, post in our requests-and-updates channel in our Discord.\n- Having little overlap in social status is hugely beneficial for stable Herd Unity. Several Low Ranking mares are more ideal to a tightly-knit herd than multiple Lead Mare status mares.`,
	],
};
</script>

<template>
	<DynamicInfo
		:hero="hero"
		:images="images"
		:content="content" />
</template>
