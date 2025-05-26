<script setup lang="ts">
import LandingLinkBox from '@/components/custom/LandingLinkBox.vue';
import Layout from '@/components/custom/Layout.vue';
</script>

<template>
	<Layout title="Home">
		<template #hero>
			<h1>Rattlesnake Mountain</h1>
			<p>An ARPG for wild horse enthusiasts!</p>
		</template>

		<!-- Main content for this page -->
		<div
			class="max-container grid grid-cols-1 gap-4 p-4 text-center lg:grid-cols-3">
			<div class="box lg:col-span-2">
				<h2>Hello there!</h2>
				<p>The battle-born state welcomes you.</p>
				<br />
				<h4>About Us</h4>
				<p>
					Rattlesnake Mountain is a moderately realistic wild
					horse art roleplay game founded in the fall of 2019.
					Our setting is the Virginia Range in Nevada, the home
					of some hardy and sure-footed little horses that--while
					invasive--have made a home of the harsh desert. These
					horses have fiery souls and courageous hearts; but will
					that be enough for your character to survive alongside
					them?
				</p>
			</div>

			<div class="box">
				<h2>News</h2>
				mb-2
				<h5 class="border-new-orleans-500 mb-2 border-b-1">
					{{
						new Date().toLocaleString('default', {
							month: 'long',
							year: 'numeric',
						})
					}}
				</h5>
				<p>
					An activity check is now open on our Discord. Be sure
					to respond to it by September 30th, 2023 @ 11:59PM MT
					(Mountain Time). Otherwise, we're currently looking for
					2 Story Progression Rollers to help out! If you're
					interested in applying, head over here.
				</p>
			</div>

			<div class="box text-center lg:col-span-3">
				<h2>
					<span class="text-4xl">
						New? Get started <a href="#"> here. </a>
					</span>
				</h2>
			</div>
		</div>

		<div class="bg-cape-palliser-500">
			<div
				class="max-container col-span-3 grid grid-cols-1 gap-4 p-4 py-8 text-center lg:grid-cols-3">
				<LandingLinkBox
					title="@Rattlesnake-Mountain"
					href="https://www.deviantart.com/rattlesnake-mountain"
					imageSrc="/images/group-logo.png"
					imageAlt="DeviantArt Group logo"
					description="Find our DeviantArt Group." />

				<LandingLinkBox
					title="@rattlesnakeadmin"
					href="https://www.deviantart.com/rattlesnakeadmin"
					imageSrc="/images/admin-logo.png"
					imageAlt="Admin account logo"
					description="This account handles all imports and their information." />

				<LandingLinkBox
					title="@discord"
					href="https://discord.gg/rArZNnkCfE"
					imageSrc="/images/discord-logo.png"
					imageAlt="Discord server logo"
					description="Discord!" />
			</div>
		</div>
	</Layout>
</template>
