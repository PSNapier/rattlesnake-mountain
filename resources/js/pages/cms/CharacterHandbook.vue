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
	box1: [
		`![](/images/characterhandbook-step-start.png)`,
		`All playable character designs must be done on [our lineart base](${linkDict.LINEART.path}). They will then be uploaded to the Rattlesnake Mountain accounts on [DeviantArt](${linkDict.ADMIN.path}). This is done in an attempt to keep all basic information about characters in one place for easy access. Of course, once your design is approved and accepted to the group, you are welcome to upload the design to your own account.`,
		`You may choose two existing NPC horses as your character's sire and dam. However, the combination of their genes must be able to create your character's genotype/phenotype. If you need help with this, please let a **@Designer** know in our Discord!`,
	],
	box2: [
		`![](/images/characterhandbook-step-coatcolors.png)`,
		`We ask that you keep coat colors realistic. Before your character(s) can be accepted to the group, the design will undergo a realism check--it is possible that an admin may ask you to change your horse's design if it does not adhere to our [design rules](${linkDict.DESIGN_RULES.path}).`,
		` a. If you believe that you are wrongly being asked to change something (we all make mistakes!), please speak with a staff member privately.b. Chestnuts, Bays, Blacks, Duns, and Roans are fairly common among the Rattlesnake Mountain herds and are free to make in either homozygous or heterozygous form. Other Paint markings, Champagnes, Creams, Pearls, Greys, etc are considerably rare and require items (Stones) to add to these genes to genotypes.\n- Rare genes must be applied via Stones or they can be rolled for when breeding.\n- When you join, you are given the opportunity to acquire some of these genes through Stones in your Welcome Package.\n- Players may trade Stones between themselves using the #trading-post channel.\n- Finally, Stones and horses with rare genes may occasionally be found in the wild.\n\nc. Eye color is typically brown, unless there is a coat modifier that calls for a different color (i.e., paint markings and blue eyes.)\n- Eye colors are not restricted and may be any color you wish, including heterochromia, etc.`,
	],
	box3: [
		`![](/images/characterhandbook-step-breeds.png)`,
		`*All horses on Rattlesnake Mountain, unless an item has been applied or it has been a random encounter, are considered Mustangs.*`,
		`a. If a "breed-changing" item has been applied, please ensure that your horse is either a hot-blooded or warm-blooded breed.\n\nb. [Cold-blooded breeds, or Drafts](${linkDict.DRAFT_BREED_REF.path}), would have a very difficult time on the range and therefore are only be available during special events or through items.\n\nc. Burros are not permitted as lead characters. That said, they may be added to your herd with a special item or found in the wild as an NPC to claim. They may be designed using our burro lines, which are found within our [import line file](${linkDict.LINEART.path}).`,
	],
	box4: [
		`![](/images/characterhandbook-step-sexrole.png)`,
		`*Players are welcome to choose either sex, but Lead Mares and Lead Stallions function in two completely different ways. **Individuals who wish to create LGBTQIA+ characters are encouraged to do so**--the following gender/sex roles are generally the rule and not the exception.*`,
		`a. Lead Mares are the decision-makers, the ones who determine if a mare that the Lead Stallion brings in will stay in the herd or not. They decide where to go, when to go, and how to go about going there.\n- They are the backbone of the herd's social structure, and often will determine whether or not a herd will be successful or not--a strong Lead Mare doesn't let any of her herdmates get stolen, and will occasionally go toe-to-toe with a rude bachelor or inexperienced band stallion.\n\nb. Lead Stallions are, generally speaking, the protectors. They spend their days at the back of or on the outskirts of the herd, relying on the Lead Mare to keep everyone in line.\n- Often, he will play-fight with bachelors and band stallions alike, honing his abilities and keeping himself in shape. They frequently give way to mares of any ranking in the herd, and spend a lot of their time making sure that wayward mares and fillies don't spend too much time in the company of other stallions, especially when they are in estrus.\n\nc. Unless they are joining alongside a herdmember, all newly created horses will be Bachelors or Lone Mares until they claim their first opposite-sex herdmember.`,
	],
	box5: [
		`![](/images/characterhandbook-step-age.png)`,
		`*Age is very important to a wild mustang. It determines their place in the world, and how successful they will be in terms of survival and reproduction.*`,
		` a. Mares and Stallions are typically rejected from or leave their birth herd by the age of two. At this point in time, mares will be absorbed into an existing herd and stallions will find or form a bachelor herd.\n\nb. From two until the age of six or seven, stallions will remain bachelors, honing their skills and roughing it. Occasionally, they might find themselves with a mare, but they will quickly lose it to a stronger band stallion. Mares, on the other hand, may have multiple foals, and grow into their herd, creating relationships with the other mares.\n\nc. At around eight, the horse has reached the prime of their life. They are strong and capable, usually finding themselves in the highest ranking socially, and continue to climb that ladder until they become too old.\n\nd. The average mustang's lifespan in the wild is 15-20 years.  Often, stallions will lose their bands and become bachelors again. Mares, however, will remain with her herd as an Elder until her death. As a result, mares usually live and procreate longer than stallions.\n\ne. For more information on our horse's lifespans and the modifiers related to age, please refer to our [Lifespan page](${linkDict.LIFESPANS.path}).`,
	],
	box6: [
		`![](/images/characterhandbook-step-stats.png)`,
		`*All horses are better skilled in one area than another, and Rattlesnake Mountain's horses are no different! Our Mustangs' stats--listed below--are divided into eight different categories. Those familiar with Dungeons and Dragons will recognize our categories--those unfamiliar, [here is an explanation using tomatoes](${linkDict.STATS_REF.path}).*`,
		`Strength:\n\nDexterity:\n\nConstitution:\n\nIntelligence:\n\nWisdom:\n\nCharisma:\n\nExperience:\n\nHealth:`,
		`**I. Overview**\n\na. In short, your horse's stats will determine how successful or unsuccessful they will be in Rattlesnake Mountain.\n- For example, a horse with a higher health stat will fair better against injuries than a horse with very little health.\n\nc. Age functions similarly to experience and is a determining factor that will influence your overall stats.\n- For more information on the average lifespan, [see this page](${linkDict.LIFESPANS.path}).​`,
		`**II. Character Creation**\n\na. All player characters begin with +1 stat in 2 chosen categories excluding Experience and Health (otherwise known as "proficiencies").\n- Proficiencies may be any stat category you'd like; ex. +1 Dexterity and +1 Charisma for a dashing rogue!\n\nb. All player characters begin with -2 stats in 2 chosen categories excluding Experience and Health (otherwise known as "incompetencies").\n- Incompetencies may be any stat category you'd like and may match a character's personality; ex. -1 Intelligence and -1 Dexterity for a horse who struggles with memory and the art of conversation!\n\nc. Proficiencies and Incompetencies can be leveled as your horse progresses through life. For more information on that, [check out this page](${linkDict.STATS_LEVELING.path}).`,
		`**III. Experience and Health**\n\na. Experience is gained solely by in-game events or by aging and may, occasionally, counteract deficits in your horse's stats. Regular level-up points may not be allocated into experience.\n- For example, a horse with more experience may pass charisma checks in order to avoid a conflict with another horse, whereas a younger and less experienced horse will not pass those charisma checks.\n\nb. Health is a combination of the Strength and Constitution categories and cannot be directly added to without increasing either Strength or Constitution. Regular level-up points may not be allocated into Health.\n- More health allows your horse to survive more injury events, or will allow them to fight a competitor longer.\n- While it may be tempting to pour all your stats into Strength and Constitution for those reasons, there are plenty of situations in Rattlesnake Mountain where the survival and success of your horse will depend on other stats.\n\nc. If a horse's health percentage hits 0%, they are automatically forced into a "saving throw" situation.\n- In these cases, the player must submit a healing image for that horse in order to roll against a d20. If the result of that d20 roll is a 1, the horse is marked as deceased in the game and removed from play unless an appropriate item is applied.\n- This is why herd checkpoints are important: they give you the opportunity to heal herd mates and prepare against the unknown!`,
		`**IV. Earning Stats**\n\nStats are gained for every piece of art or story written of your horse and may be distributed as you see fit. However, just how many stats your horse will gain for an image or story depends on the amount of effort you put into it.\n\na. For a breakdown of how we determine stats per roll, [see this page](${linkDict.STATS_LEVELING.path}).`,
		``,
	],
	box7: [
		`![](/images/characterhandbook-step-benefits.png)`,
		`*Benefits and Detriments function as a variety of character-defining traits. They may boost or lower stats, or they may provide other benefits, like a higher chance to encounter wildlife.*`,
		`a. A single horse may have up to four Benefits or Detriments and they may be swapped out as you please. Legendary traits cannot be removed from a horse.\n- All player-created (Herd Leader) characters may be created with one of each or two of each category (i.e., two benefits and two detriments; not two benefits and one detriment).\n- Characters may not be created with Rare or Legendary traits.\n- Additionally, Legendary traits can never be purchased with Scorpions or with real money. They must always be either bred for, earned over certain story arcs, or already exist on a claimable horse.\n\nb. Foal traits are rolled at random by moderators unless an item is applied during the breeding or a parent has an applicable breeding trait.\n\nc. Occasionally, game-wide events will have an effect on stats, benefits, and detriments to give them a sense of difficulty. After all, life as a wild horse is not easy!\n- Some story arcs will earn your horse(s) Benefits and Detriments.\n\nd. A comprehensive [list of all benefits and detriments can be found here](${linkDict.BENE_DETS.path}).`,
	],
};
</script>

<template>
	<DynamicInfo
		:hero="hero"
		:images="images"
		:content="content" />
</template>
