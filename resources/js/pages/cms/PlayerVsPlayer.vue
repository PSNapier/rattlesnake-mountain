<script setup lang="ts">
import DynamicInfo from '@/components/custom/DynamicInfo.vue';
import { linkDict } from '@/composables/useLinkDictionary';

const hero = {
	title: `Player vs. Player`,
	description: `Fight for what you want.`,
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
		`![](/images/pvp-banner-events.png)`,
		`Player-versus-Player--or PvP--are events that are generated and created by you! It is up to you whether your character will become a dominant force in Rattlesnake Mountain or be a wallflower. NPC steals and challenges can only get you so much renown, after all!`,
		`Note: PvP should be initiated for the sake of fun and character development; it should never used as a way to attack another player--any instance of this would be breaking our [number one rule](${linkDict.RULES.path})!`,
	],
	box2: [
		`![](/images/pvp-banner-steals.png)`,
		`*Steals are quick, one response attempts to acquire up to two mares (and their below yearling-age foals) from another herd.*`,
		`a. In order to initiate a steal, either draw or write your attempt to coerce or drive the mare(s) away from their herd and submit it to your character thread in our Discord with the following form filled out:`,
		`Opponent: Link your opponent's DeviantArt upload here.\n\nTarget(s): Link all targeted horses' DeviantArt uploads here, including any foals that are below yearling age.`,
		`b. A @Moderator will then tag the stallion or mare's player(s) in a reply to your steal post.\n- The opponent will then have one week to make a response in the form of art or literature, else the steal will default to the initiator.\n\nc. Once the opponent has responded, the stats, benefits, detriments, and items (if any are used) of all horses involved (the initiator, the opponent(s), and the potential prize(s) are weighed against each other by a PvP admin.\n- A little bit of luck will factor into these steals, so even if your opponent outweighs you stat-wise, there is still a chance of victory. While steals are typically less risky, there is always a chance for injury--this will be rolled for in your victory rolls.\n\nd. It is possible to gain items, scorpions and stats through steals.\n- Each piece submitted will have a 25% chance of finding one of these things, and their quantity and/or rarity will be determined through an extra roll.`,
	],
	box3: [
		`![](/images/pvp-banner-challenges.png)`,
		`*Challenges, as opposed to steals, take a little bit more time and effort--but have a lot more risk and a lot more reward.*`,
		`a. Outright fights between stallions or even mares are quite rare in the wild, as they can be very detrimental to the health of both fighters.\n- As such, each response in a challenge has a 50% chance of resulting in an injury.\n- Mares, as they typically do not fight in this manner, will receive a 75% chance of resulting in injury,\n- Each image will result in a roll on a d100, with a 1 resulting in the accidental death of the horse depicted.\n\nb. Challenges can be issued for more than two mares in a herd or a horse's entire herd.\n- In order to initiate a challenge, either draw or write your horse posturing up against their opponent and post it in your character's thread on our Discord with the following form:`,
		`Opponent: Link your opponent's DeviantArt upload here.\n\nTarget(s): Link all targeted horses' DeviantArt uploads here, including any foals that are below yearling age.`,
		`b. A @Moderator will then tag the stallion or mare's player(s) in a reply to your challenge post.\n- The opponent will then have one week to make a response in the form of art or literature, else the challenge will default to the initiator. Their response must be posted in the initiator's character thread.\n\nc. The initiator then must depict the fight in either art or literature and submit it to their character thread.\n- The initiator has two weeks to make this response.\n\nd. The opponent then must show their side of the fight in either art or literature.\n- The opponent has two weeks to make this response.\n\ne. All challenges have one month to finish completely, else they will be called early.\n\nf. Once the challenge has finished, the stats, benefits, detriments, and items (if any are used) of all horses involved (the initiator, the opponent(s), and the potential prize(s) are weighed against each other by a PvP admin.\n- Luck will play a factor in these fights.\n\ng. Once the admin has the default results, they will be posted in a Google form and a vote will be called for the entire playerbase to take part in.\n- The playerbase must always supply a reason why they're choosing a specific horse as the victor; this can be done anonymously, but this feedback will be posted on the Google form for the challenge artists to see.`,
	],
	box4: [
		`![](/images/pvp-banner-spars.png)`,
		`*Spars are practice challenges that have little to no risk outside of injury. Stallions--especially bachelors--spar with each other quite frequently to hone their battle skills and keep themselves in shape. A stallion with a lot of spars under his belt will fair better against injury in challenges and steals.*`,
		`a. Spars are an agreed-upon PvP setting, and may have their own predetermined outcome.\n- If they do not have a predetermined winner, a @Moderator will weigh them in the same manner as steals.\n- In order to initiate a spar, submit an image or literature with the following form filled out to your character's thread.`,
		`Opponent: Link your opponent's DeviantArt upload here.`,
		`\n- Once a spar has finished, it is your responsibility to tag a @Moderator to roll the results if the results have not been decided already.`,
	],
	box5: [
		`![](/images/pvp-banner-trades.png)`,
		`*A much more peaceful method of getting what your character wants, trading mares or of-age (usually older than a year) is unsanctioned by the admin team. All we ask is that you don't harass someone for their mare(s) and you try to keep things fair. In order to initiate a trade, simply post in the #trading-post channel of our Discord. We recommend creating a story (the mare(s) wandered away from their old herd, their herd stallion encourage it, etc) to go along with this trade, of course!*`,
	],
};
</script>

<template>
	<DynamicInfo
		:hero="hero"
		:images="images"
		:content="content" />
</template>
