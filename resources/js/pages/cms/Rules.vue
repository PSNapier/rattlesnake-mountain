<script setup lang="ts">
import DynamicInfo from '@/components/custom/DynamicInfo.vue';

const hero = {
	title: 'Rules',
	description: 'Be sure to follow these at all times!',
};
const images = [
	{
		name: 'Adair408',
		link: 'https://www.deviantart.com/adair408',
		path: '/images/rules-art-adair408.png',
	},
	{
		name: 'Jarchivist',
		link: 'https://www.deviantart.com/jarchivist',
		path: '/images/rules-art-jarchivist.png',
	},
];
const content = {
	box1: [
		'![](/images/rules-banner-rules.png)',
		`**I. Respect**\n\nRattlesnake Mountain and all its players and staff abide by the golden rule: "Do to others what you want them to do to you.” This is, above all, the most important rule of the group. It isn't hard to treat people how you'd like to be treated, and at the end of the day, Rattlesnake Mountain is a game. We come here to have fun.\n\na. Mature, NSFW, or potentially triggering content should be marked accordingly.\n\nb. Art theft in any way, shape, or form is not tolerated. That said, tracing references from stock photos is allowed as long as the piece complies with the stock artist's own set of rules. Credit does not need to be given unless the stock artist requires it.\n\nc. Commissions are allowed, however the image must be submitted to the appropriate folder in the group with the Submission form filled out completely. Failure to do so will not result in a roll!\n\nd. In that same vein, "premade linearts" are also permitted as references. They may count for rolls as long as literature accompanies the image when it is submitted to the group.`,
		`**II. Designs**\n\nAll designs and characters that were not designed or created by you return to their original designer(s)/creator(s) upon your departure from the Discord.\n\na. All characters will either be released for others to claim, use as an NPC, or deceased should a player leave the Discord at the discretion of the admins.\n\nb. This is waived only if they have been purchased with an item from the shop or the design/character has been under your control actively (one roll per month) for at least five in game years.`,
		`**III. Activity**\n\nReal life comes first, and we are always willing to work with players should they be unable to meet our activity requirements! Please don't hesitate to reach out!\n\na. Players who miss two activity checks in a row (a timeline of ~6 months) with no notice will have their Herd Leader listed as Away. Players may reinstate their Herd Leader at any time should they return to the game.\n\nb. Should a Herd Leader be listed as Away, their herd NPCs will be released. If the Herd Leader is reinstated, their herd may only be reclaimed by natural means - either through weekly requested encounters; randomized encounters; items; etc.\n\nc. Herd members that are released and then claimed by other players may not be reclaimed by the returning player unless the herd member becomes available again.`,
		`**IV. In-Game Time**\n\nTime is the master of our universe, and Rattlesnake Mountain is no different.\n\na. All characters will officially age one year every in-game Spring (~every 4 months) for the sake of game continuity. Players may have "unofficial" birth dates for their characters.\n\nb. For every year aged, a horse earns +1 Experience.`,
		`**V. Concluding Notes**\n\nThese rules are subject to change at any time. By joining the Rattlesnake Mountain group, you are henceforth agreeing to adhere to these rules at any given time and location pertaining to Rattlesnake Mountain.`,
		`**VI. Disclaimer**\n\nRattlesnake Mountain is intended to be a realistic wild horse game that functions similarly to a "Choose Your Own Adventure" novel and Dungeons and Dragons. As a result, character death and possession (herdmates, foals, etc) loss are a very real and intended part of the game. If you cannot handle the potential for your character to lose everything - including their life - then this is not the silly art game for you and we fully encourage you to find another ARPG that suits your preferences.`,
	],
	box2: [
		`![](/images/rules-banner-artandlit.png)`,
		`**I. General**\n\nAll responses must be submitted to your character's thread in the #story-progression channel. Art and Literature submitted on DeviantArt must be submitted to the group on DeviantArt in order for it to be processed.\n\na. Photo backgrounds must be your intellectual property or edited in some way, shape, or form in order for it to count toward a roll.\n\nb. Artwork and literature may only be used once in the group. Recolors and reworks are allowed, but will not count toward a roll.\n\nc. Collaborations, roleplay, and gift art are, of course, permitted, and often required in player-vs-player situations!\n\nd. The bare minimum of stat requirements are 1. As such, literature must be at least 200 words, and art must be at least a headshot.\n\ne. All Art and Literature involving your character will be linked on your character's DeviantArt upload, along with the staff's official stat count. This is in effort to consolidate information and make updates to your character(s) flow much faster!`,
		`**II. Submissions**\n\nStory submissions should be posted in your herd's story thread in #herd-stories with the form filled out.`,
		`# **Rules last updated by @siat-s on April 24th @ 2:20PM EST.**`,
	],
};
</script>

<template>
	<DynamicInfo
		:hero="hero"
		:images="images"
		:content="content" />
</template>
