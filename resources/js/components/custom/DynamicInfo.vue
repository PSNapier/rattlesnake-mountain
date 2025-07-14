<script setup lang="ts">
import Layout from '@/components/custom/Layout.vue';
import { ContentBlock } from '@/types/handbook';
// @ts-expect-error markdown-it types not being imported?
import markdownit from 'markdown-it';

const md = markdownit();
function dedent(str: string) {
	return str.replace(/^\t+/gm, '');
}

defineProps<{
	hero: { title: string; description: string };
	images: { name: string; link: string | false; path: string }[];
	content: Record<string, ContentBlock[]>;
}>();

// const hero = {
// 	title: 'Title',
// 	description: 'Description.',
// };
// const images = [
// 	{
// 		name: 'name',
// 		link: 'https://siat-s.deviantart.com',
// 		path: '/images/characterhandbook-art-home-hero-spring.png',
// 	},
// ];
// const content = {
// 	box1: [
// 		{
// 			type: 'image',
// 			path: '/images/characterhandbook-art-home-hero-spring.png',
// 		},
// 		{
// 			type: 'md',
// 			content: `Markdown text...`,
// 		},
// 	],
// };
</script>

<template>
	<Layout :title="hero.title">
		<template #hero>
			<h1>{{ hero.title }}</h1>
			<p>{{ hero.description }}</p>
		</template>

		<!-- Main content for this page -->
		<div
			class="max-container grid grid-cols-1 items-start gap-4 p-4 lg:grid-cols-[2fr_1fr]">
			<!-- Info column -->
			<div class="flex flex-col gap-4">
				<div
					v-for="(blocks, boxKey) in content"
					:key="boxKey"
					class="box">
					<template
						v-for="(block, index) in blocks"
						:key="index">
						<div
							v-if="block.type === 'md'"
							v-html="
								md.render(dedent(block.content ?? ''))
							"
							class="space-y-2" />
						<img
							v-else-if="block.type === 'image'"
							:src="block.path"
							alt=""
							class="mb-2 rounded-xl" />
					</template>
				</div>
			</div>

			<!-- Image column -->
			<div class="hidden flex-col justify-between gap-4 lg:flex">
				<div
					v-for="(img, i) in images"
					:key="'img-' + i">
					<div class="box">
						<img
							:src="img.path"
							:alt="`Art by ${img.name}`"
							class="h-auto w-full rounded-xl" />
					</div>
					<div class="mt-2 text-center text-sm">
						Art by
						<template v-if="img.link">
							<a
								:href="
									typeof img.link === 'string'
										? img.link
										: undefined
								"
								target="_blank"
								rel="noopener noreferrer"
								class="text-shakespeare-400 underline">
								@{{ img.name }}
							</a>
						</template>
						<template v-else> @{{ img.name }} </template>
					</div>
				</div>
			</div>
		</div>
	</Layout>
</template>
