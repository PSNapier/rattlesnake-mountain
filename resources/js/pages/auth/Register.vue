<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

const form = useForm({
	referred_by_username: '',
	name: '',
	email: '',
	password: '',
	password_confirmation: '',
	rules_agreed: false,
});

const submit = () => {
	form.transform((data) => {
		// Convert checkbox value to 1 (accepted) or 0 (not accepted)
		// The accepted rule accepts: true, 1, "1", "yes", "on", "true"
		const rulesAgreed = data.rules_agreed ? 1 : 0;
		
		// Trim referred_by_username and convert empty string to null
		const referredByUsername = data.referred_by_username?.trim() || null;
		
		return {
			...data,
			rules_agreed: rulesAgreed,
			referred_by_username: referredByUsername,
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
					<Label for="password_confirmation"
						>Confirm password</Label
					>
					<Input
						id="password_confirmation"
						type="password"
						required
						:tabindex="4"
						autocomplete="new-password"
						v-model="form.password_confirmation"
						placeholder="Confirm password" />
					<InputError
						:message="form.errors.password_confirmation" />
				</div>

				<div class="grid gap-2">
					<Label for="referred_by_username">Were you referred by someone?</Label>
					<Input
						id="referred_by_username"
						type="text"
						:tabindex="5"
						autocomplete="username"
						v-model="form.referred_by_username"
						placeholder="Referrer Username (optional)" />
					<InputError :message="form.errors.referred_by_username" />
				</div>

				<div class="flex items-start gap-2">
					<input
						id="rules_agreed"
						type="checkbox"
						v-model="form.rules_agreed"
						class="mt-1 h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
						required
						:tabindex="6" />
					<Label
						for="rules_agreed"
						class="block text-muted-foreground text-md font-normal cursor-pointer">
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
					tabindex="7"
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
					:tabindex="8"
					>Log in</TextLink
				>
			</div>
		</form>
	</AuthBase>
</template>
