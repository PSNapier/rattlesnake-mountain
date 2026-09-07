<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { useCsrfFetch } from '@/composables/useCsrfFetch';
import { useImageUpload } from '@/composables/useImageUpload';
import { useInitials } from '@/composables/useInitials';
import { useUploadLimit } from '@/composables/useUploadLimit';
import { type BreadcrumbItem, type SharedData, type User } from '@/types';
import { LoaderCircle } from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

const breadcrumbItems: BreadcrumbItem[] = [
	{
		title: 'Avatar settings',
		href: '/settings/appearance',
	},
];

const page = usePage<SharedData>();
const user = computed(() => page.props.auth.user as User);
const { getInitials } = useInitials();
const { csrfFetch, resolveErrorMessage } = useCsrfFetch();
const { validateFile, uploadFile } = useImageUpload();
const { maxBytes, maxMegabytes } = useUploadLimit();

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

		if (validateFile(file, { maxSize: maxBytes.value })) {
			uploadAvatar(file);
		}
	}
};

const uploadAvatar = async (file: File): Promise<void> => {
	isUploading.value = true;

	try {
		const result = await uploadFile({
			url: route('appearance.avatar.upload'),
			file,
			fieldName: 'avatar',
		});

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
		alert(
			error instanceof Error
				? error.message
				: 'Failed to upload avatar. Please try again.',
		);
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
		const response = await csrfFetch(route('appearance.avatar.delete'), {
			method: 'DELETE',
		});

		if (!response.ok) {
			throw new Error(
				await resolveErrorMessage(response, { action: 'Delete' }),
			);
		}

		const result = await response.json();

		if (!result.success) {
			throw new Error(
				result.message || 'Delete failed. Please try again.',
			);
		}

		// Force full page refresh
		window.location.reload();
	} catch (error) {
		alert(
			error instanceof Error
				? error.message
				: 'Failed to delete avatar. Please try again.',
		);
		isDeleting.value = false;
	}
};
</script>

<template>
	<AppLayout :breadcrumbs="breadcrumbItems">
		<Head title="Avatar settings" />

		<SettingsLayout>
			<div class="space-y-6">
				<HeadingSmall
					title="Avatar settings"
					description="Choose the picture shown beside your name" />

				<div class="space-y-6">
					<div class="space-y-4">
						<div>
							<h3 class="text-sm font-medium">Avatar</h3>
							<p class="text-muted-foreground text-sm">
								Upload a profile picture or use your
								initials
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
										<LoaderCircle
											v-if="isUploading"
											class="h-4 w-4 animate-spin" />
										{{
											isUploading
												? 'Uploading...'
												: 'Upload Avatar'
										}}
									</Button>
									<Button
										v-if="avatar"
										type="button"
										variant="destructive"
										:disabled="isDeleting"
										@click="deleteAvatar">
										<LoaderCircle
											v-if="isDeleting"
											class="h-4 w-4 animate-spin" />
										{{
											isDeleting
												? 'Deleting...'
												: 'Delete Avatar'
										}}
									</Button>
								</div>
								<p
									class="text-muted-foreground text-xs">
									JPEG, PNG, or WebP. Max
									{{ maxMegabytes }}MB.
								</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</SettingsLayout>
	</AppLayout>
</template>
