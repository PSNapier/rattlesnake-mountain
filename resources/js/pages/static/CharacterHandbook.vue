<script setup lang="ts">
import Layout from '@/components/custom/Layout.vue';
import { linkDict } from '@/composables/useLinkDictionary';
import markdownit from 'markdown-it';

const md = markdownit();
function dedent(str: string) {
	return str.replace(/^\t+/gm, '');
}

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
		link: false,
		path: '/images/characterhandbook-art-bee.png',
	},
	{
		name: 'empiredog',
		link: 'https://www.deviantart.com/empiredog',
		path: '/images/characterhandbook-art-empiredog.png',
	},
];
const content = {
	box1: [
		{
			type: 'image',
			path: '/images/characterhandbook-step-start.png',
		},
		{
			type: 'md',
			content: `*All playable character designs must be done on
			[our lineart base](${linkDict.LINEART.path}).
			They will then be uploaded to the Rattlesnake Mountain
			accounts on [DeviantArt](${linkDict.ADMIN.path}). This is
			done in an attempt to keep all basic information about
			characters in one place for easy access. Of course,
			once your design is approved and accepted to the group,
			you are welcome to upload the design to your own
			account.*
			\n
			*You may choose two existing NPC horses as your character's sire and dam. However, the combination of their genes must be able to create your character's genotype/phenotype. If you need help with this, please let a **@Designer** know in our Discord!*`,
		},
	],
	box2: [
		{
			type: 'image',
			path: '/images/characterhandbook-step-coatcolors.png',
		},
		{
			type: 'md',
			content: `*We ask that you keep coat colors realistic. Before your character(s) can be accepted to the group, the design will undergo a realism check--it is possible that an admin may ask you to change your horse's design if it does not adhere to [our design rules](${linkDict.DESIGN_RULES.path}).*
			\n
			a. If you believe that you are wrongly being asked to change something (we all make mistakes!), please speak with a staff member privately.
			\n
			b. Chestnuts, Bays, Blacks, Duns, and Roans are fairly common among the Rattlesnake Mountain herds and are free to make in either homozygous or heterozygous form. Other Paint markings, Champagnes, Creams, Pearls, Greys, etc are considerably rare and require items (Stones) to add to these genes to genotypes.
			- Rare genes must be applied via Stones or they can be rolled for when breeding.
			- When you join, you are given the opportunity to acquire some of these genes through Stones in your Welcome Package.
			- Players may trade Stones between themselves using the #trading-post channel.
			- Finally, Stones and horses with rare genes may occasionally be found in the wild.
     		c. Eye color is typically brown, unless there is a coat modifier that calls for
			a different color (i.e., paint markings and blue eyes.)
			- Eye colors are not restricted and may be any color you wish, including heterochromia, etc.`,
		},
	],
	box3: [
		{
			type: 'image',
			path: '/images/characterhandbook-step-breeds.png',
		},
		{
			type: 'md',
			content: `*All horses on Rattlesnake Mountain, unless an item has been applied or it has been a random encounter, are considered Mustangs.*
			\n
			a. If a "breed-changing" item has been applied, please ensure that your horse is either a hot-blooded or warm-blooded breed.
			\n
			b. [Cold-blooded breeds, or Drafts](${linkDict.DRAFT_BREED_REF.path}), would have a very difficult time on the range and therefore are only be available during special events or through items.
			\n
			c. Burros are not permitted as lead characters. That said, they may be added to your herd with a special item or found in the wild as an NPC to claim. They may be designed using our burro lines, which are found within [our import line file](${linkDict.LINEART.path}).`,
		},
	],
};
</script>

<template>
	<Layout title="Home">
		<template #hero>
			<h1>{{ hero.title }}</h1>
			<p>{{ hero.description }}</p>
		</template>

		<!-- Main content for this page -->
		<div
			class="max-container grid grid-cols-1 items-start gap-4 p-4 lg:grid-cols-[2fr_1fr]">
			<!-- Info column -->
			<div class="flex flex-col gap-4">
				<div
					v-for="(blocks, boxKey) in content"
					:key="boxKey"
					class="box">
					<template
						v-for="(block, index) in blocks"
						:key="index">
						<div
							v-if="block.type === 'md'"
							v-html="
								md.render(dedent(block.content ?? ''))
							"
							class="space-y-2" />
						<img
							v-else-if="block.type === 'image'"
							:src="block.path"
							alt=""
							class="mb-2 rounded-xl" />
					</template>
				</div>
			</div>

			<!-- Image column -->
			<div class="flex flex-col justify-between gap-4">
				<div
					v-for="(img, i) in images"
					:key="'img-' + i">
					<div class="box">
						<img
							:src="img.path"
							:alt="`Art by ${img.name}`"
							class="h-auto w-full rounded-xl" />
					</div>
					<div class="mt-2 text-center text-sm">
						Art by
						<template v-if="img.link">
							<a
								:href="
									typeof img.link === 'string'
										? img.link
										: undefined
								"
								target="_blank"
								rel="noopener noreferrer"
								class="text-shakespeare-400 underline">
								@{{ img.name }}
							</a>
						</template>
						<template v-else> @{{ img.name }} </template>
					</div>
				</div>
			</div>
		</div>
	</Layout>
</template>
