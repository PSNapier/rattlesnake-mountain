<template>
	<div
		class="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4 py-12 sm:px-6 lg:px-8 dark:bg-gray-900">
		<div class="w-full max-w-md space-y-8">
			<div>
				<h2
					class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
					Development Site Access
				</h2>
				<p
					class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
					Enter the development password to continue
				</p>
			</div>
			<form
				class="mt-8 space-y-6"
				@submit.prevent="submit">
				<div>
					<label
						for="dev_password"
						class="sr-only"
						>Password</label
					>
					<input
						id="dev_password"
						v-model="form.dev_password"
						name="dev_password"
						type="password"
						required
						class="relative block w-full appearance-none rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-900 placeholder-gray-500 focus:z-10 focus:border-indigo-500 focus:ring-indigo-500 focus:outline-none sm:text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-400"
						placeholder="Enter password" />
					<div
						v-if="errors?.dev_password"
						class="mt-2 text-sm text-red-600 dark:text-red-400">
						{{ errors.dev_password[0] }}
					</div>
				</div>

				<div>
					<button
						type="submit"
						:disabled="processing"
						class="group relative flex w-full justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50">
						<span
							v-if="processing"
							class="absolute inset-y-0 left-0 flex items-center pl-3">
							<svg
								class="h-5 w-5 animate-spin text-white"
								xmlns="http://www.w3.org/2000/svg"
								fill="none"
								viewBox="0 0 24 24">
								<circle
									class="opacity-25"
									cx="12"
									cy="12"
									r="10"
									stroke="currentColor"
									stroke-width="4"></circle>
								<path
									class="opacity-75"
									fill="currentColor"
									d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
							</svg>
						</span>
						{{
							processing ? 'Authenticating...' : 'Continue'
						}}
					</button>
				</div>
			</form>
		</div>
		<Footer />
	</div>
</template>

<script setup lang="ts">
import Footer from '@/components/custom/Footer.vue';
import { router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

defineProps({
	errors: Object,
});

const form = reactive({
	dev_password: '',
});

const processing = ref(false);

function submit() {
	processing.value = true;

	router.post('/dev-password', form, {
		onFinish: () => {
			processing.value = false;
		},
	});
}
</script>
