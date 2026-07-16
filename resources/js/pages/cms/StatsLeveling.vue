<script setup lang="ts">
import DynamicInfo from '@/components/custom/DynamicInfo.vue';
import { linkDict } from '@/composables/useLinkDictionary';

const hero = {
	title: 'Stats & Leveling',
	description: 'An arpg for wild horse enthusiasts!',
};
const images = [
	{
		name: 'zach',
		link: 'https://www.deviantart.com/snompy',
		path: '/images/statsleveling-art-zach.png',
	},
	{
		name: 'yell',
		link: 'https://www.deviantart.com/iiyell',
		path: '/images/statsleveling-art-yell.png',
	},
	{
		name: 'KarmaArt666',
		link: 'https://www.deviantart.com/KarmaArt666',
		path: '/images/statsleveling-art-KarmaArt666.png',
	},
];
const content = {
	box1: [
		`![](/images/statsleveling-banner-freshcharacters.png)`,
		`*For a reminder on what each stat category does, [here is an explanation using tomatoes](${linkDict.STATS_REF.path}). Rattlesnake Mountain distributes stats in the following categories; **players cannot distribute stats into Experience or Health***.`,
		`Strength:\n\nDexterity:\n\nConstitution:\n\nIntelligence:\n\nWisdom:\n\nCharisma:\n\nExperience:\n\nHealth:`,
		`a. All player characters begin with +1 stat in 2 chosen categories excluding Experience and Health (otherwise known as "proficiencies").\n- Proficiencies may be any stat category you'd like; ex. +1 Dexterity and +1 Charisma for a dashing rogue!\n\nb. All player characters begin with -2 stats in 2 chosen categories excluding Experience and Health (otherwise known as "incompetencies").\n- Incompetencies may be any stat category you'd like and may match a character's personality; ex. -1 Intelligence and -1 Dexterity for a horse who struggles with memory and the art of conversation!`,
	],
	box2: [
		`![](/images/statsleveling-banner-levelingstats.png)`,
		`a. Each submission a player makes to their story (or reference for a character), will earn their characters stat points depending on effort. Players must include a breakdown of the stat estimation in each submission (found in the submission form).\n- Stat points ("attribute points") may be distributed by the player into Strength, Dexterity, Constitution, Intelligence, Wisdom, or Charisma. Stat points cannot be distributed into Experience or Health.\n\nb. Every 10 stat points allocated into an attribute will level up that category by +1. For example, putting 10 stats into Dexterity will increase a character's Dexterity by an additional +1.\n-  Attribute levels must be confirmed by staff in #requests-and-updates each time an attribute is leveled.\n- Proficiencies are capped at level 6 (for 50 stat points invested) for an additional +6 to their proficiency, excluding any additional bonus from items, etc.\n- Attributes without a bonus or negative score are capped at level 5 for an additional +5 to that stat.\n- Incompetencies are capped at level 4 for an additional +4 to their incompetency, excluding any additional bonus from items, etc.`,
		`# **To submit a level up, please post in #requests-and-updates using the Stat Check form.**`,
	],
	box3: [
		`![](/images/statsleveling-banner-artstatcounts.png)`,
		`*Points are counted individually per horse. If a horse is drawn twice in an image, that will increase the points gained over the horse that is drawn only once.*`,
		`**Base Points Per Horse**\n\nHalf & Partial Body: +1\n- Must at least be a headshot, half the front or back of the body, or missing the legs from the end of the shoulder & gaskin down.\n- For art of a horse that is covered by another horse, must lack any evidence that the entire body was drawn prior to putting the other horse in front of it.\n\nFullbody: +2\n- Must be the entire body of the horse. May exclude hooves.\n\nColored: +1 Stat\n- Horse must be colored.\n\nShading: +1 Stat\n- Horse must be shaded.`,
		`**Base Points Per Image/Comic Panel**\n\nBackground: +1 Stat\n- Backgrounds will not be counted if they are simply solid shapes or brushes of shrubs, dirt, etc. Includes photomanipulated backgrounds - however they must be edited in some way!\n\nSimple Animations: +1 Stat\n- Simple Animations include a horse blinking, tail swish, etc.\n\nComplex Animations: +1 Stat\n- Complex Animations are horses walking, trotting, etc or backgrounds with wind, etc.`,
	],
	box4: [
		`![](/images/statsleveling-banner-litstatcounts.png)`,
		`Every 200 Words: +1 Stat\n- If a herd member is not included in the story submission, or only featured for one line, the herd member will not receive stats.\n\nEvery 1000 Words: Bonus +1 Stat`,
	],
};
</script>

<template>
	<DynamicInfo
		:hero="hero"
		:images="images"
		:content="content" />
</template>
