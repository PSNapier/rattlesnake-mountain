<script setup lang="ts">
import Layout from '@/components/custom/Layout.vue';
import { computed } from 'vue';

interface CmsBox {
	id: string;
	span: 1 | 2 | 3;
	style: 'box' | 'box-alt' | 'box-centered';
	html: string;
}

const props = withDefaults(
	defineProps<{
		hero: { title: string; description: string | null };
		content: CmsBox[];
		comingSoon?: boolean;
		notPublic?: boolean;
	}>(),
	{ comingSoon: false, notPublic: false },
);

const spanClasses: Record<CmsBox['span'], string> = {
	1: 'lg:col-span-1',
	2: 'lg:col-span-2',
	3: 'lg:col-span-3',
};

function boxClasses(box: CmsBox): string[] {
	// space-y-2 stands in for the per-block wrapper divs the markdown
	// renderer used to emit: without it the paragraphs inside a box collide.
	const classes: string[] = ['space-y-2'];
	classes.push(box.style === 'box-alt' ? 'box-alt' : 'box');
	if (box.style === 'box-centered') classes.push('text-center');
	classes.push(spanClasses[box.span]);
	return classes;
}

const boxes = computed(() => props.content);
</script>

<template>
	<Layout
		:title="hero.title"
		class="info-page">
		<template #hero>
			<h1>{{ hero.title }}</h1>
			<p>{{ hero.description }}</p>
		</template>

		<!-- Staff-only visibility notice: shown when an admin previews a hidden page. -->
		<div
			v-if="notPublic"
			class="max-container p-4 pb-0">
			<div
				class="border-cape-palliser-500 bg-cape-palliser-50 text-cape-palliser-950 rounded-xl border p-4">
				<p class="font-bold">Hidden Page</p>
				<p>
					This page is hidden from the public. Only staff can see
					it right now.
				</p>
			</div>
		</div>

		<!-- Deferred feature notice: the page below is rules documentation, not a
		     playable in-app system yet. -->
		<div
			v-if="comingSoon"
			class="max-container p-4 pb-0">
			<div
				class="border-new-orleans-500 bg-new-orleans-50 text-cape-palliser-950 rounded-xl border p-4">
				<p class="font-bold">Coming Soon</p>
				<p>
					This part of the game has not moved onto the site yet.
					The rules below are here to read, but play still runs
					through our
					<a
						href="https://discord.gg/rArZNnkCfE"
						target="_blank"
						rel="noopener noreferrer"
						class="text-shakespeare-500 underline">
						Discord </a
					>. Nothing on this page submits a turn.
				</p>
			</div>
		</div>

		<!-- Main content for this page -->
		<div
			class="max-container grid grid-cols-1 items-start gap-4 p-4 lg:grid-cols-3">
			<div
				v-for="box in boxes"
				:key="box.id"
				:class="boxClasses(box)"
				v-html="box.html"></div>
		</div>
	</Layout>
</template>
