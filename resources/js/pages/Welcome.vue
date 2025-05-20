<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Footer from '../components/custom/Footer.vue';
import HeaderNav from '../components/custom/HeaderNav.vue';
// import { ref } from 'vue';

function cycleBackground() {
	const heroImg = document.querySelector('.hero-img') as HTMLElement;
	const preloadImages = ['url(/path/to/spring.jpg)',
		'url(/path/to/summer.jpg)',
		'url(/path/to/autumn.jpg)',
		'url(/path/to/winter.jpg)'];

	if (heroImg) {
		const backgroundImage = getComputedStyle(heroImg).backgroundImage;
		console.log('Current background image URL:', backgroundImage);
		if (backgroundImage.includes('spring')) {
			heroImg.style.backgroundImage = backgroundImage.replace('spring', 'summer');
		} else if (backgroundImage.includes('summer')) {
			heroImg.style.backgroundImage = backgroundImage.replace('summer', 'autumn');
		} else if (backgroundImage.includes('autumn')) {
			heroImg.style.backgroundImage = backgroundImage.replace('autumn', 'winter');
		} else if (backgroundImage.includes('winter')) {
			heroImg.style.backgroundImage = backgroundImage.replace('winter', 'spring');
		}
	}
}
</script>

<template>
	<Head title="Rattlesnake Mountain - Home">
		<link rel="preconnect" href="https://rsms.me/" />
		<link
			rel="stylesheet"
			href="https://rsms.me/inter/inter.css"
			/>
	</Head>

	<HeaderNav />

	<div
		@click="cycleBackground"
		class="hero-img flex h-120 flex-col justify-end select-none xl:h-140"
		>
		<div class="bg-cape-palliser-100 flex flex-col items-center justify-center rounded-t-[40%] p-6 text-center lg:rounded-t-[50%]">
			<h1>
				Rattlesnake Mountain
			</h1>
			<p>
				An ARPG for wild horse enthusiasts!
			</p>
		</div>
	</div>

	<div class="max-container grid grid-cols-1 gap-4 p-4 text-center lg:grid-cols-3">
		<div class="box lg:col-span-2">
			<h2>
				Hello there!
			</h2>
			<p>
				The battle-born state welcomes you.
			</p>
			<br />
			<h4>
				About Us
			</h4>
			<p>
				Rattlesnake Mountain is a moderately realistic wild horse art roleplay game founded in the fall of 2019. Our setting is the
				Virginia Range in Nevada, the home of some hardy and sure-footed little horses that--while invasive--have made a home of the
				harsh desert. These horses have fiery souls and courageous hearts; but will that be enough for your character to survive
				alongside them?
			</p>
		</div>

		<div class="box">
			<h2>
				News
			</h2>
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
				An activity check is now open on our Discord. Be sure to respond to it by September 30th, 2023 @ 11:59PM MT (Mountain Time).
				Otherwise, we're currently looking for 2 Story Progression Rollers to help out! If you're interested in applying, head over
				here.
			</p>
		</div>

		<div class="box text-center lg:col-span-3">
			<h2>
				<span class="text-4xl">
					New? Get started <a href="#">
						here.
					</a>
				</span>
			</h2>
		</div>
	</div>

	<div class="bg-cape-palliser-500">
		<div class="max-container col-span-3 grid grid-cols-1 gap-4 p-4 py-8 text-center lg:grid-cols-3">
			<div class="box-alt">
				<h3>
					<a href="https://www.deviantart.com/rattlesnake-mountain">
						@Rattlesnake-Mountain
					</a>
				</h3>
				<img src="" />
				<p>
					Find our DeviantArt Group here.
				</p>
			</div>

			<div class="box-alt">
				<h3>
					<a href="https://www.deviantart.com/rattlesnakeadmin">
						@rattlesnakeadmin
					</a>
				</h3>
				<img src="" />
				<p>
					This account handles all imports and their information.
				</p>
			</div>

			<div class="box-alt">
				<h3>
					<a href="https://discord.gg/rArZNnkCfE">
						@discord
					</a>
				</h3>
				<img src="" />
				<p>
					Discord is required for play!
				</p>
			</div>
		</div>
	</div>

	<Footer />
</template>
