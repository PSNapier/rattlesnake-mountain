<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Footer from './Footer.vue';
import HeaderNav from './HeaderNav.vue';

function cycleBackground() {
	const heroImg = document.querySelector('.hero-img') as HTMLElement;
	if (heroImg) {
		const backgroundImage = getComputedStyle(heroImg).backgroundImage;
		if (backgroundImage.includes('spring')) {
			heroImg.style.backgroundImage = backgroundImage.replace(
				'spring',
				'summer',
			);
		} else if (backgroundImage.includes('summer')) {
			heroImg.style.backgroundImage = backgroundImage.replace(
				'summer',
				'autumn',
			);
		} else if (backgroundImage.includes('autumn')) {
			heroImg.style.backgroundImage = backgroundImage.replace(
				'autumn',
				'winter',
			);
		} else if (backgroundImage.includes('winter')) {
			heroImg.style.backgroundImage = backgroundImage.replace(
				'winter',
				'spring',
			);
		}
	}
}
</script>

<template>
	<Head>
		<slot name="head" />
	</Head>

	<HeaderNav />

	<div
		@click="cycleBackground"
		class="hero-img flex h-140 flex-col justify-end select-none md:h-120 xl:h-160">
		<div
			class="bg-cape-palliser-100 flex flex-col items-center justify-center rounded-t-[40%] p-6 text-center lg:rounded-t-[50%]">
			<slot name="hero">
				<!-- Default hero content if not provided -->
				<h1>Rattlesnake Mountain</h1>
				<p>An ARPG for wild horse enthusiasts!</p>
			</slot>
		</div>
	</div>

	<!-- Main page content slot -->
	<slot />

	<Footer />
</template>
