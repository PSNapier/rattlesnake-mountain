<script setup lang="ts">
import DynamicInfo from '@/components/custom/DynamicInfo.vue';

const hero = {
	title: `Lifespans`,
	description: `An in-general guide to your character's life.`,
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
		`*Mustangs, known for their tenacity, can live long lives in the wild. However, they are confronted on a daily basis with many trials and tribulations, some of which are dangerous and deadly. Both the very young and very old can find themselves quick meals for a hungry predator and are susceptible to disease. Horses in the prime of their lives are typically stronger, and can face multiple challenges without much damage done to their physical well-being.*`,
	],
	box2: [
		`![](/images/lifespans-banner-prenatal.png)`,
		`*Prior to birth, there are a variety of things that can go wrong and result in a miscarriage by the dam or a stillborn. During gestation, it is important to ensure that the dam has plenty of food and clean water--usually this is her responsibility, though there are times when food and water become scarce and relocation becomes the only option.*`,
		`a. While your mare is in gestation, each submission featuring the herd she is with will require a roll on a d20.\n- A roll of 20 or 1 will result in your mare having a complication that you must work to counter as the story progresses.\n- A roll of 10 will result in a stillborn or miscarriage, depending on how long your mare has been gestating for.\n- Items can always be used to ensure a healthy pregnancy!`,
	],
	box3: [
		`![](/images/lifespans-banner-newborn.png)`,
		`*Newborn foals are perhaps the most vulnerable creature on the range--it's a good thing that their dam is there to protect them!*`,
		`a. Once the foal has been born, an initial health and story check roll will take place.\n- Occasionally, dams may reject their foals, or their milk doesn't flow, or foals don't receive enough colostrum. A roll on a d20 will determine your foal's initial health, which will determine the foal's strength and constitution, which may be distributed as you wish.\n- Rolling a 2 or 15 will force an emergency story event that will require you to work on restoring your foal's health or defending it from predators.\n- Rolling a 1 will force a death saving roll. In the subsequent death saving roll, 1-10 will result in the death of the foal during the birthing process, shortly after birth, or in the weeks that follow due to natural causes (i.e., predation, illness, etc). 11-20 will result in a complicated birth in which the mare and foal will take a percentage away from their health.\n- All other rolls will result in a healthy mother and baby and is considered an uncomplicated birth.\n- As with prenatal foals, items can be used to ensure a healthy newborn and mother!`,
	],
	box4: [
		`![](/images/lifespans-banner-weanling.png)`,
		`*Your foal has survived its early childhood and being weaned from its mother (though they may try to drink every once in a while - naughty!) At this stage of the game, your foal has grown to be a yearling and, while their curiosity may still get the better of them, they've learned some of the tricks of the trade.*`,
		`a. Both weanlings and yearlings will gain a +10% modifier to their health stat.`,
	],
	box5: [
		`![](/images/lifespans-banner-twoyears.png)`,
		`Two is usually the time that your horse will leave their natal band to create a life of their own. Your horse has grown strong, and with each day they become more and more capable. Small predators, such as coyotes, run from you. If you're in a herd, cougars don't want anything to do with you.`,
		`a. Horses in this age range gain a +20% modifier to your horse's health stat.\n\nb. Horses at this age who attempt claims, steals, or challenges for NPCs receive an automatic 50% debuff to all stats and require two rolls for success where rolls are required.\n- Additionally, should a horse successfully claim a NPC, they will have an increased chance of losing that herd member for each roll.`,
	],
	box6: [
		`![](/images/lifespans-banner-fiveyears.png)`,
		`*Your horse is in their prime! Stallions have reached their full adult size and potentially gotten a herd, while mares are beginning to have successful births and, if they have personality and drive for it, are becoming lead mares.*`,
		`a. Stallions will gain a +30% modifier to their health (due to their tendency toward combat and higher metabolic rate).\n\nb. Mares will gain a +40% modifier to their health, however this is decreased to 20% during gestation.`,
	],
	box7: [
		`![](/images/lifespans-banner-fifteenyears.png)`,
		`*Wild horses tend not to live past twenty. As they grow older, they once again become susceptible to disease and frailty. Mares may continue to have foals until their death, but it becomes increasingly difficult and dangerous. Stallions usually return to their bachelor roots, retiring from band stallion life.*`,
		`a. All horses--no matter their sex--take an automatic -20% modifier to their health.\n\nb. Older mares that fall pregnant receive a -40% modifier to their health.\n\nc. All horses decrease the herd's travel time by 50%, but have a 50% chance of finding food and water during resource shortage events (droughts, wildfires, etc).`,
	],
};
</script>

<template>
	<DynamicInfo
		:hero="hero"
		:images="images"
		:content="content" />
</template>
