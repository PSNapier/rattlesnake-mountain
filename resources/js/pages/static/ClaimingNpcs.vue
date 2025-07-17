<script setup lang="ts">
import DynamicInfo from '@/components/custom/DynamicInfo.vue';
import { linkDict } from '@/composables/useLinkDictionary';

const hero = {
	title: `Claiming NPCs`,
	description: `Grow your herd.`,
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
		`![](/images/claimingnpcs-banner-claiming.png)`,
		`*An NPC is a character that is, in most games, not playable by any individual and exist to bring life to a world or aid in a story-line, be it negatively or positively. In Rattlesnake Mountain, our NPCs exist for that reason, however most are claimable in the sense that they become played characters once they belong to a player's herd. Here you will find information on how a NPC horse (mare, stallion, or foal) can be claimed for your herd.*`,
	],
	box2: [
		`![](/images/claimingnpcs-banner-process.png)`,
		`a. In order to start claiming an individual mare, stallion, or foal, you must encounter them in your herd's story. This may happen randomly during story progression roles, and may be influenced by both Benefits and Detriments.\n- You may also request to encounter a specific mare, foal, or stallion once per real life week in any submission made during that week; simply include it in the "Goals" section of the submission form.\n- An admin will then respond to your artwork or literature, as usual, with a continuation of your herd's story but with the introduction of the mare, stallion, or foal. As stated, this can also occur randomly, and requests do not have to be made to meet an NPC.\n\nb. So-called "Requested NPCs" will be considered "frozen" and taken out of RNG rotation until they are claimed or one month after the claiming story has begun.\n- NPCs (excluding randomly generated, non-designed encounters) that are RNG'd into your story will not be "frozen" or taken out of gameplay, leaving them available for claim by the character who satisfies the NPC first.\n- In the instance that the NPC is claimed before you, but you were on a prompt to claim the NPC, the amount of stats earned from the piece (finished or otherwise) will be doubled for you and any other featured horse. Alternatively, one PvP item will be deposited to your vault for either resale or personal use.\n\nc. Each NPC comes with their own checklist (hidden to you but visible to admins) that must be completed before the NPC will join your herd.\n- This checklist is reliant on the NPC's age, personality, stats, current health, detriments, benefits, and history.\n- Most of these needs are numerical and require a certain amount of interactions, while others are more story and personality related.\n- For example, a young, inexperienced stallion or lead mare will have a difficulty keeping an older and experienced mare in their herd for any length of time. Foals may be easier to claim, but more difficult to keep alive. As such, the process and story will be different for each type of claim attempt.\n\nd. To progress the claiming of an NPC, simply continue the new story route with them, meeting their needs and requirements along the way.\n- While that story may reroute you from your original course, it is always possible to return to your main story after the NPC's claiming story is finished! Think of claiming as a side quest.\n- Once you have successfully claimed an NPC for your herd, it is up to you to flesh out their information: give them a name, improve upon their basic personality and history, etc. e. To make edits to an NPC after they have been claimed, head on over to design-submissions, listing out which items you would like altered.\n- Note that ID #, Sex, Age, Breed, Phenotype, Genotype, Rarity, Design, Distinguishing Marks, etc cannot be changed without an item that allows an overhaul.\n- Randomly generated Genotypes and Phenotypes may be designed by you or a Rattlesnake Designer and may be affected by Stones. To add a gene to a genotype, indicate which Stones you are using when you submit the character's design.\n- To request a Rattlesnake Designer to design your claimable, check out rattlesnake-designs.\n- All designs are subject to admin approval and follow the same process, rules, and regulations stated in both the [Character Handbook](${linkDict.CHARACTER_HANDBOOK.path}) and [Character Creation](${linkDict.CHARACTER_UPLOAD.path}) pages unless otherwise stated.`,
	],
	box3: [
		`![](/images/claimingnpcs-banner-more.png)`,
		`*Because this is a mostly realistic game, claiming can never be considered permanent. Sometimes, events will separate your herd and it will stay separated. Illness and death are not foreign to wild animals, and even the elements themselves will get too harsh for some horses to survive. There are, of course, some helpful items that will increase your NPC's and PC's longevity, but they are not always a guarantee and can get quite expensive.*`,
		`a. If an NPC should happen to die during your story, they will be retired from the game completely and moved to the graveyard.\n- NPCs can only be retired from gameplay if they have died or if they have an item used to retire them.\n- Once retired or deceased, an NPC cannot be brought back to life.\n\nb. If your herd leader falls inactive (missing two activity checks (~6 months) without notice or leaving the Discord), all NPC's under them will be released and considered claimable again.\n- In some instances--that is, another herd is close by, the leaders of the other herd will have the opportunity to easily claim those newly released herd members.\n- This is done via thread on our Discord. We recommend that if you have mentions turned off on our Discord, to turn them on, or check for updates each week.\n\nc. Claiming has an immediate and direct effect on Herd Unity (not all horses are best friends right away!) For more on Herd Unity and the effect it has on your herd, check out our [Herd Unity](${linkDict.HERD_UNITY.path}) page.`,
	],
};
</script>

<template>
	<DynamicInfo
		:hero="hero"
		:images="images"
		:content="content" />
</template>
