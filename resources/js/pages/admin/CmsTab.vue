<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
	Dialog,
	DialogContent,
	DialogFooter,
	DialogHeader,
	DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import AnnouncementsSection from '@/pages/admin/AnnouncementsSection.vue';
import { router, usePage } from '@inertiajs/vue3';
import { GripVertical } from 'lucide-vue-next';
import Sortable from 'sortablejs';
import {
	computed,
	nextTick,
	onBeforeUnmount,
	onMounted,
	ref,
	watch,
} from 'vue';

interface NavPage {
	id: number;
	slug: string;
	title: string;
	visibility: 'live' | 'hidden';
}

interface NavNode {
	id: number;
	label: string;
	path: string | null;
	page: NavPage | null;
	children?: NavNode[];
}

interface Props {
	systemPages: NavPage[];
	headerTree: NavNode[];
	announcements?: InstanceType<
		typeof AnnouncementsSection
	>['$props']['announcements'];
}

const props = defineProps<Props>();

/** Home's read-more lands on `news`, so it can't be hidden or deleted. */
const isProtected = (page: NavPage | null) => page?.slug === 'news';

const inertiaPage = usePage<{ errors: Record<string, string> }>();
const treeError = computed(() => {
	const errors = inertiaPage.props.errors ?? {};

	return errors.order ?? errors.parent_id ?? errors.path ?? errors.label;
});

function toggleVisibility(page: NavPage) {
	const visibility = page.visibility === 'live' ? 'hidden' : 'live';
	router.patch(
		route('admin.cms.pages.visibility', page.id),
		{ visibility },
		{ preserveScroll: true },
	);
}

// Header Pages tree. Every list, top level and each dropdown, is its own
// Sortable in one shared group, so a row can be dragged between them.
const treeRef = ref<HTMLElement | null>(null);
const dragging = ref(false);
let sortables: Sortable[] = [];

function destroySortables() {
	sortables.forEach((sortable) => sortable.destroy());
	sortables = [];
}

function mountSortables() {
	destroySortables();
	if (!treeRef.value) return;

	const lists = [
		treeRef.value,
		...treeRef.value.querySelectorAll<HTMLElement>('ul[data-parent]'),
	];

	sortables = lists.map((list) =>
		Sortable.create(list, {
			group: 'nav',
			handle: '.drag-handle',
			animation: 150,
			// Pointer events instead of native HTML5 drag: nested lists pick
			// the right drop target reliably, and touch behaves like mouse.
			forceFallback: true,
			fallbackOnBody: true,
			swapThreshold: 0.65,
			emptyInsertThreshold: 16,
			onStart() {
				dragging.value = true;
			},
			// Dropdowns are one level deep: a row that has its own children
			// cannot go inside another dropdown.
			onMove(event) {
				const intoDropdown = event.to.dataset.parent !== undefined;
				const hasChildren =
					event.dragged.querySelector('ul[data-parent] > li') !==
					null;

				return !(intoDropdown && hasChildren);
			},
			onEnd(event) {
				dragging.value = false;

				const to = event.to;
				const order = Array.from(to.children)
					.map((child) =>
						Number((child as HTMLElement).dataset.id),
					)
					.filter((id) => !Number.isNaN(id));
				const parentId = to.dataset.parent
					? Number(to.dataset.parent)
					: null;
				const moved =
					event.from !== to || event.oldIndex !== event.newIndex;

				// Put the DOM back the way Vue rendered it. The server's
				// answer re-renders the tree, and Vue must not find nodes it
				// did not place.
				event.from.insertBefore(
					event.item,
					event.from.children[event.oldIndex ?? 0] ?? null,
				);

				if (!moved || order.length === 0) return;

				router.post(
					route('admin.cms.menu.reorder'),
					{ order, parent_id: parentId },
					{ preserveScroll: true },
				);
			},
		}),
	);
}

onMounted(mountSortables);
onBeforeUnmount(destroySortables);
watch(
	() => props.headerTree,
	() => nextTick(mountSortables),
);

// Inline editing, for page rows (label) and ghost rows (label and link).
const editingId = ref<number | null>(null);
const editForm = ref({ label: '', path: '' });

function startEdit(node: NavNode) {
	editingId.value = node.id;
	editForm.value = { label: node.label, path: node.path ?? '' };
}

function saveEdit(node: NavNode, parentId: number | null) {
	const payload: { label: string; parent_id: number | null; path?: string } =
		{
			label: editForm.value.label,
			parent_id: parentId,
		};
	if (!node.page) payload.path = editForm.value.path;

	router.put(route('admin.cms.menu.update', node.id), payload, {
		preserveScroll: true,
		onSuccess: () => (editingId.value = null),
	});
}

const addingLink = ref(false);
const linkForm = ref({ label: '', path: '' });

function saveLink() {
	router.post(route('admin.cms.menu.store'), linkForm.value, {
		preserveScroll: true,
		onSuccess: () => {
			addingLink.value = false;
			linkForm.value = { label: '', path: '' };
		},
	});
}

// Deleting a page row deletes the page (soft, and its row waits for a
// restore). Deleting a ghost row removes the link.
const deleteDialogOpen = ref(false);
const deleteTarget = ref<NavNode | null>(null);

function openDelete(node: NavNode) {
	deleteTarget.value = node;
	deleteDialogOpen.value = true;
}

function closeDeleteDialog() {
	deleteDialogOpen.value = false;
	setTimeout(() => {
		deleteTarget.value = null;
	}, 250);
}

function confirmDelete() {
	const node = deleteTarget.value;
	if (!node) return;

	const url = node.page
		? route('admin.cms.pages.destroy', node.page.id)
		: route('admin.cms.menu.destroy', node.id);

	router.delete(url, { preserveScroll: true, onSuccess: closeDeleteDialog });
}
</script>

<template>
	<Card>
		<CardHeader>
			<CardTitle>System Pages</CardTitle>
			<p class="text-cape-palliser-600 text-sm">
				Reached without the navbar. Editable, but never moved or
				deleted.
			</p>
		</CardHeader>
		<CardContent>
			<ul
				class="space-y-2"
				data-testid="system-pages">
				<li
					v-for="page in props.systemPages"
					:key="page.id"
					class="flex flex-wrap items-center gap-3 rounded border border-gray-200 p-3">
					<span class="flex-1 font-medium">{{
						page.title
					}}</span>
					<a
						:href="
							page.slug === 'home' ? '/' : `/${page.slug}`
						"
						class="text-cape-palliser-600 text-sm underline"
						>/{{ page.slug === 'home' ? '' : page.slug }}</a
					>
					<Button
						variant="outline"
						size="sm"
						:disabled="page.slug === 'home'"
						:title="
							page.slug === 'home'
								? 'The home page can\'t be hidden'
								: page.visibility === 'live'
									? 'Click to hide this page from the public'
									: 'Click to make this page live'
						"
						@click="toggleVisibility(page)">
						{{
							page.visibility === 'live'
								? 'Live'
								: 'Hidden'
						}}
					</Button>
				</li>
			</ul>
		</CardContent>
	</Card>

	<Card>
		<CardHeader
			class="flex flex-row flex-wrap items-start justify-between gap-2">
			<div>
				<CardTitle>Header Pages</CardTitle>
				<p class="text-cape-palliser-600 text-sm">
					The navbar. Drag a row under another to put it in that
					dropdown. Hidden pages stay here but leave the public
					header.
				</p>
			</div>
			<Button
				size="sm"
				@click="addingLink = !addingLink"
				>Add link</Button
			>
		</CardHeader>
		<CardContent class="space-y-3">
			<p
				v-if="treeError"
				class="text-sm text-red-600"
				role="alert">
				{{ treeError }}
			</p>

			<form
				v-if="addingLink"
				class="flex flex-wrap items-end gap-2 rounded border border-dashed border-gray-300 p-3"
				@submit.prevent="saveLink">
				<label class="flex min-w-40 flex-1 flex-col gap-1 text-sm">
					Label
					<Input
						v-model="linkForm.label"
						required
						placeholder="Horses" />
				</label>
				<label class="flex min-w-40 flex-1 flex-col gap-1 text-sm">
					Link
					<Input
						v-model="linkForm.path"
						required
						placeholder="/horses or https://…" />
				</label>
				<Button
					type="submit"
					size="sm"
					>Save link</Button
				>
				<Button
					type="button"
					variant="outline"
					size="sm"
					@click="addingLink = false"
					>Cancel</Button
				>
			</form>

			<ul
				ref="treeRef"
				class="space-y-2"
				data-testid="header-tree">
				<li
					v-for="node in props.headerTree"
					:key="node.id"
					:data-id="node.id"
					class="rounded border border-gray-200">
					<div
						class="flex flex-wrap items-center gap-3 p-3"
						:class="{
							'opacity-50':
								node.page?.visibility === 'hidden',
						}">
						<span
							class="drag-handle cursor-grab touch-none text-gray-400 active:cursor-grabbing"
							:aria-label="`Drag ${node.label}`">
							<GripVertical class="size-5" />
						</span>

						<template v-if="editingId === node.id">
							<Input
								v-model="editForm.label"
								class="max-w-56"
								aria-label="Label" />
							<Input
								v-if="!node.page"
								v-model="editForm.path"
								class="max-w-72"
								aria-label="Link" />
							<Button
								size="sm"
								@click="saveEdit(node, null)"
								>Save</Button
							>
							<Button
								variant="outline"
								size="sm"
								@click="editingId = null"
								>Cancel</Button
							>
						</template>
						<template v-else>
							<span class="flex-1 font-medium">{{
								node.label
							}}</span>
							<span
								v-if="node.page"
								class="text-cape-palliser-600 text-sm"
								>/{{ node.page.slug }}</span
							>
							<span
								v-else
								class="text-cape-palliser-600 text-sm"
								><span
									class="mr-1 rounded bg-gray-100 px-1.5 py-0.5 text-xs uppercase"
									>Link</span
								>{{ node.path }}</span
							>
							<div class="flex flex-wrap gap-2">
								<Button
									v-if="node.page"
									variant="outline"
									size="sm"
									:disabled="isProtected(node.page)"
									@click="
										toggleVisibility(node.page)
									">
									{{
										node.page.visibility ===
										'live'
											? 'Live'
											: 'Hidden'
									}}
								</Button>
								<Button
									variant="outline"
									size="sm"
									@click="startEdit(node)"
									>Edit</Button
								>
								<Button
									variant="destructive"
									size="sm"
									:disabled="isProtected(node.page)"
									@click="openDelete(node)"
									>Delete</Button
								>
							</div>
						</template>
					</div>

					<ul
						:data-parent="node.id"
						class="mr-3 ml-8 min-h-3 space-y-2 rounded"
						:class="[
							node.children?.length ? 'mb-3' : 'mb-1',
							// Outline, not border or padding: resizing lists
							// mid-drag shifts rows out from under the pointer.
							dragging
								? 'outline-1 outline-offset-2 outline-gray-300 outline-dashed'
								: '',
						]">
						<li
							v-for="child in node.children ?? []"
							:key="child.id"
							:data-id="child.id"
							class="rounded border border-gray-200">
							<div
								class="flex flex-wrap items-center gap-3 p-3"
								:class="{
									'opacity-50':
										child.page?.visibility ===
										'hidden',
								}">
								<span
									class="drag-handle cursor-grab touch-none text-gray-400 active:cursor-grabbing"
									:aria-label="`Drag ${child.label}`">
									<GripVertical class="size-5" />
								</span>

								<template v-if="editingId === child.id">
									<Input
										v-model="editForm.label"
										class="max-w-56"
										aria-label="Label" />
									<Input
										v-if="!child.page"
										v-model="editForm.path"
										class="max-w-72"
										aria-label="Link" />
									<Button
										size="sm"
										@click="
											saveEdit(child, node.id)
										"
										>Save</Button
									>
									<Button
										variant="outline"
										size="sm"
										@click="editingId = null"
										>Cancel</Button
									>
								</template>
								<template v-else>
									<span class="flex-1 font-medium">{{
										child.label
									}}</span>
									<span
										v-if="child.page"
										class="text-cape-palliser-600 text-sm"
										>/{{ child.page.slug }}</span
									>
									<span
										v-else
										class="text-cape-palliser-600 text-sm"
										><span
											class="mr-1 rounded bg-gray-100 px-1.5 py-0.5 text-xs uppercase"
											>Link</span
										>{{ child.path }}</span
									>
									<div class="flex flex-wrap gap-2">
										<Button
											v-if="child.page"
											variant="outline"
											size="sm"
											:disabled="
												isProtected(
													child.page,
												)
											"
											@click="
												toggleVisibility(
													child.page,
												)
											">
											{{
												child.page
													.visibility ===
												'live'
													? 'Live'
													: 'Hidden'
											}}
										</Button>
										<Button
											variant="outline"
											size="sm"
											@click="startEdit(child)"
											>Edit</Button
										>
										<Button
											variant="destructive"
											size="sm"
											:disabled="
												isProtected(
													child.page,
												)
											"
											@click="
												openDelete(child)
											"
											>Delete</Button
										>
									</div>
								</template>
							</div>
						</li>
					</ul>
				</li>
			</ul>
		</CardContent>
	</Card>

	<Dialog v-model:open="deleteDialogOpen">
		<DialogContent>
			<DialogHeader>
				<DialogTitle
					>Delete "{{ deleteTarget?.label }}"?</DialogTitle
				>
			</DialogHeader>
			<p v-if="deleteTarget?.page">
				This deletes the page. Staff can recover it, and its navbar
				entry comes back in the same place.
			</p>
			<p v-else>
				This removes the link from the navbar. Pages inside it move
				to top level.
			</p>
			<DialogFooter>
				<Button
					variant="outline"
					@click="closeDeleteDialog"
					>Cancel</Button
				>
				<Button
					variant="destructive"
					@click="confirmDelete"
					>Delete</Button
				>
			</DialogFooter>
		</DialogContent>
	</Dialog>

	<AnnouncementsSection :announcements="props.announcements" />
</template>
