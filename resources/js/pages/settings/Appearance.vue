<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

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

// Local reactive avatar state for immediate UI updates
const localAvatar = ref<string | null | undefined>(page.props.auth.user.avatar);

// Computed avatar that uses local state for immediate updates
const avatar = computed(() => localAvatar.value ?? page.props.auth.user.avatar);

// Sync localAvatar when page props change (after reload)
watch(
	() => page.props.auth.user.avatar,
	(newAvatar) => {
		localAvatar.value = newAvatar;
	},
);

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

// Helper function to fetch a fresh CSRF token
const fetchFreshCsrfToken = async (): Promise<string> => {
	try {
		// Make a lightweight GET request to refresh the session
		const response = await fetch(window.location.href, {
			method: 'GET',
			credentials: 'same-origin',
			headers: {
				Accept: 'text/html',
				'X-Requested-With': 'XMLHttpRequest',
			},
		});

		if (!response.ok) {
			throw new Error('Failed to refresh session');
		}

		// Parse the response HTML to extract the new CSRF token
		const html = await response.text();
		const parser = new DOMParser();
		const doc = parser.parseFromString(html, 'text/html');
		const metaTag = doc.querySelector('meta[name="csrf-token"]');
		const newToken = metaTag?.getAttribute('content') || '';

		// Update the meta tag in the current document
		const currentMetaTag = document.querySelector('meta[name="csrf-token"]');
		if (currentMetaTag && newToken) {
			currentMetaTag.setAttribute('content', newToken);
		}

		return newToken;
	} catch (error) {
		console.warn('Failed to refresh CSRF token:', error);
		// Fallback to current meta tag if refresh fails
		const metaTag = document.querySelector('meta[name="csrf-token"]');
		return metaTag?.getAttribute('content') || '';
	}
};

// Helper function to perform the actual upload
const performAvatarUpload = async (csrfToken: string, file: File, retry = false): Promise<Response> => {
	const formData = new FormData();
	formData.append('avatar', file);

	// Build headers
	const headers: HeadersInit = {
		Accept: 'application/json',
		'X-Requested-With': 'XMLHttpRequest',
	};

	// Only add CSRF token if we have it
	if (csrfToken) {
		headers['X-CSRF-TOKEN'] = csrfToken;
	}

	const response = await fetch(route('appearance.avatar.upload'), {
		method: 'POST',
		body: formData,
		credentials: 'same-origin',
		headers,
	});

	// If we get a 419 and haven't retried yet, fetch fresh token and retry
	if (response.status === 419 && !retry) {
		const freshToken = await fetchFreshCsrfToken();
		return performAvatarUpload(freshToken, file, true);
	}

	return response;
};

const uploadAvatar = async (file: File): Promise<void> => {
	isUploading.value = true;

	try {
		// Get CSRF token from meta tag right before request
		const metaTag = document.querySelector('meta[name="csrf-token"]');
		const csrfToken = metaTag?.getAttribute('content') || '';

		const response = await performAvatarUpload(csrfToken, file);

		if (!response.ok) {
			// Handle specific error codes
			if (response.status === 419) {
				throw new Error('Session expired. Please refresh the page and try again.');
			}

			// Try to parse JSON error response
			let errorMessage = `Upload failed (HTTP ${response.status})`;
			try {
				const errorData = await response.json();
				errorMessage = errorData.message || errorMessage;
			} catch {
				// Response is not JSON, use status-based message
				if (response.status === 419) {
					errorMessage = 'Session expired. Please refresh the page and try again.';
				}
			}
			throw new Error(errorMessage);
		}

		const result = await response.json();

		if (!result.success) {
			throw new Error(result.message || 'Upload failed. Please try again.');
		}

		// Update local avatar immediately for UI
		if (result.avatar) {
			localAvatar.value = result.avatar;
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

// Helper function to perform the delete
const performAvatarDelete = async (csrfToken: string, retry = false): Promise<Response> => {
	const headers: HeadersInit = {
		Accept: 'application/json',
		'X-Requested-With': 'XMLHttpRequest',
	};

	// Only add CSRF token if we have it
	if (csrfToken) {
		headers['X-CSRF-TOKEN'] = csrfToken;
	}

	const response = await fetch(route('appearance.avatar.delete'), {
		method: 'DELETE',
		credentials: 'same-origin',
		headers,
	});

	// If we get a 419 and haven't retried yet, fetch fresh token and retry
	if (response.status === 419 && !retry) {
		const freshToken = await fetchFreshCsrfToken();
		return performAvatarDelete(freshToken, true);
	}

	return response;
};

const deleteAvatar = async (): Promise<void> => {
	if (!confirm('Are you sure you want to delete your avatar?')) {
		return;
	}

	isDeleting.value = true;

	try {
		// Get CSRF token from meta tag right before request
		const metaTag = document.querySelector('meta[name="csrf-token"]');
		const csrfToken = metaTag?.getAttribute('content') || '';

		const response = await performAvatarDelete(csrfToken);

		if (!response.ok) {
			// Handle specific error codes
			if (response.status === 419) {
				throw new Error('Session expired. Please refresh the page and try again.');
			}

			// Try to parse JSON error response
			let errorMessage = `Delete failed (HTTP ${response.status})`;
			try {
				const errorData = await response.json();
				errorMessage = errorData.message || errorMessage;
			} catch {
				// Response is not JSON, use status-based message
				if (response.status === 419) {
					errorMessage = 'Session expired. Please refresh the page and try again.';
				}
			}
			throw new Error(errorMessage);
		}

		const result = await response.json();

		if (!result.success) {
			throw new Error(result.message || 'Delete failed. Please try again.');
		}

		// Force full page refresh
		window.location.reload();
	} catch (error) {
		alert(error instanceof Error ? error.message : 'Failed to delete avatar. Please try again.');
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
							<Avatar
								:key="avatar || 'no-avatar'"
								class="size-24 overflow-hidden rounded-xl">
								<AvatarImage
									v-if="avatar"
									:src="avatar"
									:alt="user.name" />
								<AvatarFallback
									class="bg-primary text-primary-foreground rounded-xl text-2xl font-semibold">
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
										v-if="avatar"
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
