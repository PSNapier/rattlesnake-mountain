<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const currentBackground = ref(0);

function cycleBackground() {
    const heroImg = document.querySelector('.hero-img') as HTMLElement;
    const preloadImages = [
        'url(/path/to/spring.jpg)',
        'url(/path/to/summer.jpg)',
        'url(/path/to/autumn.jpg)',
        'url(/path/to/winter.jpg)'
    ];

    preloadImages.forEach(image => {
        const img = new Image();
        img.src = image.replace('url(', '').replace(')', '').replace(/"/g, '');
    });

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
		<link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
	</Head>

    <nav class="flex flex-row flex-wrap justify-around xl:justify-center xl:[&>ul]:mx-4 items-center font-amaranth font-bold text-2xl bg-new-orleans-300 [&>ul]:p-2 [&>ul]:hover:bg-shakespeare-100 [&>ul]:hover:text-shakespeare-400 border-b-4 border-new-orleans-400 [&>ul]:rounded-t-lg select-none">
        <ul><a href="#">Home</a></ul>
        <ul class="relative group">
            <a href="#">Getting Started</a>
            <div class="absolute hidden group-hover:block bg-shakespeare-100 text-shakespeare-400 rounded-b-lg font-sans font-normal text-lg p-2 left-0 right-0 min-w-max">
                  <a href="#"><li class="py-2 hover:text-shakespeare-300 border-b-1 border-shakespeare-400">Test</li></a>
                  <a href="#"><li class="py-2 hover:text-shakespeare-300 border-b-1 border-shakespeare-400">Test</li></a>
                  <a href="#"><li class="pt-2 hover:text-shakespeare-300">Test</li></a>
            </div>
        </ul>
        <ul><a href="#">Wildlife</a></ul>
        <ul><a href="#">Contact Us</a></ul>
        <div class="w-4 xl:w-12"></div>
            <input
            type="text"
            placeholder=" search..."
            class="bg-new-orleans-200 text-cape-palliser-700 rounded focus:outline-none focus:ring-0 font-sans font-normal text-lg"
            >
      </nav>

      <div @click="cycleBackground" class="h-120 xl:h-140 hero-img flex flex-col justify-end select-none">
            <div
            class="p-6 bg-cape-palliser-100 rounded-t-[40%] lg:rounded-t-[50%] text-center flex flex-col justify-center items-center">
                  <h1>Rattlesnake Mountain</h1>
                  <p>An ARPG for wild horse enthusiasts!</p>
            </div>
      </div>

    <div class="max-container grid grid-cols-1 lg:grid-cols-3 gap-4 p-4 text-center">
        <div class="lg:col-span-2 box">
            <h2>Hello there!</h2>
            <p>The battle-born state welcomes you.</p>
            <br>
            <h4>About Us</h4>
            <p>Rattlesnake Mountain is a moderately realistic wild horse art roleplay game founded in the fall of 2019. Our setting is the Virginia Range in Nevada, the home of some hardy and sure-footed little horses that--while invasive--have made a home of the harsh desert. These horses have fiery souls and courageous hearts; but will that be enough for your character to survive alongside them?</p>
        </div>

        <div class="box">
            <h2>News</h2>
            <h5 class="border-b-1 border-new-orleans-500 mb-2">{{ new Date().toLocaleString('default', { month: 'long', year: 'numeric' }) }}</h5>
            <p>An activity check is now open on our Discord. Be sure to respond to it by September 30th, 2023 @ 11:59PM MT (Mountain Time). Otherwise, we're currently looking for 2 Story Progression Rollers to help out! If you're interested in applying, head over here.</p>
        </div>

        <div class="lg:col-span-3 text-center box">
            <h2><span class="text-4xl">New? Get started <a href="#">here.</a></span></h2>
        </div>
        </div>

        <div class="bg-cape-palliser-500">
        <div class="max-container p-4 py-8 col-span-3 grid grid-cols-1 lg:grid-cols-3 gap-4 text-center">
            <div class="box-alt">
                <h3><a href="https://www.deviantart.com/rattlesnake-mountain">@Rattlesnake-Mountain</a></h3>
                <img src="">
                <p>Find our DeviantArt Group here.</p>
            </div>

            <div class="box-alt">
                <h3><a href="https://www.deviantart.com/rattlesnakeadmin">@rattlesnakeadmin</a></h3>
                <img src="">
                <p>This account handles all imports and their information.</p>
            </div>

            <div class="box-alt">
                <h3><a href="https://discord.gg/rArZNnkCfE">@discord</a></h3>
                <img src="">
                <p>Discord is required for play!</p>
            </div>
            </div>
        </div>


      <footer class="max-container text-center p-4 text-mine-shaft-600 text-sm">
            <p>Rattlesnake Mountain is a horse art roleplay game by <a href="#">siat-s.deviantart.com</a></p>
            <p>Copyright © Siat-s {{ new Date().getFullYear() }}</p>
            <p>Website by <a href="#">Abature Studio</a></p>
      </footer>
</template>
