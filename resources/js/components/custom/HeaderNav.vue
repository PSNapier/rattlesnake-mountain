<script setup lang="ts">
import { linkDict } from '@/composables/useLinkDictionary';
import { Bars3Icon } from '@heroicons/vue/24/solid';
import { ref } from 'vue';

const navOpen = ref(false);
function toggleNavOpen() {
	navOpen.value = !navOpen.value;
}

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
	<!-- Desktop -->
	<nav
		class="font-amaranth bg-new-orleans-300 [&>ul]:hover:bg-shakespeare-100 [&>ul]:hover:text-shakespeare-400 border-new-orleans-400 hidden flex-row flex-wrap items-center justify-around border-b-4 text-2xl font-bold select-none lg:flex xl:justify-center [&>ul]:rounded-t-lg [&>ul]:p-2 xl:[&>ul]:mx-4">
		<ul
			v-for="link in mainLinks"
			:key="link.label"
			class="group relative">
			<a :href="link.path">{{ link.label }}</a>

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

		<!-- <div class="flex flex-row">
			<a href="/login">
				<button
					class="text-new-orleans-300 bg-cape-palliser-500 hover:bg-new-orleans-500 hover:text-new-orleans-100 flex cursor-pointer gap-4 rounded-l-xl p-2 font-sans text-sm transition-colors">
					Login
				</button>
			</a>
			<a href="/register">
				<button
					class="text-new-orleans-300 bg-cape-palliser-500 hover:bg-new-orleans-500 hover:text-new-orleans-100 flex cursor-pointer gap-4 rounded-r-xl p-2 font-sans text-sm transition-colors">
					Register
				</button>
			</a>
		</div> -->
	</nav>

	<!-- Mobile -->
	<nav class="relative text-xl lg:hidden">
		<div
			class="bg-new-orleans-300 border-new-orleans-400 flex flex-row items-center justify-end gap-4 border-b-4">
			<!-- <a
				href="/login"
				class="underline"
				>Login</a
			>
			<a
				href="/register"
				class="underline"
				>Register</a
			> -->
			<button
				class="bg-cape-palliser-500 text-new-orleans-200 m-2 inline-flex items-center justify-center rounded-md p-1 focus:outline-none"
				@click="toggleNavOpen">
				<Bars3Icon class="size-8" />
			</button>
		</div>
		<div
			class="bg-new-orleans-400 text-new-orleans-100 absolute top-full right-0 z-50 w-full p-4 text-right leading-8"
			v-if="navOpen">
			<ul
				v-for="link in mainLinks"
				:key="link.label"
				class="group relative mb-4">
				<a :href="link.path">{{ link.label }}</a>

				<div
					v-if="link === linkDict.GETTING_STARTED"
					class="">
					<a
						v-for="sub in subLinksGettingStarted"
						:key="sub.label"
						:href="sub.path">
						<li class="">
							{{ sub.label }}
						</li>
					</a>
				</div>

				<div
					v-if="link === linkDict.WILDLIFE"
					class="">
					<a
						v-for="sub in subLinksWildlife"
						:key="sub.label"
						:href="sub.path">
						<li class="">
							{{ sub.label }}
						</li>
					</a>
				</div>
			</ul>
		</div>
	</nav>
</template>
