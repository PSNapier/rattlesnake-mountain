<script setup lang="ts">
import DynamicInfo from '@/components/custom/DynamicInfo.vue';
import { linkDict } from '@/composables/useLinkDictionary';

const hero = {
	title: 'Getting Started',
	description: 'A quick startup guide for the new player.',
};
const images = [
	{
		name: 'empiredog',
		link: 'https://www.deviantart.com/empiredog',
		path: '/images/gettingstarted-art-empiredog.png',
	},
	{
		name: 'iiyell',
		link: 'https://www.deviantart.com/iiyell',
		path: '/images/gettingstarted-art-iiyell.png',
	},
	{
		name: 'moontearcove',
		link: 'https://www.deviantart.com/moontearcove',
		path: '/images/gettingstarted-art-moontearcove.png',
	},
];
const content = {
	box1: [
		`![](/images/gettingstarted-banner-step1.png)`,
		`First, you'll want to read the [group rules](${linkDict.RULES.path}) and join our [Discord](${linkDict.DISCORD.path}). Discord is required to play on Rattlesnake Mountain, as we function across multiple art websites. The rules may also be quickly found on our Discord.`,
	],
	box2: [
		`![](/images/gettingstarted-banner-step2.png)`,
		`Next, you'll want to receive the **@Rattlesnake** role by reacting to the post at the bottom of the #rules channel in our Discord. By obtaining this role, it indicates to our staff that you comprehend and can adhere to the group's rules.`,
		`Then, if you're so inclined, click the "Join" button on our DeviantArt group page. DeviantArt is not required for playing on Rattlesnake Mountain, however all art or literature posted on DeviantArt that involves your characters on Rattlesnake Mountain should be submitted to the group.`,
	],
	box3: [
		`![](/images/gettingstarted-banner-step3.png)`,
		`Third, it's time to acquire your Welcome Package! This package will contain goodies that will help you get started. To obtain your Welcome Package, post the following form in the #join-up channel (this is also pinned in that channel).`,
		`**Your Preferred Name:** Sometimes our names differ from our art/literature account names. Make sure we get it right here.\n\n**Art/Literature Account Link:** Link your DeviantArt, Instagram, ToyHouse, etc here.\n\n**Preferred Pronouns:** Optional if you don't wish to disclose this information.\n\n**Recruited By\\*:** See the paragraph below.`,
		`**All Welcome Packages contain the following:**\n\n500 Scorpions\n\n1 White-Modifier Stone of your choice.\n\n1 Cream or 1 Pearl Stone of your choice.\n\n2 Randomized Stones for further character customization.\n\n2 Random Herbs for everyday use.\n\n2 Randomized Feathers for PvP.​`,
		`*\\* Were you recruited by another player? Sign you and your friend up for a Recruit-a-Friend package by simply listing their name in the form when you join. Note that players who spam-invite players (specifically players who join but never participate) to take advantage of this system will have their Recruit-A-Friend benefits removed permanently, including but not limited to all bonus stats removed from their characters and all additional scorpions removed/items revoked. **Please do not artificially inflate our player counts for your benefit!***`,
		`**Recruit-A-Friend packages contain the following in addition to the base Welcome Package:**\n\nA bonus of 100 Scorpions for you and your friend.\n\n2 Stones of your choice, each.\n\n2 Herbs of your choice, each.\n\n2 Feathers of your choice, each.\n\n+10 Scorpions per submission for 3 months of play, each.\n\n+1 extra stat per submission for 3 months of play, each.`,
	],
	box4: [
		`![](/images/gettingstarted-banner-step4.png)`,
		`Fourth, it's time to design or acquire your first character! For more information, check out our [Character Handbook](${linkDict.CHARACTER_HANDBOOK.path}) and [Character Creation](${linkDict.CHARACTER_UPLOAD.path}) pages. To submit your designs, head on over to our #leader-designs channel in our Discord and use the form there.`,
		`![](/images/gettingstarted-banner-charactercreationalts.png)`,
		`If you do not wish to create or design your own character, Rattlesnake Mountain offers a few alternatives.`,
		`**I. Randomized Claimable**\n\na. We will randomly choose from a list of our current claimable Bachelor Stallions and Herd Mares and supply you with three options of each gender. You will then choose one horse, and they will become your main character.\n\n• In order to choose a horse, please post the form found in our #requests-and-updates.`,
		`**II. Adopt a Claimable**\n\na. You may [browse](${linkDict.CLAIMABLE.path}) through our available horses and pick out one that you would like to play! Our DeviantArt account houses the most up-to-date information on which horses are available for claim.\n\nb. The horse's personality, history, benefits, and detriments are able to be changed at your discretion. If you find a horse that you would like to adopt, please claim it in #requests-and-updates.`,
		`**III. Ask a Rattlesnake Designer**\n\na. You may ask an official Rattlesnake Designer to design you a horse or design a rolled genotype yourself. An admin will randomly roll you five phenotypes, and you will pick one for the designer to work with you on creating!\n\nb. Please keep in mind, a design may take one-two weeks, depending upon complexity and designer availability.\n\nc. In order to roll your phenotypes, please use the Randomized Claimable form in #requests-and-updates. A Designer may then be found for your chosen genotype in #rattlesnake-studio. All designs should be submitted in #leader-designs when you are finished creating your character.`,
	],
	box5: [
		`![](/images/gettingstarted-banner-step5.png)`,
		`Fifth, after your design has been approved or selected and your herd's story thread has been created in the Discord, ensure your character is updated correctly on our DeviantArt account. A link to your character's upload will be posted in your upload approval response.`,
	],
	box6: [
		`![](/images/gettingstarted-banner-step6.png)`,
		`Feel free to create a reference image of your character. Alternatively, writers may opt to create an origin story for their character or commission a reference. **Note that this reference image/origin story only counts toward gaining stats and does not count as a roll. To have your reference counted, please post it in your herd's story thread.**`,
	],
	box7: [
		`![](/images/gettingstarted-banner-step7.png)`,
		`Last but not least, it's time to draw or write about your character for the first time and get started on your journey! If you want to get familiar with the lay of the land, our map will be of use. Should you require some inspiration, our initial prompts available for all characters are as follows:`,
		`**You wake one morning near an empty tank of water atop Rattlesnake Mountain and find that you are alone. The scent of man fills your nose, overpowering anything else. Unsettled, do you...**\n\n-> Head North, toward Pyramid Lake.\n\n-> Head Northeast, toward Rochester.\n\n-> Head Northwest, toward Lake Almanor.\n\n-> Head South, toward Carson City.\n\n-> Head Southeast, toward Grant Mountain.\n\n-> Head Southwest, toward Bald Mountain.\n\n-> Head East, toward Fallon.\n\n-> Head West, toward Tahoe National Forest.`,
		`*You will want to post this response - and all future responses - to the Thread created for your character on our Discord in #story-progression. Failure to post to your created Thread will likely result in your roll not being processed!*`,
		`**Every submission should include the form posted in the #herd-stories channel guidelines in the artist's comments of the piece/work. This form may also be found in your character's Story Thread.**`,
	],
};
</script>

<template>
	<DynamicInfo
		:hero="hero"
		:images="images"
		:content="content">
	</DynamicInfo>
</template>
