<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
	computed,
	nextTick,
	onBeforeUnmount,
	onMounted,
	ref,
	watch,
} from 'vue';
import { formatPublishedAt, type CmsAnnouncement } from './boxes';

/**
 * Inner content of the home page's pinned News slot: the newest announcement,
 * clamped, with a read-more link into the archive. The box wrapper and its
 * width come from the caller, so view and edit mode share this markup.
 */
const props = defineProps<{
	announcements: CmsAnnouncement[];
}>();

const latest = computed(() => props.announcements[0] ?? null);

// A max-height clamp rather than a server excerpt, so lists and images in the
// lead survive. The fade only shows when something is actually cut off.
const bodyRef = ref<HTMLElement | null>(null);
const clamped = ref(false);
let observer: ResizeObserver | null = null;

function measure() {
	const el = bodyRef.value;
	clamped.value = el !== null && el.scrollHeight > el.clientHeight + 1;
}

onMounted(() => {
	measure();
	if (typeof ResizeObserver !== 'undefined' && bodyRef.value) {
		// Images load after mount and change the height.
		observer = new ResizeObserver(measure);
		observer.observe(bodyRef.value.firstElementChild ?? bodyRef.value);
	}
});

onBeforeUnmount(() => observer?.disconnect());

watch(latest, () => nextTick(measure));
</script>

<template>
	<h2>News</h2>

	<p v-if="!latest">No announcements right now. Check back soon.</p>

	<article
		v-else
		data-testid="home-news">
		<h5 class="border-new-orleans-500 mb-2 border-b-1">
			{{ latest.title }}
		</h5>
		<div
			ref="bodyRef"
			class="max-h-64 overflow-hidden"
			:class="{
				'[mask-image:linear-gradient(to_bottom,#000_60%,transparent)]':
					clamped,
			}"
			:data-clamped="clamped">
			<div
				class="cms-announcement space-y-2"
				v-html="latest.body"></div>
		</div>
		<p
			v-if="latest.published_at"
			class="mt-1 text-sm italic">
			{{ formatPublishedAt(latest.published_at) }}
		</p>
		<Link
			href="/news"
			class="mt-2 inline-block font-bold underline"
			>Read more news</Link
		>
	</article>
</template>
