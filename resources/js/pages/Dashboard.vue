<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import ImageUpload from '@/components/ImageUpload.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';

interface CharacterImage {
	id: number;
	filename: string;
	storage_path: string;
	width: number;
	height: number;
	alt_text?: string;
	description?: string;
	is_public: boolean;
	created_at: string;
	url: string;
	thumbnail_url: string;
}

interface Props {
	characterImages: CharacterImage[];
}

const props = defineProps<Props>();
const page = usePage();

const breadcrumbs: BreadcrumbItem[] = [
	{
		title: 'Dashboard',
		href: '/dashboard',
	},
];

const handleUploadSuccess = () => {
	router.reload();
};

// Delete image
const deleteImage = async (imageId: number) => {
	if (!confirm('Are you sure you want to delete this image?')) {
		return;
	}

	try {
		const response = await fetch(`/character-images/${imageId}`, {
			method: 'DELETE',
			headers: {
				'X-CSRF-TOKEN':
					document
						.querySelector('meta[name="csrf-token"]')
						?.getAttribute('content') || '',
				Accept: 'application/json',
			},
		});

		if (!response.ok) {
			throw new Error(`HTTP error! status: ${response.status}`);
		}

		const result = await response.json();

		if (result.success) {
			router.reload();
		} else {
			alert(result.message || 'Delete failed. Please try again.');
		}
	} catch (error) {
		console.error('Delete error:', error);
		alert('Delete failed. Please try again.');
	}
};
</script>

<template>
	<Head title="Dashboard" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
			<!-- Header -->
			<div class="flex items-center justify-between">
				<div>
					<h1
						class="text-2xl font-bold text-gray-900 dark:text-white">
						Character Images
					</h1>
					<p class="text-gray-600 dark:text-gray-400">
						Upload and manage your character images
					</p>
				</div>
			</div>

			<!-- Flash Messages -->
			<div
				v-if="(page.props.flash as any)?.success"
				class="rounded-lg bg-green-50 p-4 dark:bg-green-900/20">
				<div class="flex">
					<div class="flex-shrink-0">
						<svg
							class="h-5 w-5 text-green-400"
							viewBox="0 0 20 20"
							fill="currentColor">
							<path
								fill-rule="evenodd"
								d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
								clip-rule="evenodd" />
						</svg>
					</div>
					<div class="ml-3">
						<p
							class="text-sm font-medium text-green-800 dark:text-green-200">
							{{ (page.props.flash as any)?.success }}
						</p>
					</div>
				</div>
			</div>

			<div
				v-if="(page.props.flash as any)?.error"
				class="rounded-lg bg-red-50 p-4 dark:bg-red-900/20">
				<div class="flex">
					<div class="flex-shrink-0">
						<svg
							class="h-5 w-5 text-red-400"
							viewBox="0 0 20 20"
							fill="currentColor">
							<path
								fill-rule="evenodd"
								d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
								clip-rule="evenodd" />
						</svg>
					</div>
					<div class="ml-3">
						<p
							class="text-sm font-medium text-red-800 dark:text-red-200">
							{{ (page.props.flash as any)?.error }}
						</p>
					</div>
				</div>
			</div>

			<!-- Upload Section -->
			<Card>
				<CardHeader>
					<CardTitle>Upload New Image</CardTitle>
				</CardHeader>
				<CardContent>
					<ImageUpload
						upload-url="/character-images"
						accept="image/png"
						:max-size="2 * 1024 * 1024"
						:form-fields="[
							{
								name: 'alt_text',
								label: 'Alt Text (Optional)',
								placeholder:
									'Describe the image for accessibility',
								maxlength: 255,
							},
							{
								name: 'description',
								label: 'Description (Optional)',
								placeholder:
									'Add a description or notes about this character',
								maxlength: 1000,
							},
						]"
						drag-drop-text="Drop your PNG image here"
						file-type-hint="PNG files only, max 2MB"
						@success="handleUploadSuccess" />
				</CardContent>
			</Card>

			<!-- Images Grid -->
			<div
				class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
				<template v-if="props.characterImages.length > 0">
					<Card
						v-for="image in props.characterImages"
						:key="image.id"
						class="overflow-hidden">
						<CardContent class="p-0">
							<div class="aspect-square overflow-hidden">
								<img
									:src="image.thumbnail_url"
									:alt="
										image.alt_text ||
										image.filename
									"
									class="h-full w-full object-contain transition-transform hover:scale-105" />
							</div>
							<div class="p-4">
								<h3
									class="truncate font-medium text-gray-900 dark:text-white">
									{{ image.filename }}
								</h3>
								<p
									class="text-sm text-gray-500 dark:text-gray-400">
									{{ image.width }} ×
									{{ image.height }}
								</p>
								<p
									v-if="image.alt_text"
									class="mt-1 text-sm text-gray-600 dark:text-gray-300">
									{{ image.alt_text }}
								</p>
								<div class="mt-3 flex gap-2">
									<Button
										@click="deleteImage(image.id)"
										variant="destructive"
										size="sm">
										Delete
									</Button>
								</div>
							</div>
						</CardContent>
					</Card>
				</template>
				<template v-else>
					<div class="col-span-full py-12 text-center">
						<div class="mx-auto mb-4 h-12 w-12 text-gray-400">
							<svg
								fill="none"
								stroke="currentColor"
								viewBox="0 0 24 24">
								<path
									stroke-linecap="round"
									stroke-linejoin="round"
									stroke-width="2"
									d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
							</svg>
						</div>
						<h3
							class="mb-2 text-lg font-medium text-gray-900 dark:text-white">
							No images yet
						</h3>
						<p class="text-gray-500 dark:text-gray-400">
							Upload your first character image to get
							started!
						</p>
					</div>
				</template>
			</div>
		</div>
	</AppLayout>
</template>
