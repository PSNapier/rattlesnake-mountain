<script setup lang="ts">
import DynamicInfo from '@/components/custom/DynamicInfo.vue';
import { linkDict } from '@/composables/useLinkDictionary';

const hero = {
	title: 'Character Handbook',
	description: 'A basic guide on character creation.',
};
const images = [
	{
		name: 'HotnSpicy1',
		link: 'https://www.deviantart.com/hotnspicy1',
		path: '/images/characterhandbook-art-hotnspicy1.png',
	},
	{
		name: 'viscella',
		link: 'https://www.deviantart.com/viscella',
		path: '/images/characterhandbook-art-viscella.png',
	},
	{
		name: 'KarmaArt666',
		link: 'https://www.deviantart.com/KarmaArt666',
		path: '/images/characterhandbook-art-KarmaArt666.png',
	},
	{
		name: 'bee',
		link: '',
		path: '/images/characterhandbook-art-bee.png',
	},
	{
		name: 'empiredog',
		link: 'https://www.deviantart.com/empiredog',
		path: '/images/characterhandbook-art-empiredog.png',
	},
];
const content = {
	box1: `<img src="/images/characterhandbook-step-start.png" alt="testing" />
		<p><em>All playable character designs must be done on
		<a href="${linkDict.LINEART.path}">our lineart base</a>.
		They will then be uploaded to the Rattlesnake Mountain
		accounts on <a href="${linkDict.ADMIN.path}">DeviantArt</a>. This is
		done in an attempt to keep all basic information about
		characters in one place for easy access. Of course,
		once your design is approved and accepted to the group,
		you are welcome to upload the design to your own
		account.</em></p>
		<p><em>You may choose two existing NPC horses as your character's sire and dam. However, the combination of their genes must be able to create your character's genotype/phenotype. If you need help with this, please let a <strong>@Designer</strong> know in our Discord!</em></p>`,
	box2: `<img src="/images/characterhandbook-step-coatcolors.png" alt="" />
		<p><em>We ask that you keep coat colors realistic. Before your character(s) can be accepted to the group, the design will undergo a realism check--it is possible that an admin may ask you to change your horse's design if it does not adhere to <a href="${linkDict.DESIGN_RULES.path}">our design rules</a>.</em></p>
		<ol type="a">
		<li>If you believe that you are wrongly being asked to change something (we all make mistakes!), please speak with a staff member privately.</li>
		<li>Chestnuts, Bays, Blacks, Duns, and Roans are fairly common among the Rattlesnake Mountain herds and are free to make in either homozygous or heterozygous form. Other Paint markings, Champagnes, Creams, Pearls, Greys, etc are considerably rare and require items (Stones) to add to these genes to genotypes.
		<ul>
		<li>Rare genes must be applied via Stones or they can be rolled for when breeding.</li>
		<li>When you join, you are given the opportunity to acquire some of these genes through Stones in your Welcome Package.</li>
		<li>Players may trade Stones between themselves using the #trading-post channel.</li>
		<li>Finally, Stones and horses with rare genes may occasionally be found in the wild.</li>
		</ul>
		</li>
		<li>Eye color is typically brown, unless there is a coat modifier that calls for
a different color (i.e., paint markings and blue eyes.)
		<ul>
		<li>Eye colors are not restricted and may be any color you wish, including heterochromia, etc.</li>
		</ul>
		</li>
		</ol>`,
	box3: `<img src="/images/characterhandbook-step-breeds.png" alt="" />
		<p><em>All horses on Rattlesnake Mountain, unless an item has been applied or it has been a random encounter, are considered Mustangs.</em></p>
		<ol type="a">
		<li>If a "breed-changing" item has been applied, please ensure that your horse is either a hot-blooded or warm-blooded breed.</li>
		<li><a href="${linkDict.DRAFT_BREED_REF.path}">Cold-blooded breeds, or Drafts</a>, would have a very difficult time on the range and therefore are only be available during special events or through items.</li>
		<li>Burros are not permitted as lead characters. That said, they may be added to your herd with a special item or found in the wild as an NPC to claim. They may be designed using our burro lines, which are found within <a href="${linkDict.LINEART.path}">our import line file</a>.</li>
		</ol>`,
};
</script>

<template>
	<DynamicInfo
		:hero="hero"
		:images="images"
		:content="content" />
</template>
