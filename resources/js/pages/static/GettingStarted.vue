<script setup lang="ts">
import Layout from '@/components/custom/Layout.vue';
import { linkDict } from '@/composables/useLinkDictionary';
</script>

<template>
	<Layout title="Getting Started">
		<template #hero>
			<h1>Getting Started</h1>
			<p>A quick startup guide for the new player.</p>
		</template>

		<!-- Main content for this page -->
		<div class="max-container grid grid-cols-1 gap-4 p-4 lg:grid-cols-3">
			<div class="box lg:col-span-2">
				<img
					src="/images/gettingstarted-banner-step1.png"
					alt="Step Banner"
					class="mb-2 h-auto w-full rounded-lg" />
				<div>
					<p>
						First, you'll want to read the group
						<a :href="linkDict.RULES.path">rules</a> and join
						our <a :href="linkDict.DISCORD.path">Discord</a>.
						Discord is required to play on Rattlesnake
						Mountain, as we function across multiple art
						websites. The rules may also be quickly found on
						our Discord.
					</p>
				</div>
			</div>

			<div class="lg:row-span-2">
				<div class="box">
					<img
						src="/images/gettingstarted-art-empiredog.png"
						alt="Art by empiredog"
						class="h-auto w-full rounded-xl" />
				</div>
				<div class="mt-2 text-center text-sm">
					Art by
					<a
						href="https://www.deviantart.com/empiredog"
						target="_blank"
						rel="noopener noreferrer"
						class="text-shakespeare-400 underline">
						@empiredog
					</a>
				</div>
			</div>

			<div class="box lg:col-span-2">
				<img
					src="/images/gettingstarted-banner-step2.png"
					alt="Step Banner"
					class="mb-2 h-auto w-full rounded-lg" />
				<div>
					<p>
						Next, you'll want to receive the @Rattlesnake role
						by reacting to the post at the bottom of the
						#rules channel in our Discord. By obtaining this
						role, it indicates to our staff that you
						comprehend and can adhere to the group's rules.
					</p>
					<br />
					<p>
						Then, if you're so inclined, click the "Join"
						button on our DeviantArt group page. DeviantArt is
						not required for playing on Rattlesnake Mountain,
						however all art or literature posted on DeviantArt
						that involves your characters on Rattlesnake
						Mountain should be submitted to the group.
					</p>
				</div>
			</div>

			<div class="box lg:col-span-2">
				<img
					src="/images/gettingstarted-banner-step3.png"
					alt="Step Banner"
					class="mb-2 h-auto w-full rounded-lg" />
				<div>
					<p>
						Third, it's time to acquire your Welcome Package!
						This package will contain goodies that will help
						you get started. To obtain your Welcome Package,
						post the following form in the #join-up channel
						(this is also pinned in that channel).
					</p>
					<br />
					<p>
						Your Preferred Name: Sometimes our names differ
						from our art/literature account names. Make sure
						we get it right here. Art/Literature Account Link:
						Link your DeviantArt, Instagram, ToyHouse, etc
						here. Preferred Pronouns: Optional if you don't
						wish to disclose this information. Recruited By*:
						See the paragraph below.
					</p>
					<br />
					<ul class="list-inside list-disc">
						All Welcome Packages contain the following:
						<li>500 Scorpions</li>
						<li>1 White-Modifier Stone of your choice</li>
						<li>1 Cream or 1 Pearl Stone of your choice</li>
						<li>
							2 Randomized Stones for further character
							customization
						</li>
						<li>2 Random Herbs for everyday use</li>
						<li>2 Randomized Feathers for PvP</li>
					</ul>
					<br />
					<p>
						* Were you recruited by another player? Sign you
						and your friend up for a Recruit-a-Friend package
						by simply listing their name in the form when you
						join. Note that players who spam-invite players
						(specifically players who join but never
						participate) to take advantage of this system will
						have their Recruit-A-Friend benefits removed
						permanently, including but not limited to all
						bonus stats removed from their characters and all
						additional scorpions removed/items revoked. Please
						do not artificially inflate our player counts for
						your benefit!
					</p>
					<br />
					<ul class="mt-2 list-inside list-disc">
						Recruit-A-Friend packages contain the following in
						addition to the base Welcome Package:

						<li>
							A bonus of 100 Scorpions for you and your
							friend.
						</li>
						<li>2 Stones of your choice, each.</li>
						<li>2 Herbs of your choice, each.</li>
						<li>2 Feathers of your choice, each.</li>
						<li>
							+10 Scorpions per submission for 3 months of
							play, each.
						</li>
						<li>
							+1 extra stat per submission for 3 months of
							play, each.
						</li>
					</ul>
				</div>
			</div>

			<!-- Empty div for layout consistency -->
			<div></div>

			<div class="box row-span-2 lg:col-span-2">
				<img
					src="/images/gettingstarted-banner-step4.png"
					alt="Step Banner"
					class="mb-2 h-auto w-full rounded-lg" />
				<div>
					<p>
						Fourth, it's time to design or acquire your first
						character! For more information, check out our
						<a :href="linkDict.CHARACTER_HANDBOOK.path"
							>Character Handbook</a
						>
						and
						<a :href="linkDict.CHARACTER_UPLOAD.path"
							>Character Creation</a
						>
						pages. To submit your designs, head on over to our
						#leader-designs channel in our Discord and use the
						form there.
					</p>
					<br />
				</div>
				<img
					src="/images/gettingstarted-banner-charactercreationalts.png"
					alt="Character Creation Alternatives Banner" />
				<div>
					<p class="italic">
						If you do not wish to create or design your own
						character, Rattlesnake Mountain offers a few
						alternatives.
					</p>
					<br />
					<p><strong>I. Randomized Claimable</strong></p>
					<p>
						a. We will randomly choose from a list of our
						current claimable Bachelor Stallions and Herd
						Mares and supply you with three options of each
						gender. You will then choose one horse, and they
						will become your main character.
					</p>
					<p>
						• In order to choose a horse, please post the form
						found in our #requests-and-updates.
					</p>
					<br />
					<p><strong>II. Adopt a Claimable</strong></p>
					<p>
						a. You may browse through our available horses and
						pick out one that you would like to play! Our
						DeviantArt account houses the most up-to-date
						information on which horses are available for
						claim.
					</p>
					<p>
						b. The horse's personality, history, benefits, and
						detriments are able to be changed at your
						discretion. If you find a horse that you would
						like to adopt, please claim it in
						#requests-and-updates.
					</p>
					<br />
					<p><strong>III. Ask a Rattlesnake Designer</strong></p>
					<p>
						a. You may ask an official Rattlesnake Designer to
						design you a horse or design a rolled genotype
						yourself. An admin will randomly roll you five
						phenotypes, and you will pick one for the designer
						to work with you on creating!
					</p>
					<p>
						b. Please keep in mind, a design may take one-two
						weeks, depending upon complexity and designer
						availability.
					</p>
					<p>
						c. In order to roll your phenotypes, please use
						the Randomized Claimable form in
						#requests-and-updates. A Designer may then be
						found for your chosen genotype in
						#rattlesnake-studio. All designs should be
						submitted in #leader-designs when you are finished
						creating your character.
					</p>
				</div>
			</div>

			<div class="lg:row-span-2">
				<div class="box">
					<img
						src="/images/gettingstarted-art-iiyell.png"
						alt="Art by iiyell"
						class="h-auto w-full rounded-xl" />
				</div>
				<div class="mt-2 text-center text-sm">
					Art by
					<a
						href="https://www.deviantart.com/iiyell"
						target="_blank"
						rel="noopener noreferrer"
						class="text-shakespeare-400 underline">
						@iiyell
					</a>
				</div>
			</div>

			<div class="box lg:col-span-2">
				<img
					src="/images/gettingstarted-banner-step5.png"
					alt="Step Banner"
					class="mb-2 h-auto w-full rounded-lg" />
				<div>
					<p>
						Fifth, after your design has been approved or
						selected and your herd's story thread has been
						created in the Discord, ensure your character is
						updated correctly on our DeviantArt account. A
						link to your character's upload will be posted in
						your upload approval response.
					</p>
				</div>
			</div>

			<div class="lg:row-span-2">
				<div class="box">
					<img
						src="/images/gettingstarted-art-moontearcove.png"
						alt="Art by moontearcove"
						class="h-auto w-full rounded-xl" />
				</div>
				<div class="mt-2 text-center text-sm">
					Art by
					<a
						href="https://www.deviantart.com/moontearcove"
						target="_blank"
						rel="noopener noreferrer"
						class="text-shakespeare-400 underline">
						@moontearcove
					</a>
				</div>
			</div>

			<div class="box lg:col-span-2">
				<img
					src="/images/gettingstarted-banner-step6.png"
					alt="Step Banner"
					class="mb-2 h-auto w-full rounded-lg" />
				<div>
					<p>
						Feel free to create a reference image of your
						character. Alternatively, writers may opt to
						create an origin story for their character or
						commission a reference.
						<strong
							>Note that this reference image/origin story
							only counts toward gaining stats and does not
							count as a roll. To have your reference
							counted, please post it in your herd's story
							thread.</strong
						>
					</p>
				</div>
			</div>

			<div class="box lg:col-span-2">
				<img
					src="/images/gettingstarted-banner-step7.png"
					alt="Step Banner"
					class="mb-2 h-auto w-full rounded-lg" />
				<div>
					<p>
						Last but not least, it's time to draw or write
						about your character for the first time and get
						started on your journey! If you want to get
						familiar with the lay of the land, our
						<a :href="linkDict.MAP.path">map</a>
						will be of use. Should you require some
						inspiration, our initial prompts available for all
						characters are as follows:
					</p>
					<br />
					<p>
						<strong
							>You wake one morning near an empty tank of
							water atop Rattlesnake Mountain and find that
							you are alone. The scent of man fills your
							nose, overpowering anything else. Unsettled,
							do you...</strong
						>
					</p>
					<p>&rarr; Head North, toward Pyramid Lake.</p>
					<p>&rarr; Head Northeast, toward Rochester.</p>
					<p>&rarr; Head Northwest, toward Lake Almanor.</p>
					<p>&rarr; Head South, toward Carson City.</p>
					<p>&rarr; Head Southeast, toward Grant Mountain.</p>
					<p>&rarr; Head Southwest, toward Bald Mountain.</p>
					<p>&rarr; Head East, toward Fallon.</p>
					<p>&rarr; Head West, toward Tahoe National Forest.</p>
					<br />
					<p class="italic">
						You will want to post this response - and all
						future responses - to the Thread created for your
						character on our Discord in #story-progression.
						Failure to post to your created Thread will likely
						result in your roll not being processed!
					</p>
					<br />
					<p>
						<strong>
							Every submission should include the form
							posted in the #herd-stories channel
							guidelines in the artist's comments of the
							piece/work. This form may also be found in
							your character's Story Thread.</strong
						>
					</p>
				</div>
			</div>
		</div>
	</Layout>
</template>
