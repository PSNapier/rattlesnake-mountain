<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { useInitials } from '@/composables/useInitials';
import { type BreadcrumbItem, type SharedData, type User } from '@/types';

import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

const breadcrumbItems: BreadcrumbItem[] = [
	{
		title: 'Appearance settings',
		href: '/settings/appearance',
	},
];

const page = usePage<SharedData>();
const user = computed(() => page.props.auth.user as User);
const { getInitials } = useInitials();

const fileInput = ref<HTMLInputElement>();
const isUploading = ref(false);
const isDeleting = ref(false);

const handleFileSelect = (event: Event): void => {
	const target = event.target as HTMLInputElement;
	if (target.files && target.files[0]) {
		const file = target.files[0];

		// Validate file type
		if (!file.type.startsWith('image/')) {
			alert('Please select an image file.');
			return;
		}

		// Validate file size (2MB)
		if (file.size > 2 * 1024 * 1024) {
			alert('File size must be less than 2MB.');
			return;
		}

		uploadAvatar(file);
	}
};

const uploadAvatar = async (file: File): Promise<void> => {
	isUploading.value = true;

	try {
		const formData = new FormData();
		formData.append('avatar', file);

		// Get CSRF token from meta tag
		const metaTag = document.querySelector('meta[name="csrf-token"]');
		const csrfToken = metaTag?.getAttribute('content') || '';

		const response = await fetch(route('appearance.avatar.upload'), {
			method: 'POST',
			body: formData,
			headers: {
				'X-Requested-With': 'XMLHttpRequest',
				Accept: 'application/json',
				'X-CSRF-TOKEN': csrfToken,
			},
			credentials: 'same-origin',
		});

		const result = await response.json();

		if (!response.ok) {
			throw new Error(result.message || 'Failed to upload avatar');
		}

		// Update user avatar immediately
		if (result.avatar) {
			page.props.auth.user.avatar = result.avatar;
		}

		// Reload page to get updated user data
		router.reload({ only: ['auth'] });

		// Reset file input
		if (fileInput.value) {
			fileInput.value.value = '';
		}
	} catch (error) {
		alert(error instanceof Error ? error.message : 'Failed to upload avatar. Please try again.');
	} finally {
		isUploading.value = false;
	}
};

const deleteAvatar = async (): Promise<void> => {
	if (!confirm('Are you sure you want to delete your avatar?')) {
		return;
	}

	isDeleting.value = true;

	try {
		// Get CSRF token from meta tag
		const metaTag = document.querySelector('meta[name="csrf-token"]');
		const csrfToken = metaTag?.getAttribute('content') || '';

		const response = await fetch(route('appearance.avatar.delete'), {
			method: 'DELETE',
			headers: {
				'X-Requested-With': 'XMLHttpRequest',
				Accept: 'application/json',
				'X-CSRF-TOKEN': csrfToken,
			},
			credentials: 'same-origin',
		});

		const result = await response.json();

		if (!response.ok) {
			throw new Error(result.message || 'Failed to delete avatar');
		}

		// Update user avatar immediately
		page.props.auth.user.avatar = null;

		// Reload page to get updated user data
		router.reload({ only: ['auth'] });
	} catch (error) {
		alert(error instanceof Error ? error.message : 'Failed to delete avatar. Please try again.');
	} finally {
		isDeleting.value = false;
	}
};
</script>

<template>
	<AppLayout :breadcrumbs="breadcrumbItems">
		<Head title="Appearance settings" />

		<SettingsLayout>
			<div class="space-y-6">
				<HeadingSmall
					title="Appearance settings"
					description="Update your account's appearance settings" />

				<div class="space-y-6">
					<div class="space-y-4">
						<div>
							<h3 class="text-sm font-medium">Avatar</h3>
							<p class="text-muted-foreground text-sm">
								Upload a profile picture or use your initials
							</p>
						</div>

						<div class="flex items-center gap-6">
							<Avatar class="size-24 overflow-hidden rounded-xl">
								<AvatarImage
									v-if="user.avatar"
									:src="user.avatar"
									:alt="user.name" />
								<AvatarFallback
									class="bg-primary text-primary-foreground rounded-full text-2xl font-semibold">
									{{ getInitials(user.name) }}
								</AvatarFallback>
							</Avatar>

							<div class="flex flex-col gap-3">
								<div class="flex items-center gap-3">
									<input
										ref="fileInput"
										type="file"
										accept="image/jpeg,image/jpg,image/png,image/webp"
										class="hidden"
										@change="handleFileSelect" />
									<Button
										type="button"
										variant="outline"
										:disabled="isUploading"
										@click="fileInput?.click()">
										{{ isUploading ? 'Uploading...' : 'Upload Avatar' }}
									</Button>
									<Button
										v-if="user.avatar"
										type="button"
										variant="destructive"
										:disabled="isDeleting"
										@click="deleteAvatar">
										{{ isDeleting ? 'Deleting...' : 'Delete Avatar' }}
									</Button>
								</div>
								<p class="text-muted-foreground text-xs">
									JPEG, PNG, or WebP. Max 2MB.
								</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</SettingsLayout>
	</AppLayout>
</template>
