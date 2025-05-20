<script setup lang="ts">
import { linkDict } from '@/composables/useLinkDictionary';
</script>

<template>
	<nav
		class="font-amaranth bg-new-orleans-300 [&>ul]:hover:bg-shakespeare-100 [&>ul]:hover:text-shakespeare-400 border-new-orleans-400 flex flex-row flex-wrap items-center justify-around border-b-4 text-2xl font-bold select-none xl:justify-center [&>ul]:rounded-t-lg [&>ul]:p-2 xl:[&>ul]:mx-4"
	>
		<ul>
			<a href="{{ linkDict.HOME.path }}">{{ linkDict.HOME.label }}</a>
		</ul>
		<ul class="group relative">
			<a href="{{ linkDict.GETTING_STARTED.path }}">{{ linkDict.GETTING_STARTED.label }}</a>
			<div
				class="bg-shakespeare-100 text-shakespeare-400 absolute right-0 left-0 hidden min-w-max rounded-b-lg p-2 font-sans text-lg font-normal group-hover:block"
			>
				<a href="#"><li class="hover:text-shakespeare-300 border-shakespeare-400 border-b-1 py-2">Test</li></a>
				<a href="#"><li class="hover:text-shakespeare-300 border-shakespeare-400 border-b-1 py-2">Test</li></a>
				<a href="#"><li class="hover:text-shakespeare-300 pt-2">Test</li></a>
			</div>
		</ul>
		<ul>
			<a href="{{  linkDict.WILDLIFE.path }}">{{ linkDict.WILDLIFE.label }}</a>
		</ul>
		<ul>
			<a href="{{  linkDict.CONTACT_US.path }}">{{ linkDict.CONTACT_US.label }}</a>
		</ul>
		<div class="w-4 xl:w-12"></div>
		<input
			type="text"
			placeholder=" search..."
			class="bg-new-orleans-200 text-cape-palliser-700 rounded font-sans text-lg font-normal focus:ring-0 focus:outline-none"
		/>
	</nav>
</template>
