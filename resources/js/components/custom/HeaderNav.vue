<script setup lang="ts">
import { linkDict } from '@/composables/useLinkDictionary';

const mainLinks = [
	linkDict.HOME,
	linkDict.GETTING_STARTED,
	linkDict.WILDLIFE,
	linkDict.CONTACT_US,
];
const subLinksGettingStarted = [
	linkDict.RULES,
	linkDict.LORE,
	linkDict.CHARACTER_HANDBOOK,
	linkDict.STATS_LEVELING,
	linkDict.CHARACTER_UPLOAD,
	linkDict.SHOP,
];
const subLinksWildlife = [
	linkDict.LIFESPANS,
	linkDict.STORY_PROGRESSION,
	linkDict.CLAIMING_NPCS,
	linkDict.HERD_UNITY,
	linkDict.BREEDING_FOALING,
	linkDict.PLAYER_VS_PLAYER,
];
</script>

<template>
	<nav
		class="font-amaranth bg-new-orleans-300 [&>ul]:hover:bg-shakespeare-100 [&>ul]:hover:text-shakespeare-400 border-new-orleans-400 flex flex-row flex-wrap items-center justify-around border-b-4 text-2xl font-bold select-none xl:justify-center [&>ul]:rounded-t-lg [&>ul]:p-2 xl:[&>ul]:mx-4">
		<!-- Loop through mainLinks -->
		<ul
			v-for="link in mainLinks"
			:key="link.label"
			class="group relative">
			<a :href="link.path">{{ link.label }}</a>

			<!-- Dropdown for GETTING_STARTED -->
			<div
				v-if="link === linkDict.GETTING_STARTED"
				class="bg-shakespeare-100 text-shakespeare-400 absolute left-1/2 z-10 hidden min-w-max -translate-x-1/2 rounded-t-lg rounded-b-lg p-2 font-sans text-lg font-normal group-hover:block">
				<a
					v-for="sub in subLinksGettingStarted"
					:key="sub.label"
					:href="sub.path">
					<li
						class="hover:text-shakespeare-300 border-shakespeare-400 border-b-1 py-2">
						{{ sub.label }}
					</li>
				</a>
			</div>

			<!-- Dropdown for WILDLIFE -->
			<div
				v-if="link === linkDict.WILDLIFE"
				class="bg-shakespeare-100 text-shakespeare-400 absolute left-1/2 z-10 hidden min-w-max -translate-x-1/2 rounded-t-lg rounded-b-lg p-2 font-sans text-lg font-normal group-hover:block">
				<a
					v-for="sub in subLinksWildlife"
					:key="sub.label"
					:href="sub.path">
					<li
						class="hover:text-shakespeare-300 border-shakespeare-400 border-b-1 py-2">
						{{ sub.label }}
					</li>
				</a>
			</div>
		</ul>

		<div class="w-4 xl:w-12"></div>
		<input
			type="text"
			placeholder=" search..."
			class="bg-new-orleans-200 text-cape-palliser-700 rounded font-sans text-lg font-normal focus:ring-0 focus:outline-none" />
	</nav>
</template>
