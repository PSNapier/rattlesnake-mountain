<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
	<Head title="Rattlesnake Mountain - Home">
		<link rel="preconnect" href="https://rsms.me/" />
		<link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
	</Head>

	<nav class="flex justify-around xl:justify-center xl:[&>ul]:mx-4 items-center font-amaranth font-bold text-2xl bg-new-orleans-300 [&>ul]:p-2 [&>ul]:hover:bg-shakespeare-100 [&>ul]:hover:text-shakespeare-400 border-b-4 border-new-orleans-400 [&>ul]:rounded-t-lg">
        <a href="#"><ul>Home</ul></a>
        <ul class="relative group">
            <a href="#">Getting Started</a>
            <div class="absolute hidden group-hover:block bg-shakespeare-100 text-shakespeare-400 rounded-b font-sans font-normal text-lg p-2 left-0 right-0 min-w-max">
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

      <div class="">
            <div class="h-120 xl:h-140 hero-img flex flex-col justify-end">
                  <div
                  class="p-6 bg-cape-palliser-100 rounded-t-[50%] text-center flex flex-col justify-center items-center">
                        <h1>Rattlesnake Mountain</h1>
                        <p>An ARPG for wild horse enthusiasts!</p>
                  </div>
            </div>
      </div>

	<div>
		<div>
            <h2>Hello there!</h2>
            <p>The battle-born state welcomes you.</p>
            <h4>About Us</h4>
            <p>Rattlesnake Mountain is a moderately realistic wild horse art roleplay game founded in the fall of 2019. Our setting is the Virginia Range in Nevada, the home of some hardy and sure-footed little horses that--while invasive--have made a home of the harsh desert. These horses have fiery souls and courageous hearts; but will that be enough for your character to survive alongside them?</p>
      </div>

      <div>
            <h2>News</h2>
            <h5>{{ new Date().toLocaleString('default', { month: 'long', year: 'numeric' }) }}</h5>
            <p>An activity check is now open on our Discord. Be sure to respond to it by September 30th, 2023 @ 11:59PM MT (Mountain Time). Otherwise, we're currently looking for 2 Story Progression Rollers to help out! If you're interested in applying, head over here.</p>
      </div>

      <div>
            <h2>New? Get started <a href="#">here.</a></h2>
      </div>
	</div>

      <div>
            <div>
                  <h3>@Link</h3>
                  <img src="">
                  <p>Linkage verbage...</p>
            </div>

            <div>
                  <h3>@Link</h3>
                  <img src="">
                  <p>Linkage verbage...</p>
            </div>

            <div>
                  <h3>@Link</h3>
                  <img src="">
                  <p>Linkage verbage...</p>
            </div>

            <div>
                  <h3>@Link</h3>
                  <img src="">
                  <p>Linkage verbage...</p>
            </div>
      </div>

      <footer>
            <p>Rattlesnake Mountain is a horse art roleplay game by <a href="#">siat-s.deviantart.com</a></p>
            <p>Copyright © Siat-s {{ new Date().getFullYear() }}</p>
            <p>Website Designed & Hosted by <a href="#">Abature Studio</a></p>
      </footer>
</template>
