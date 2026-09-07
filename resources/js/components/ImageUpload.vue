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
import { useImageUpload } from '@/composables/useImageUpload';
import { useUploadLimit } from '@/composables/useUploadLimit';
import { LoaderCircle } from 'lucide-vue-next';
import { computed, onBeforeUnmount, reactive, ref } from 'vue';

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
	maxSize: undefined,
	formFields: () => [],
	dragDropText: 'Drop your image here',
	browseButtonText: 'Browse Files',
	fileTypeHint: undefined,
	previewTitle: 'Preview & Confirm Upload',
	uploadButtonText: 'Upload Image',
	uploadingButtonText: 'Uploading...',
});

const emit = defineEmits<{
	success: [data: any];
	error: [error: Error | string];
	complete: [];
}>();

const { validateFile: validateImageFile, uploadFile } = useImageUpload();
const { maxBytes, sizeHint } = useUploadLimit();

// Fall back to the signed-in user's shared limit when no override is passed.
const maxSize = computed(() => props.maxSize ?? maxBytes.value);
const sizeHintText = computed(
	() => props.fileTypeHint ?? `PNG files only, ${sizeHint.value}`,
);

const isUploading = ref(false);
const selectedFile = ref<File | null>(null);
const previewUrl = ref<string>('');
const showPreviewDialog = ref(false);
const fileInput = ref<HTMLInputElement>();

const uploadForm = reactive<Record<string, string>>({});

// Initialize form fields
props.formFields.forEach((field) => {
	uploadForm[field.name] = '';
});

const validateFile = (file: File): boolean =>
	validateImageFile(file, { accept: props.accept, maxSize: maxSize.value });

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

	try {
		const result = await uploadFile({
			url: props.uploadUrl,
			file: selectedFile.value,
			fieldName: 'image',
			fields: { ...uploadForm },
		});

		emit('success', result);
	} catch (error) {
		console.error('Upload error:', error);
		const errorMessage =
			error instanceof Error
				? error.message
				: 'Upload failed. Please try again.';
		emit('error', errorMessage);
		alert(errorMessage);
	} finally {
		resetForm();
		emit('complete');
	}
};

const resetForm = (): void => {
	isUploading.value = false;
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
			class="rounded-lg border-2 border-dashed border-gray-300 p-8 text-center transition-colors hover:border-gray-400">
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
					<p class="text-lg font-medium text-gray-900">
						{{ dragDropText }}
					</p>
					<p class="text-sm text-gray-500">or</p>
					<Button
						type="button"
						@click="triggerFileInput"
						variant="outline"
						class="mt-2">
						{{ browseButtonText }}
					</Button>
				</div>
				<p class="text-xs text-gray-500">
					{{ sizeHintText }}
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
							<LoaderCircle
								v-if="isUploading"
								class="h-4 w-4 animate-spin" />
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
