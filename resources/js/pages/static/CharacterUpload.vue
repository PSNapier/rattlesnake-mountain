<script setup lang="ts">
import DynamicInfo from '@/components/custom/DynamicInfo.vue';
import { linkDict } from '@/composables/useLinkDictionary';

const hero = {
	title: 'Character Creation',
	description: 'Forms and other ways to create a character.',
};
const images = [
	{
		name: 'bee',
		link: '',
		path: '/images/characterupload-art-bee.png',
	},
	{
		name: 'Nikkayla',
		link: 'https://www.deviantart.com/Nikkayla',
		path: '/images/characterupload-art-Nikkayla.png',
	},
];
const content = {
	box1: [
		`![](/images/characterupload-banner-designchecklist.png)`,
		`*Before submitting your design(s), please be sure to run through this list. Going through this will help you spend less time waiting for your character(s) to be uploaded, and more time playing!*`,
		`- Have you read through our [Rules](${linkDict.RULES.path}), [Character Handbook](${linkDict.CHARACTER_HANDBOOK.path}), and [Lifespans](${linkDict.LIFESPANS.path}) pages?\n- Have you checked the saturation of your markings, coats, mane, and tail?\n- Have you colored the small bit of flesh outside the eye?\n- Is your horse on the correct lines? (Or have you specified why they are not?)\n- Have you double-checked your horse’s health, stat point allocation, and age?\n- Have you added your character's [Benefits and Detriments](${linkDict.BENE_DETS.path})?\n- Is the story you’ve given your horse realistic, sensical, and fits in with the world and lore of Rattlesnake Mountain? Remember, horses in real life are not vicious killers or "edgy" - in fact, family is super important to them and their development/success through life!\n- Have you checked that you have the needed items or the Scorpions for the genes you desire? You can double-check this in our bot command channel!\n- Is the skin color given to your horse true to their coat? Remember to check our [Design Guide](${linkDict.DESIGN_RULES.path}) if you aren't sure, or you can always ask for help!\n- Any physical defects your horse has (missing eyes, limbs, etc) will affect their ability to survive on Rattlesnake Mountain. \n- Again, your horse should not be actively evil or immoral; this is a semi-realistic game. As such, please keep in mind that horses are not evil or immoral! Animals are not inherently wicked, mean, etc nor are they like humans - at the end of the day, your character's goal is to survive and procreate.\n- Please keep your backstories realistic, and not overpowered - remember once again that you are playing as a horse! Backstories should not contain evil, abusive, or power-hungry characters unless they are human.`,
		`# *If you’re sure everything is dandy, go ahead and send your horse off. We can’t wait to see you in game!*`,
	],
	box2: [
		`![](/images/characterupload-banner-creationform.png)`,
		`*Once you have finished your Design, double-check that it follows the requirements in our [Character Handbook](${linkDict.CHARACTER_HANDBOOK.path}) and the checklist above. Next, upload the design with the below information filled out to a location where we can download a quality (non-pixelated) version from (Google Drive, Sta.sh, Instagram, etc)! Then, please fill out the form posted in our design channels to submit your design.*`,
		`***Please do not edit the type or form in any way and ensure the form is displayed exactly as follows and includes bold or italicized typeface where indicated. Occasionally, when copy/pasted, the form will add extra spaces between lines: please be sure to delete them or copy/paste the form from [this example](${linkDict.EXAMPLE_CHAR.path}). When we copy/paste this form into DeviantArt's submission box, it should exactly match [this example](${linkDict.EXAMPLE_CHAR.path}) in terms of layout. If you need help with text formatting or need a guide on submitting your first character, check out [this video](${linkDict.SUBMISSION_VID.path}).***`,
		`**ID #:** Keep this blank!\n\n**Name:**\n\n**Gender:** Mare or Stallion\n\n**Age:** Keep in mind that a three-year-old horse cannot be considered a Lead Mare/Stallion!\n\n**Breed:**\n\n**Social Rank:** Bachelor, Band/Lead Stallion, Lead Mare, Herd Mare, etc.\n\n**Phenotype:**\n\n**Genotype:** If you are not sure, an admin will be glad to assign you a genotype that matches your phenotype!\n\n**Personality:** May be as long or as short as you'd like.\n\n**History:** May be as long or as short as you'd like.\n\n**Player:** Your name here.`,
		`**Stat Distribution**\n\nStrength:\n\nDexterity:\n\nConstitution:\n\nIntelligence:\n\nWisdom:\n\nCharisma:\n\nExperience: Only gained via in-game events or by aging. This should be 0 if this is your first created character.\n\nHealth: Number determined by combining Strength and Constitution, with an additional percentage modifier based on Lifespan status (formula is as follows (Strength + Constitution) / Modifier Percentage). Ex: 7 + (40% Modifier) = 10HP`,
		`**Benefits:** Choose one or two.\n\nBenefit Name: Description copied from the Google Sheet.`,
		`**Detriments:** Choose one one or two.\n\nDetriment Name: Description copied from the Google Sheet.\n- Either choose from our list or create your own that will be approved at the discretion of our moderators and become a part of the game!`,
		`**Lineart & Background** by @/Cinrillon\n\nDesign by Name here.`,
	],
	box3: [
		`![](/images/characterupload-banner-charcreationalts.png)`,
		`If you do not wish to create or design your own character, Rattlesnake Mountain offers a few alternatives.`,
		`**I. Randomized Claimable**\n\na. We will randomly choose from a list of our current claimable Bachelor Stallions and Herd Mares and supply you with three options of each gender. You will then choose one horse, and they will become your main character.\n\n• In order to choose a horse, please post the form found in our #requests-and-updates.`,
		`**II. Adopt a Claimable**\n\na. You may [browse](${linkDict.CLAIMABLE.path}) through our available horses and pick out one that you would like to play! Our DeviantArt account houses the most up-to-date information on which horses are available for claim.\n\nb. The horse's personality, history, benefits, and detriments are able to be changed at your discretion. If you find a horse that you would like to adopt, please claim it in #requests-and-updates.`,
		`**III. Ask a Rattlesnake Designer**\n\na. You may ask an official Rattlesnake Designer to design you a horse or design a rolled genotype yourself. An admin will randomly roll you five phenotypes, and you will pick one for the designer to work with you on creating!\n\nb. Please keep in mind, a design may take one-two weeks, depending upon complexity and designer availability.\n\nc. In order to roll your phenotypes, please use the Randomized Claimable form in #requests-and-updates. A Designer may then be found for your chosen genotype in #rattlesnake-studio. All designs should be submitted in #leader-designs when you are finished creating your character.`,
	],
};
</script>

<template>
	<DynamicInfo
		:hero="hero"
		:images="images"
		:content="content" />
</template>
