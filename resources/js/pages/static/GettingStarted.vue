<script setup lang="ts">
import DynamicInfo from '@/components/custom/DynamicInfo.vue';
// import { linkDict } from '@/composables/useLinkDictionary';

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
</script>

<template>
	<DynamicInfo
		:hero="hero"
		:images="images"
		class="[&>div]:box [&>div:space-y-x]">
		<div>
			<img
				src="/images/gettingstarted-banner-step1.png"
				alt="" />
			<p>
				First, you'll want to read the
				<a href="${linkDict.RULES.path}">group rules</a> and join
				our <a href="${linkDict.DISCORD.path}">Discord</a>. Discord
				is required to play on Rattlesnake Mountain, as we function
				across multiple art websites. The rules may also be quickly
				found on our Discord.
			</p>
		</div>

		<div class="">
			<img
				src="/images/gettingstarted-banner-step2.png"
				alt="" />
			<p>
				Next, you'll want to receive the
				<strong>@Rattlesnake</strong> role by reacting to the post
				at the bottom of the #rules channel in our Discord. By
				obtaining this role, it indicates to our staff that you
				comprehend and can adhere to the group's rules.
			</p>
			<p>
				Then, if you're so inclined, click the "Join" button on our
				DeviantArt group page. DeviantArt is not required for
				playing on Rattlesnake Mountain, however all art or
				literature posted on DeviantArt that involves your
				characters on Rattlesnake Mountain should be submitted to
				the group.
			</p>
		</div>

		<div class="box space-y-2">
			<img
				src="/images/gettingstarted-banner-step3.png"
				alt="" />
			<p>
				Third, it's time to acquire your Welcome Package! This
				package will contain goodies that will help you get started.
				To obtain your Welcome Package, post the following form in
				the #join-up channel (this is also pinned in that channel).
			</p>
			<ul>
				<li>
					<strong>Your Preferred Name:</strong> Sometimes our
					names differ from our art/literature account names.
					Make sure we get it right here.
				</li>
				<li>
					<strong>Art/Literature Account Link:</strong> Link your
					DeviantArt, Instagram, ToyHouse, etc here.
				</li>
				<li>
					<strong>Preferred Pronouns:</strong> Optional if you
					don't wish to disclose this information.
				</li>
				<li>
					<strong>Recruited By*:</strong> See the paragraph
					below.
				</li>
			</ul>
			<ul>
				<strong>All Welcome Packages contain the following:</strong>
				<li>500 Scorpions</li>
				<li>1 White-Modifier Stone of your choice.</li>
				<li>1 Cream or 1 Pearl Stone of your choice.</li>
				<li>
					2 Randomized Stones for further character
					customization.
				</li>
				<li>2 Random Herbs for everyday use.</li>
				<li>2 Randomized Feathers for PvP.</li>
			</ul>
			<p>
				<em
					>* Were you recruited by another player? Sign you and
					your friend up for a Recruit-a-Friend package by simply
					listing their name in the form when you join. Note that
					players who spam-invite players (specifically players
					who join but never participate) to take advantage of
					this system will have their Recruit-A-Friend benefits
					removed permanently, including but not limited to all
					bonus stats removed from their characters and all
					additional scorpions removed/items revoked.
					<strong
						>Please do not artificially inflate our player
						counts for your benefit!</strong
					></em
				>
			</p>
			<ul>
				<strong
					>Recruit-A-Friend packages contain the following in
					addition to the base Welcome Package:</strong
				>
				<li>A bonus of 100 Scorpions for you and your friend.</li>
				<li>2 Stones of your choice, each.</li>
				<li>2 Herbs of your choice, each.</li>
				<li>2 Feathers of your choice, each.</li>
				<li>
					+10 Scorpions per submission for 3 months of play,
					each.
				</li>
				<li>
					+1 extra stat per submission for 3 months of play,
					each.
				</li>
			</ul>
		</div>
	</DynamicInfo>
</template>
