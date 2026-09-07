import { useCsrfFetch } from '@/composables/useCsrfFetch';

export interface ValidateFileOptions {
	/** Comma-separated MIME list mirroring the input's `accept` attribute. */
	accept?: string;
	/** Maximum file size in bytes. */
	maxSize: number;
}

export interface UploadFileOptions {
	url: string;
	file: File;
	/** Form field name the endpoint expects, e.g. "image" or "avatar". */
	fieldName: string;
	/** Extra text fields appended alongside the file. */
	fields?: Record<string, string>;
}

export function useImageUpload() {
	const { csrfFetch, resolveErrorMessage } = useCsrfFetch();

	const validateFile = (
		file: File,
		{ accept, maxSize }: ValidateFileOptions,
	): boolean => {
		if (!file.type.includes('image/')) {
			alert('Please select an image file.');
			return false;
		}

		if (accept) {
			const acceptedTypes = accept
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
				const acceptTypes = accept
					.split(',')
					.map((t) => t.trim())
					.join(' or ');
				alert(`Please select a ${acceptTypes} file.`);
				return false;
			}
		}

		if (file.size > maxSize) {
			const maxSizeMB = (maxSize / (1024 * 1024)).toFixed(0);
			alert(`File size must be less than ${maxSizeMB}MB.`);
			return false;
		}

		return true;
	};

	const uploadFile = async ({
		url,
		file,
		fieldName,
		fields = {},
	}: UploadFileOptions): Promise<any> => {
		const formData = new FormData();
		formData.append(fieldName, file);

		// Add form fields
		Object.keys(fields).forEach((key) => {
			formData.append(key, fields[key]);
		});

		const response = await csrfFetch(url, {
			method: 'POST',
			body: formData,
		});

		if (!response.ok) {
			// Get file size for better error messages
			const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);

			throw new Error(
				await resolveErrorMessage(response, {
					action: 'Upload',
					fileSizeMB,
				}),
			);
		}

		const result = await response.json();

		if (!result.success) {
			throw new Error(
				result.message || 'Upload failed. Please try again.',
			);
		}

		return result;
	};

	return { validateFile, uploadFile };
}
