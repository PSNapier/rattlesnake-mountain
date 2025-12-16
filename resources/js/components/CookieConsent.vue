<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const showBanner = ref(false);

onMounted(() => {
	const hasConsent = localStorage.getItem('cookie_consent') === 'true';
	if (!hasConsent) {
		showBanner.value = true;
	}
});

const acceptCookies = () => {
	localStorage.setItem('cookie_consent', 'true');
	showBanner.value = false;
};
</script>

<template>
	<div
		v-if="showBanner"
		class="fixed right-0 bottom-0 left-0 z-50 border-t border-gray-200 bg-white p-4 shadow-lg sm:right-4 sm:bottom-4 sm:left-4 sm:max-w-lg sm:rounded-lg sm:border dark:border-gray-800 dark:bg-gray-900">
		<div class="flex items-start gap-4">
			<div class="flex-1">
				<p class="text-sm text-gray-700 dark:text-gray-300">
					We use essential cookies to provide site functionality.
					By continuing to use this site, you consent to our use
					of cookies. See our
					<Link
						:href="route('privacy_policy')"
						class="underline"
						>Privacy Policy</Link
					>
					for details.
				</p>
			</div>
			<Button
				@click="acceptCookies"
				size="sm"
				class="shrink-0">
				Accept
			</Button>
		</div>
	</div>
</template>
