export interface CsrfFetchOptions {
	method: string;
	body?: BodyInit;
}

export interface ErrorMessageOptions {
	/** Verb used in the generic fallback message, e.g. "Upload" or "Delete". */
	action?: string;
	/** File size in MB, quoted back to the user in the 413 message. */
	fileSizeMB?: string;
}

const SESSION_EXPIRED_MESSAGE =
	'Session expired. Please refresh the page and try again.';

const readCsrfToken = (): string => {
	const metaTag = document.querySelector('meta[name="csrf-token"]');
	return metaTag?.getAttribute('content') || '';
};

// Helper function to fetch a fresh CSRF token
const fetchFreshCsrfToken = async (): Promise<string> => {
	try {
		// Make a lightweight GET request to refresh the session
		// This will return the full page but ensures session is refreshed
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
		const currentMetaTag = document.querySelector(
			'meta[name="csrf-token"]',
		);
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

// Helper function to perform the actual request
const performCsrfFetch = async (
	url: string,
	options: CsrfFetchOptions,
	csrfToken: string,
	retry = false,
): Promise<Response> => {
	// Build headers
	const headers: HeadersInit = {
		Accept: 'application/json',
		'X-Requested-With': 'XMLHttpRequest',
	};

	// Only add CSRF token if we have it
	if (csrfToken) {
		headers['X-CSRF-TOKEN'] = csrfToken;
	}

	const response = await fetch(url, {
		method: options.method,
		body: options.body,
		credentials: 'same-origin', // Include cookies for session
		headers,
	});

	// If we get a 419 and haven't retried yet, fetch fresh token and retry
	if (response.status === 419 && !retry) {
		const freshToken = await fetchFreshCsrfToken();
		return performCsrfFetch(url, options, freshToken, true);
	}

	return response;
};

export function useCsrfFetch() {
	const csrfFetch = (
		url: string,
		options: CsrfFetchOptions,
	): Promise<Response> =>
		// Get CSRF token from meta tag right before request
		performCsrfFetch(url, options, readCsrfToken());

	const resolveErrorMessage = async (
		response: Response,
		options: ErrorMessageOptions = {},
	): Promise<string> => {
		const { action = 'Upload', fileSizeMB = 'unknown' } = options;

		// Handle specific error codes
		if (response.status === 413) {
			return `File (${fileSizeMB}MB) exceeds server upload limit. The server configuration limits uploads to less than your file size. Please contact support or try a smaller image.`;
		}

		if (response.status === 419) {
			return SESSION_EXPIRED_MESSAGE;
		}

		// Try to parse JSON error response
		let errorMessage = `${action} failed (HTTP ${response.status})`;
		try {
			const errorData = await response.json();
			errorMessage = errorData.message || errorMessage;
		} catch {
			// Response is not JSON, keep the status-based message
		}

		return errorMessage;
	};

	return { csrfFetch, resolveErrorMessage, fetchFreshCsrfToken };
}
