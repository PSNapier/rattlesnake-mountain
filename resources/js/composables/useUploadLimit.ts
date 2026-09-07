import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * The upload size limit for the signed-in user, resolved server-side from
 * `config/uploads.php` and shared on every Inertia response.
 */
export function useUploadLimit() {
	const page = usePage<SharedData>();

	const maxBytes = computed(() => page.props.uploads.maxBytes);
	const maxMegabytes = computed(() => page.props.uploads.maxMegabytes);
	const sizeHint = computed(() => `max ${maxMegabytes.value}MB`);

	return { maxBytes, maxMegabytes, sizeHint };
}
