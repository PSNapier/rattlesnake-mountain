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
		<div
			class="max-container grid grid-cols-1 gap-4 p-4 text-center lg:grid-cols-3">
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
				<div></div>
			</div>

			<div class="box lg:col-span-2">
				<img
					src="/images/gettingstarted-banner-step3.png"
					alt="Step Banner"
					class="mb-2 h-auto w-full rounded-lg" />
				<div></div>
			</div>

			<!-- Empty div for layout consistency -->
			<div></div>

			<div class="box row-span-2 lg:col-span-2">
				<img
					src="/images/gettingstarted-banner-step4.png"
					alt="Step Banner"
					class="mb-2 h-auto w-full rounded-lg" />
				<div></div>
				<img
					src="/images/gettingstarted-banner-charactercreationalts.png"
					alt="Character Creation Alternatives Banner"
					class="mb-2 h-auto w-full rounded-lg" />
				<div></div>
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
				<div></div>
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
				<div></div>
			</div>

			<div class="box lg:col-span-2">
				<img
					src="/images/gettingstarted-banner-step7.png"
					alt="Step Banner"
					class="mb-2 h-auto w-full rounded-lg" />
				<div></div>
			</div>
		</div>
	</Layout>
</template>
