<script setup lang="ts">
import DynamicInfo from '@/components/custom/DynamicInfo.vue';
import { linkDict } from '@/composables/useLinkDictionary';

const hero = {
	title: `Breeding & Foaling`,
	description: `Create new life.`,
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
		`![](/images/breedingfoaling-banner-breeding.png)`,
		`*Passing on their genes and ensuring their lineage survives is the end goal of most animals in the wild. Here you will find information on how breeding and foaling works on Rattlesnake Mountain.*`,
	],
	box2: [
		`![](/images/breedingfoaling-banner-notes.png)`,
		`a. Breeding and foaling may only occur at specific places and times.\n- Mares may come into heat at any time after they turn two years of age. Heat or Estrus is a randomly rolled status during the Breeding and Foaling season and lasts until the mare conceives or two real life weeks have passed.\n- The Breeding and Foaling season lasts from the end of Winter to the last few weeks of Autumn. Dates are indicated on our calendar, which can be found on our [home page](${linkDict.HOME.path}).\n- In real life, the gestational period for foals is ~11-12 months. On Rattlesnake Mountain, mares may start giving birth the following Spring, the due date provided at the time of breeding. Players may choose to delay the due date to ensure their foal arrives at their preferred time.\n- Your herd must be at a checkpoint to submit a breeding. This means that your mares have a chance of being courted by other stallions during regular story progression.`,
	],
	box3: [
		`![](/images/breedingfoaling-banner-submit.png)`,
		`a. Prior to submitting a breeding request, please take note of the following.\n- Breeding attempts must be accompanied by a non-graphic or descriptive image or short story - keep it PG or a fade-to-black - during a Checkpoint.\n- Breeding may only be attempted twice per estrus cycle.\n- Please keep in mind that breedings are not always 100% effective, nor do they guarantee a live foal that will live a long life or a healthy mare. Predators have to eat, too, and as a prey animal, the life of a wild horse is nothing if not dangerous.\n\nb. Breeding requests should be posted in the #breeding-rolls channel with the correct form filled out.`,
	],
	box4: [
		`![](/images/breedingfoaling-banner-results.png)`,
		`*After you have submitted your breeding request, a @Moderator will respond in your character's thread with the following:*`,
		`**Sex:** To be chosen by you unless the gender ratios in the entire game are skewed unrealistically. That is, if there are more stallions than mares.\n\n**Breed:** Should be Mustang, unless another breed has been introduced to your foal's bloodline.\n\n**Genotype & Phenotype:** You'll receive two to work with, unless the mare has twins or you opt for a reroll, in which case you may choose from all rolled genotypes and phenotypes. Other mutations will be indicated here as well.\n\n**Markings:** Will be left up to you, although some markings may be randomized.\n\n**Health & Stats:** This will denote the health of the mare and the foal at the time of conception and during birth. Mares will receive rolls throughout pregnancy as their stories continue.\n\n**Benefits & Detriments:** Some are inheritable and some are not. Breeding is the only way to achieve Legendary traits.`,
	],
	box5: [
		`![](/images/breedingfoaling-banner-designs.png)`,
		`*Received a healthy (or unhealthy but alive) foal? Wonderful! The next step is to design your foal using our official line art. Simply download [the line art](${linkDict.LINEART.path}), open the file in your favorite editor, and get to work on that foal design! If you need help or would like a @Designer to design your foal for you, please do not hesitate to ask a @Moderator or @Designer, respectively! Remember, while Google can be a great asset, it is not always 100% accurate in depicting correct patterns or base coats. Alternatively, you are welcome to check out [our design guide](${linkDict.DESIGN_RULES.path}).*`,
		`**Once you or a @Designer have designed your foal following the genotype given to you, please upload that file somewhere and submit that file for review in the #foal-designs channel in our Discord. Please ensure the following form is filled out. Do not change text or add any text effects.**`,
		`**ID #:** This is supplied to you in your foal results.\n\n**Gender:** Mare or Stallion, if the option was provided to you\n\n**Age:** Newborn\n\n**Breed:**\n\n**Social Rank:** Newborn\n\n**Phenotype:** Choose one of the phenotypes & genotypes that were provided to you in your foal results.\n\n**Genotype:**\n\n**Personality:** May be as long or as short as you'd like.\n\n**History:** May be as long or as short as you'd like.\n\n**Herd Leader:** Link your herd leader's DeviantArt upload here.`,
		`**Stat Distribution, provided to you with your foal results.**\n\nStrength:\n\nDexterity:\n\nConstitution:\n\nIntelligence:\n\nWisdom:\n\nCharisma:\n\nExperience:\n\nHealth:`,
		`**Benefits:** Provided to you with your foal results. If you have any in your Vault, you may add them here.\n\nBenefit Name: Description copied from the Google Sheet.`,
		`**Detriments:** Provided to you with your foal results. If you have any in your Vault, you may add them here.\n\nDetriment Name: Description copied from the Google Sheet.`,
		`**Lineart & Background** by @/Cinrillon\n\nDesign by Name here.`,
		`Link to your foal results.`,
	],
};
</script>

<template>
	<DynamicInfo
		:hero="hero"
		:images="images"
		:content="content" />
</template>
