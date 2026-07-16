<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { onBeforeUnmount, ref, watch } from 'vue';

interface ReferrerOption {
	id: number;
	name: string;
}

const form = useForm({
	referrer_id: null as number | null,
	name: '',
	email: '',
	password: '',
	password_confirmation: '',
	rules_agreed: false,
});

const searchQuery = ref('');
const searchResults = ref<ReferrerOption[]>([]);
const selectedReferrer = ref<ReferrerOption | null>(null);
const searching = ref(false);
const showResults = ref(false);

let debounceTimer: ReturnType<typeof setTimeout> | null = null;
let abortController: AbortController | null = null;

async function searchReferrers(query: string) {
	const trimmed = query.trim();

	if (trimmed.length < 2) {
		searchResults.value = [];
		showResults.value = false;
		return;
	}

	abortController?.abort();
	abortController = new AbortController();
	searching.value = true;

	try {
		const response = await fetch(
			route('register.referrer-search', { q: trimmed }),
			{
				headers: {
					Accept: 'application/json',
					'X-Requested-With': 'XMLHttpRequest',
				},
				signal: abortController.signal,
			},
		);

		if (!response.ok) {
			searchResults.value = [];
			return;
		}

		searchResults.value = await response.json();
		showResults.value = true;
	} catch (error) {
		if ((error as Error).name !== 'AbortError') {
			searchResults.value = [];
		}
	} finally {
		searching.value = false;
	}
}

watch(searchQuery, (value) => {
	if (selectedReferrer.value && value !== selectedReferrer.value.name) {
		selectedReferrer.value = null;
		form.referrer_id = null;
	}

	if (debounceTimer) {
		clearTimeout(debounceTimer);
	}

	debounceTimer = setTimeout(() => {
		searchReferrers(value);
	}, 250);
});

function selectReferrer(option: ReferrerOption) {
	selectedReferrer.value = option;
	form.referrer_id = option.id;
	searchQuery.value = option.name;
	showResults.value = false;
	searchResults.value = [];
}

function clearReferrer() {
	selectedReferrer.value = null;
	form.referrer_id = null;
	searchQuery.value = '';
	searchResults.value = [];
	showResults.value = false;
}

onBeforeUnmount(() => {
	if (debounceTimer) {
		clearTimeout(debounceTimer);
	}
	abortController?.abort();
});

const submit = () => {
	form.transform((data) => {
		const rulesAgreed = data.rules_agreed ? 1 : 0;

		return {
			...data,
			rules_agreed: rulesAgreed,
			referrer_id: data.referrer_id || null,
		};
	}).post(route('register'), {
		onFinish: () => form.reset('password', 'password_confirmation'),
	});
};
</script>

<template>
	<AuthBase
		title="Create an account"
		description="Enter your details below to create your account">
		<Head title="Register" />

		<form
			@submit.prevent="submit"
			class="flex flex-col gap-6">
			<div class="grid gap-6">
				<div class="grid gap-2">
					<Label for="name">Name</Label>
					<Input
						id="name"
						type="text"
						required
						autofocus
						:tabindex="1"
						autocomplete="name"
						v-model="form.name"
						placeholder="Full name" />
					<InputError :message="form.errors.name" />
				</div>

				<div class="grid gap-2">
					<Label for="email">Email address</Label>
					<Input
						id="email"
						type="email"
						required
						:tabindex="2"
						autocomplete="email"
						v-model="form.email"
						placeholder="email@example.com" />
					<InputError :message="form.errors.email" />
				</div>

				<div class="grid gap-2">
					<Label for="password">Password</Label>
					<Input
						id="password"
						type="password"
						required
						:tabindex="3"
						autocomplete="new-password"
						v-model="form.password"
						placeholder="Password" />
					<InputError :message="form.errors.password" />
				</div>

				<div class="grid gap-2">
					<Label for="password_confirmation">Confirm password</Label>
					<Input
						id="password_confirmation"
						type="password"
						required
						:tabindex="4"
						autocomplete="new-password"
						v-model="form.password_confirmation"
						placeholder="Confirm password" />
					<InputError :message="form.errors.password_confirmation" />
				</div>

				<div class="relative grid gap-2">
					<Label for="referrer_search">Were you referred by someone?</Label>
					<div class="flex gap-2">
						<Input
							id="referrer_search"
							type="text"
							:tabindex="5"
							autocomplete="off"
							v-model="searchQuery"
							placeholder="Search username (optional)"
							@focus="showResults = searchResults.length > 0" />
						<Button
							v-if="selectedReferrer"
							type="button"
							variant="outline"
							:tabindex="6"
							@click="clearReferrer">
							Clear
						</Button>
					</div>
					<p
						v-if="searching"
						class="text-muted-foreground text-xs">
						Searching…
					</p>
					<ul
						v-if="showResults && searchResults.length > 0"
						class="bg-background absolute top-full z-10 mt-1 max-h-48 w-full overflow-auto rounded-md border shadow-md">
						<li
							v-for="option in searchResults"
							:key="option.id">
							<button
								type="button"
								class="hover:bg-muted w-full px-3 py-2 text-left text-sm"
								@click="selectReferrer(option)">
								{{ option.name }}
							</button>
						</li>
					</ul>
					<p
						v-else-if="showResults && searchQuery.trim().length >= 2 && !searching"
						class="text-muted-foreground text-xs">
						No matching players found.
					</p>
					<input
						type="hidden"
						name="referrer_id"
						:value="form.referrer_id ?? ''" />
					<InputError :message="form.errors.referrer_id" />
				</div>

				<div class="flex items-start gap-2">
					<input
						id="rules_agreed"
						type="checkbox"
						v-model="form.rules_agreed"
						class="text-primary focus:ring-primary mt-1 h-4 w-4 rounded border-gray-300"
						required
						:tabindex="7" />
					<Label
						for="rules_agreed"
						class="text-muted-foreground block cursor-pointer text-md font-normal">
						By registering, you agree to the site
						<a
							:href="route('rules')"
							class="underline"
							target="_blank"
							rel="noopener noreferrer"
							>rules</a
						>.
					</Label>
				</div>
				<InputError :message="form.errors.rules_agreed" />

				<div class="text-muted-foreground text-xs">
					By creating an account, you agree to us storing your
					information to provide you with account functionality.
					See our
					<Link
						:href="route('privacy_policy')"
						class="underline"
						>Privacy Policy</Link
					>
					for details.
				</div>

				<Button
					type="submit"
					class="mt-2 w-full"
					tabindex="8"
					:disabled="form.processing">
					<LoaderCircle
						v-if="form.processing"
						class="h-4 w-4 animate-spin" />
					Create account
				</Button>
			</div>

			<div class="text-muted-foreground text-center text-sm">
				Already have an account?
				<TextLink
					:href="route('login')"
					class="underline underline-offset-4"
					:tabindex="9"
					>Log in</TextLink
				>
			</div>
		</form>
	</AuthBase>
</template>
