import { router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { createBox, type CmsBox } from './boxes';

export interface CmsRevision {
	id: number;
	title: string;
	created_at: string;
	author: string | null;
}

export interface InlinePage {
	id: number;
	title: string;
	hero: { title: string; description: string | null };
	content: CmsBox[];
	can_edit?: boolean;
	revisions?: CmsRevision[];
}

export interface InlineDraft {
	title: string;
	hero_title: string;
	hero_description: string;
	content: CmsBox[];
}

function snapshot(page: InlinePage): InlineDraft {
	return {
		title: page.title ?? '',
		hero_title: page.hero?.title ?? '',
		hero_description: page.hero?.description ?? '',
		content: JSON.parse(JSON.stringify(page.content ?? [])) as CmsBox[],
	};
}

function serialise(draft: InlineDraft): string {
	return JSON.stringify(draft);
}

/**
 * Inline page editing state for a CMS page.
 *
 * Always call this, even when the viewer cannot edit: the component instance is
 * reused across Inertia visits, so a conditional call would leave the lifecycle
 * hooks registered for one page and not the next. `editing` simply never turns
 * true for a viewer without permission.
 */
export function useInlineEditor(page: () => InlinePage) {
	const editing = ref(false);
	const saving = ref(false);
	const draft = ref<InlineDraft>(snapshot(page()));
	const baseline = ref(serialise(draft.value));

	function reset() {
		draft.value = snapshot(page());
		baseline.value = serialise(draft.value);
	}

	// A fresh server payload (after save, restore or plain navigation) becomes
	// the new baseline, so the discard target is always the last saved state.
	watch(page, reset);

	const isDirty = computed(() => serialise(draft.value) !== baseline.value);
	const revisions = computed<CmsRevision[]>(() => page().revisions ?? []);

	function start() {
		reset();
		editing.value = true;
	}

	function addBox() {
		draft.value.content.push(createBox());
	}

	function removeBox(id: string) {
		draft.value.content = draft.value.content.filter(
			(box) => box.id !== id,
		);
	}

	function moveBox(from: number, to: number) {
		const list = draft.value.content;
		if (from === to) return;
		if (from < 0 || from >= list.length) return;
		if (to < 0 || to >= list.length) return;
		const [moved] = list.splice(from, 1);
		list.splice(to, 0, moved);
	}

	function save() {
		if (saving.value) return;
		saving.value = true;
		router.put(
			route('admin.cms.pages.inline', page().id),
			{
				title: draft.value.title,
				hero_title: draft.value.hero_title,
				hero_description: draft.value.hero_description,
				content: draft.value.content,
			},
			{
				preserveScroll: true,
				onSuccess: () => {
					editing.value = false;
					reset();
				},
				onFinish: () => {
					saving.value = false;
				},
			},
		);
	}

	function discard() {
		// Local only: the last server payload is already in hand.
		reset();
		editing.value = false;
	}

	function restore(revisionId: number) {
		if (saving.value) return;
		saving.value = true;
		router.post(
			route('admin.cms.pages.revisions.restore', [
				page().id,
				revisionId,
			]),
			{},
			{
				preserveScroll: true,
				onSuccess: () => {
					editing.value = false;
					reset();
				},
				onFinish: () => {
					saving.value = false;
				},
			},
		);
	}

	// --- Unsaved-changes guards -------------------------------------------

	const blocking = computed(() => editing.value && isDirty.value);

	// In-app navigation. Returning false from `before` cancels the visit.
	// `saving` is set before our own request fires so save and restore are not
	// blocked by their own guard.
	const stopBeforeGuard = router.on('before', () => {
		if (!blocking.value || saving.value) return;
		return window.confirm(
			'You have unsaved page edits. Leave this page and lose them?',
		);
	});

	function onBeforeUnload(event: BeforeUnloadEvent) {
		if (!blocking.value) return;
		event.preventDefault();
		event.returnValue = '';
	}

	watch(blocking, (shouldBlock) => {
		if (shouldBlock) {
			window.addEventListener('beforeunload', onBeforeUnload);
		} else {
			window.removeEventListener('beforeunload', onBeforeUnload);
		}
	});

	onBeforeUnmount(() => {
		window.removeEventListener('beforeunload', onBeforeUnload);
		stopBeforeGuard();
	});

	return {
		editing,
		saving,
		draft,
		isDirty,
		revisions,
		start,
		addBox,
		removeBox,
		moveBox,
		save,
		discard,
		restore,
	};
}

export type InlineEditor = ReturnType<typeof useInlineEditor>;
