<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
	Dialog,
	DialogContent,
	DialogHeader,
	DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import Progress from '@/components/ui/progress/Progress.vue';
import { onBeforeUnmount, reactive, ref } from 'vue';

interface FormField {
	name: string;
	label: string;
	placeholder?: string;
	maxlength?: number;
	type?: string;
	required?: boolean;
}

interface Props {
	uploadUrl: string;
	accept?: string;
	maxSize?: number;
	formFields?: FormField[];
	dragDropText?: string;
	browseButtonText?: string;
	fileTypeHint?: string;
	previewTitle?: string;
	uploadButtonText?: string;
	uploadingButtonText?: string;
}

const props = withDefaults(defineProps<Props>(), {
	accept: 'image/png',
	maxSize: 2 * 1024 * 1024, // 2MB
	formFields: () => [],
	dragDropText: 'Drop your image here',
	browseButtonText: 'Browse Files',
	fileTypeHint: 'PNG files only, max 2MB',
	previewTitle: 'Preview & Confirm Upload',
	uploadButtonText: 'Upload Image',
	uploadingButtonText: 'Uploading...',
});

const emit = defineEmits<{
	success: [data: any];
	error: [error: Error | string];
	complete: [];
}>();

const isUploading = ref(false);
const uploadProgress = ref(0);
const selectedFile = ref<File | null>(null);
const previewUrl = ref<string>('');
const showPreviewDialog = ref(false);
const fileInput = ref<HTMLInputElement>();

const uploadForm = reactive<Record<string, string>>({});

// Initialize form fields
props.formFields.forEach((field) => {
	uploadForm[field.name] = '';
});

const validateFile = (file: File): boolean => {
	if (!file.type.includes('image/')) {
		alert('Please select an image file.');
		return false;
	}

	if (props.accept) {
		const acceptedTypes = props.accept
			.split(',')
			.map((t) => t.trim().toLowerCase());
		const fileType = file.type.toLowerCase();
		const isAccepted = acceptedTypes.some(
			(acceptType) =>
				fileType === acceptType ||
				acceptType === 'image/*' ||
				fileType.startsWith(acceptType.replace('/*', '/')),
		);

		if (!isAccepted) {
			const acceptTypes = props.accept
				.split(',')
				.map((t) => t.trim())
				.join(' or ');
			alert(`Please select a ${acceptTypes} file.`);
			return false;
		}
	}

	if (file.size > props.maxSize) {
		const maxSizeMB = (props.maxSize / (1024 * 1024)).toFixed(0);
		alert(`File size must be less than ${maxSizeMB}MB.`);
		return false;
	}

	return true;
};

const handleFileSelect = (event: Event): void => {
	const target = event.target as HTMLInputElement;
	if (target.files && target.files[0]) {
		const file = target.files[0];
		if (validateFile(file)) {
			selectedFile.value = file;
			previewUrl.value = URL.createObjectURL(file);
			showPreviewDialog.value = true;
		}
	}
};

const handleDragOver = (event: DragEvent): void => {
	event.preventDefault();
	(event.currentTarget as HTMLElement)?.classList.add(
		'border-blue-500',
		'bg-blue-50',
	);
};

const handleDragLeave = (event: DragEvent): void => {
	(event.currentTarget as HTMLElement)?.classList.remove(
		'border-blue-500',
		'bg-blue-50',
	);
};

const handleDrop = (event: DragEvent): void => {
	event.preventDefault();
	(event.currentTarget as HTMLElement)?.classList.remove(
		'border-blue-500',
		'bg-blue-50',
	);

	if (event.dataTransfer?.files && event.dataTransfer.files[0]) {
		const file = event.dataTransfer.files[0];
		if (validateFile(file)) {
			selectedFile.value = file;
			previewUrl.value = URL.createObjectURL(file);
			showPreviewDialog.value = true;
		}
	}
};

const uploadImage = async (): Promise<void> => {
	if (!selectedFile.value) {
		return;
	}

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

		// Add form fields
		Object.keys(uploadForm).forEach((key) => {
			formData.append(key, uploadForm[key]);
		});

		const response = await fetch(props.uploadUrl, {
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
			emit('success', result);
		} else {
			throw new Error(
				result.message || 'Upload failed. Please try again.',
			);
		}
	} catch (error) {
		console.error('Upload error:', error);
		const errorMessage =
			error instanceof Error
				? error
				: 'Upload failed. Please try again.';
		emit('error', errorMessage);
		alert(errorMessage);
	} finally {
		clearInterval(progressInterval);
		uploadProgress.value = 100;
		setTimeout(() => {
			resetForm();
			emit('complete');
		}, 500);
	}
};

const resetForm = (): void => {
	isUploading.value = false;
	uploadProgress.value = 0;
	showPreviewDialog.value = false;
	if (previewUrl.value) {
		URL.revokeObjectURL(previewUrl.value);
	}
	selectedFile.value = null;
	previewUrl.value = '';
	Object.keys(uploadForm).forEach((key) => {
		uploadForm[key] = '';
	});
};

const triggerFileInput = (): void => {
	fileInput.value?.click();
};

const cancelUpload = (): void => {
	if (!isUploading.value) {
		resetForm();
	}
};

// Cleanup preview URL on unmount
onBeforeUnmount(() => {
	if (previewUrl.value) {
		URL.revokeObjectURL(previewUrl.value);
	}
});
</script>

<template>
	<div>
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
						{{ dragDropText }}
					</p>
					<p class="text-sm text-gray-500 dark:text-gray-400">
						or
					</p>
					<Button
						@click="triggerFileInput"
						variant="outline"
						class="mt-2">
						{{ browseButtonText }}
					</Button>
				</div>
				<p class="text-xs text-gray-500 dark:text-gray-400">
					{{ fileTypeHint }}
				</p>
			</div>
		</div>

		<!-- Hidden file input -->
		<input
			ref="fileInput"
			type="file"
			:accept="accept"
			class="hidden"
			@change="handleFileSelect" />

		<!-- Preview Dialog -->
		<Dialog v-model:open="showPreviewDialog">
			<DialogContent class="max-w-2xl">
				<DialogHeader>
					<DialogTitle>{{ previewTitle }}</DialogTitle>
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
					<div
						v-if="formFields.length > 0"
						class="space-y-4">
						<div
							v-for="field in formFields"
							:key="field.name">
							<Label :for="field.name">
								{{ field.label }}
								<span
									v-if="field.required"
									class="text-red-500">
									*
								</span>
							</Label>
							<Input
								:id="field.name"
								v-model="uploadForm[field.name]"
								:placeholder="field.placeholder"
								:maxlength="field.maxlength"
								:type="field.type || 'text'"
								:required="field.required" />
						</div>
					</div>

					<!-- Upload Progress -->
					<div
						v-if="isUploading"
						class="space-y-2">
						<div class="flex justify-between text-sm">
							<span>{{ uploadingButtonText }}</span>
							<span>{{ uploadProgress }}%</span>
						</div>
						<Progress :value="uploadProgress" />
					</div>

					<!-- Action Buttons -->
					<div class="flex justify-end gap-3">
						<Button
							variant="outline"
							@click="cancelUpload"
							:disabled="isUploading">
							Cancel
						</Button>
						<Button
							@click="uploadImage"
							:disabled="isUploading">
							<template v-if="isUploading">
								{{ uploadingButtonText }}
							</template>
							<template v-else>
								{{ uploadButtonText }}
							</template>
						</Button>
					</div>
				</div>
			</DialogContent>
		</Dialog>
	</div>
</template>
