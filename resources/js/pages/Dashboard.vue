<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
	Dialog,
	DialogContent,
	DialogHeader,
	DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { onMounted, reactive, ref } from 'vue';

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

// Upload state
const isUploading = ref(false);
const uploadProgress = ref(0);
const selectedFile = ref<File | null>(null);
const previewUrl = ref<string>('');
const showPreviewDialog = ref(false);
const uploadForm = reactive({
	alt_text: '',
	description: '',
});

// File input ref
const fileInput = ref<HTMLInputElement>();

// Handle file selection
const handleFileSelect = (event: Event) => {
	const target = event.target as HTMLInputElement;
	if (target.files && target.files[0]) {
		const file = target.files[0];

		// Validate file type
		if (file.type !== 'image/png') {
			alert('Please select a PNG image file.');
			return;
		}

		// Validate file size (2MB)
		if (file.size > 2 * 1024 * 1024) {
			alert('File size must be less than 2MB.');
			return;
		}

		selectedFile.value = file;
		previewUrl.value = URL.createObjectURL(file);
		showPreviewDialog.value = true;
	}
};

// Handle drag and drop
const handleDragOver = (event: DragEvent) => {
	event.preventDefault();
	(event.currentTarget as HTMLElement)?.classList.add(
		'border-blue-500',
		'bg-blue-50',
	);
};

const handleDragLeave = (event: DragEvent) => {
	(event.currentTarget as HTMLElement)?.classList.remove(
		'border-blue-500',
		'bg-blue-50',
	);
};

const handleDrop = (event: DragEvent) => {
	event.preventDefault();
	(event.currentTarget as HTMLElement)?.classList.remove(
		'border-blue-500',
		'bg-blue-50',
	);

	if (event.dataTransfer?.files && event.dataTransfer.files[0]) {
		const file = event.dataTransfer.files[0];

		if (file.type !== 'image/png') {
			alert('Please select a PNG image file.');
			return;
		}

		if (file.size > 2 * 1024 * 1024) {
			alert('File size must be less than 2MB.');
			return;
		}

		selectedFile.value = file;
		previewUrl.value = URL.createObjectURL(file);
		showPreviewDialog.value = true;
	}
};

// Upload image
const uploadImage = async () => {
	if (!selectedFile.value) return;

	isUploading.value = true;
	uploadProgress.value = 0;

	// Simulate progress
	const progressInterval = setInterval(() => {
		if (uploadProgress.value < 90) {
			uploadProgress.value += 10;
		}
	}, 100);

	try {
		const formData = new FormData();
		formData.append('image', selectedFile.value);
		formData.append('alt_text', uploadForm.alt_text);
		formData.append('description', uploadForm.description);

		const response = await fetch('/character-images', {
			method: 'POST',
			body: formData,
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
			// Refresh the page to show new image
			router.reload();
		} else {
			alert(result.message || 'Upload failed. Please try again.');
		}
	} catch (error) {
		console.error('Upload error:', error);
		alert('Upload failed. Please try again.');
	} finally {
		clearInterval(progressInterval);
		uploadProgress.value = 100;
		setTimeout(() => {
			isUploading.value = false;
			uploadProgress.value = 0;
			showPreviewDialog.value = false;
			selectedFile.value = null;
			previewUrl.value = '';
			uploadForm.alt_text = '';
			uploadForm.description = '';
		}, 500);
	}
};

// Delete image
const deleteImage = async (imageId: number) => {
	if (!confirm('Are you sure you want to delete this image?')) return;

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

// Trigger file input
const triggerFileInput = () => {
	fileInput.value?.click();
};

// Cleanup preview URL on unmount
onMounted(() => {
	return () => {
		if (previewUrl.value) {
			URL.revokeObjectURL(previewUrl.value);
		}
	};
});
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
					<!-- Drag & Drop Zone -->
					<div
						@dragover="handleDragOver"
						@dragleave="handleDragLeave"
						@drop="handleDrop"
						class="rounded-lg border-2 border-dashed border-gray-300 p-8 text-center transition-colors hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-500">
						<div class="space-y-4">
							<div class="mx-auto h-12 w-12 text-gray-400">
								<svg
									fill="none"
									stroke="currentColor"
									viewBox="0 0 24 24">
									<path
										stroke-linecap="round"
										stroke-linejoin="round"
										stroke-width="2"
										d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
								</svg>
							</div>
							<div>
								<p
									class="text-lg font-medium text-gray-900 dark:text-white">
									Drop your PNG image here
								</p>
								<p
									class="text-sm text-gray-500 dark:text-gray-400">
									or
								</p>
								<Button
									@click="triggerFileInput"
									variant="outline"
									class="mt-2">
									Browse Files
								</Button>
							</div>
							<p
								class="text-xs text-gray-500 dark:text-gray-400">
								PNG files only, max 2MB
							</p>
						</div>
					</div>

					<!-- Hidden file input -->
					<input
						ref="fileInput"
						type="file"
						accept="image/png"
						class="hidden"
						@change="handleFileSelect" />
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

		<!-- Preview Dialog -->
		<Dialog v-model:open="showPreviewDialog">
			<DialogContent class="max-w-2xl">
				<DialogHeader>
					<DialogTitle>Preview & Confirm Upload</DialogTitle>
				</DialogHeader>

				<div class="space-y-4">
					<!-- Image Preview -->
					<div class="flex justify-center">
						<img
							v-if="previewUrl"
							:src="previewUrl"
							alt="Preview"
							class="max-h-96 max-w-full rounded-lg object-contain" />
					</div>

					<!-- Form Fields -->
					<div class="space-y-4">
						<div>
							<Label for="alt_text"
								>Alt Text (Optional)</Label
							>
							<Input
								id="alt_text"
								v-model="uploadForm.alt_text"
								placeholder="Describe the image for accessibility"
								maxlength="255" />
						</div>

						<div>
							<Label for="description"
								>Description (Optional)</Label
							>
							<Input
								id="description"
								v-model="uploadForm.description"
								placeholder="Add a description or notes about this character"
								maxlength="1000" />
						</div>
					</div>

					<!-- Upload Progress -->
					<div
						v-if="isUploading"
						class="space-y-2">
						<div class="flex justify-between text-sm">
							<span>Uploading...</span>
							<span>{{ uploadProgress }}%</span>
						</div>
						<Progress :value="uploadProgress" />
					</div>

					<!-- Action Buttons -->
					<div class="flex justify-end gap-3">
						<Button
							variant="outline"
							@click="showPreviewDialog = false"
							:disabled="isUploading">
							Cancel
						</Button>
						<Button
							@click="uploadImage"
							:disabled="isUploading">
							<template v-if="isUploading">
								Uploading...
							</template>
							<template v-else> Upload Image </template>
						</Button>
					</div>
				</div>
			</DialogContent>
		</Dialog>
	</AppLayout>
</template>
