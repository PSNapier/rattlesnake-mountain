<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
	Card,
	CardContent,
	CardHeader,
	CardTitle,
} from '@/components/ui/card';
import {
	Dialog,
	DialogContent,
	DialogFooter,
	DialogHeader,
	DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { router } from '@inertiajs/vue3';
import { GripVertical } from 'lucide-vue-next';
import Sortable from 'sortablejs';
import { computed, onMounted, ref, watch } from 'vue';

interface CmsImage {
	name: string;
	link: string;
	path: string;
}

interface CmsPage {
	id: number;
	slug: string;
	title: string;
	description?: string | null;
	hero_title: string;
	hero_description: string | null;
	content: Record<string, string[]>;
	images: CmsImage[];
	sort_order: number;
}

interface MenuItemWithChildren {
	id: number;
	label: string;
	path: string | null;
	sort_order: number;
	children?: { id: number; label: string; path: string | null; sort_order: number }[];
}

interface Props {
	cmsPages: CmsPage[];
	menuItems: MenuItemWithChildren[];
}

const props = defineProps<Props>();

const menuListRef = ref<HTMLElement | null>(null);
let sortableMenu: Sortable | null = null;
const childSortables = new Map<number, Sortable>();

function getParentIdFromList(el: HTMLElement): number | null {
	const id = el.dataset.parentId;
	if (id === '' || id === undefined) return null;
	return Number(id);
}

function reorderAfterMove(evt: Sortable.SortableEvent) {
	const toEl = evt.to as HTMLElement;
	const fromEl = evt.from as HTMLElement;
	const toParentId = getParentIdFromList(toEl);
	const fromParentId = getParentIdFromList(fromEl);
	const toOrder = Sortable.get(toEl)?.toArray().map((id) => Number(id)) ?? [];
	const fromOrder = Sortable.get(fromEl)?.toArray().map((id) => Number(id)) ?? [];

	const toBody = { order: toOrder, parent_id: toParentId };
	const fromBody = { order: fromOrder, parent_id: fromParentId };

	router.post(route('admin.cms.menu.reorder'), toBody, {
		preserveScroll: true,
		onSuccess: () => {
			router.post(route('admin.cms.menu.reorder'), fromBody, { preserveScroll: true });
		},
	});
}

onMounted(() => {
	if (menuListRef.value) {
		sortableMenu = Sortable.create(menuListRef.value, {
			handle: '.drag-handle',
			animation: 150,
			dataIdAttr: 'data-id',
			group: 'menu',
			onEnd(evt) {
				if (evt.to !== evt.from) {
					reorderAfterMove(evt);
				} else {
					const order = sortableMenu?.toArray().map((id) => Number(id)) ?? [];
					if (order.length) {
						router.post(route('admin.cms.menu.reorder'), { order }, { preserveScroll: true });
					}
				}
			},
			onAdd(evt) {
				reorderAfterMove(evt);
			},
		});
	}
});

function setChildListRef(parentId: number, el: HTMLElement | null) {
	childSortables.get(parentId)?.destroy();
	childSortables.delete(parentId);
	if (!el) return;
	childSortables.set(parentId, Sortable.create(el, {
		handle: '.drag-handle',
		animation: 150,
		dataIdAttr: 'data-id',
		group: 'menu',
		onEnd(evt) {
			if (evt.to !== evt.from) {
				reorderAfterMove(evt);
			} else {
				const order = childSortables.get(parentId)?.toArray().map((id) => Number(id)) ?? [];
				if (order.length) {
					router.post(route('admin.cms.menu.reorder'), { order, parent_id: parentId }, { preserveScroll: true });
				}
			}
		},
		onAdd(evt) {
			reorderAfterMove(evt);
		},
	}));
}

const menuItemDialogOpen = ref(false);
const menuItemForm = ref<{ id: number | null; label: string; path: string; parent_id: number | null }>({
	id: null,
	label: '',
	path: '',
	parent_id: null,
});
const isNewMenuItem = ref(true);

const linkedPage = computed(() => {
	const path = menuItemForm.value.path;
	if (!path || path.startsWith('http')) return null;
	const slug = path.replace(/^\//, '').trim();
	return props.cmsPages.find((p) => p.slug === slug) ?? null;
});

const displayLinkedPage = ref<CmsPage | null>(null);
watch(linkedPage, (page) => {
	if (page) {
		displayLinkedPage.value = page;
	}
});

const pageForm = ref({
	description: '',
	hero_description: '',
	contentJson: '{}',
	imagesJson: '[]',
});

watch(linkedPage, (page) => {
	if (page) {
		pageForm.value = {
			description: page.description ?? '',
			hero_description: page.hero_description ?? '',
			contentJson: JSON.stringify(page.content ?? {}, null, 2),
			imagesJson: JSON.stringify(page.images ?? [], null, 2),
		};
	} else {
		pageForm.value = {
			description: '',
			hero_description: '',
			contentJson: '{}',
			imagesJson: '[]',
		};
	}
}, { immediate: false });

function openMenuEdit(item: { id: number; label: string; path: string | null }, parentId: number | null) {
	isNewMenuItem.value = false;
	menuItemForm.value = {
		id: item.id,
		label: item.label,
		path: item.path ?? '',
		parent_id: parentId,
	};
	menuItemDialogOpen.value = true;
}

function openMenuAdd(parentId: number | null) {
	isNewMenuItem.value = true;
	menuItemForm.value = {
		id: 0,
		label: '',
		path: '',
		parent_id: parentId,
	};
	pageForm.value = {
		description: '',
		hero_description: '',
		contentJson: '{}',
		imagesJson: '[]',
	};
	menuItemDialogOpen.value = true;
}

function closeMenuDialog() {
	menuItemDialogOpen.value = false;
	displayLinkedPage.value = null;
	menuItemForm.value = { id: null, label: '', path: '', parent_id: null };
}

function saveMenuItem() {
	const doClose = () => closeMenuDialog();

	const savePageThen = () => {
		const page = linkedPage.value;
		if (!page) {
			doClose();
			return;
		}
		let content: Record<string, string[]>;
		let images: CmsImage[];
		try {
			content = JSON.parse(pageForm.value.contentJson);
		} catch {
			alert('Invalid JSON in page content.');
			return;
		}
		try {
			images = JSON.parse(pageForm.value.imagesJson);
		} catch {
			alert('Invalid JSON in page images.');
			return;
		}
		router.put(route('admin.cms.pages.update', page.id), {
			title: menuItemForm.value.label,
			hero_title: menuItemForm.value.label,
			description: pageForm.value.description || null,
			hero_description: pageForm.value.hero_description || null,
			content,
			images,
		}, { onSuccess: doClose });
	};

	if (isNewMenuItem.value) {
		router.post(route('admin.cms.menu.store'), {
			label: menuItemForm.value.label,
			path: menuItemForm.value.path || null,
			parent_id: menuItemForm.value.parent_id,
		}, { onSuccess: savePageThen });
	} else if (menuItemForm.value.id) {
		router.put(route('admin.cms.menu.update', menuItemForm.value.id), {
			label: menuItemForm.value.label,
			path: menuItemForm.value.path || null,
			parent_id: menuItemForm.value.parent_id,
		}, { onSuccess: savePageThen });
	}
}

function deleteMenuItem(id: number) {
	if (!confirm('Delete this menu item and its children?')) return;
	router.delete(route('admin.cms.menu.destroy', id));
}
</script>

<template>
	<Card>
		<CardHeader>
			<CardTitle>Menu & Pages</CardTitle>
		</CardHeader>
		<CardContent>
			<div class="space-y-2">
				<div class="flex items-center justify-end">
					<Button size="sm" @click="openMenuAdd(null)">Add top-level item</Button>
				</div>
				<ul
					ref="menuListRef"
					data-parent-id=""
					class="space-y-2">
					<li
						v-for="item in props.menuItems"
						:key="item.id"
						:data-id="item.id"
						class="rounded border border-gray-200 p-3">
						<div class="flex items-center gap-3">
							<span class="drag-handle cursor-grab text-gray-400 active:cursor-grabbing">
								<GripVertical class="size-4" />
							</span>
							<span class="font-medium flex-1">{{ item.label }}</span>
							<span class="text-cape-palliser-600 text-sm">{{ item.path ?? '—' }}</span>
							<div class="flex gap-2">
								<Button variant="outline" size="sm" @click="openMenuEdit(item, null)">Edit</Button>
								<Button variant="destructive" size="sm" @click="deleteMenuItem(item.id)">Delete</Button>
							</div>
						</div>
						<ul
							v-if="item.children?.length"
							:ref="(el) => setChildListRef(item.id, el as HTMLElement | null)"
							:data-parent-id="item.id"
							class="ml-4 mt-2 space-y-1 border-l-2 border-gray-200 pl-4">
							<li
								v-for="child in item.children"
								:key="child.id"
								:data-id="child.id"
								class="flex cursor-default items-center gap-3 py-1 hover:bg-gray-50">
								<span class="drag-handle cursor-grab text-gray-400 active:cursor-grabbing">
									<GripVertical class="size-4" />
								</span>
								<span class="flex-1">{{ child.label }}</span>
								<span class="text-cape-palliser-600 text-sm">{{ child.path ?? '—' }}</span>
								<div class="flex gap-2">
									<Button variant="outline" size="sm" @click="openMenuEdit(child, item.id)">Edit</Button>
									<Button variant="destructive" size="sm" @click="deleteMenuItem(child.id)">Delete</Button>
								</div>
							</li>
						</ul>
						<div class="ml-4 mt-2 flex justify-end">
							<Button size="sm" @click="openMenuAdd(item.id)">Add child</Button>
						</div>
					</li>
				</ul>
			</div>
		</CardContent>
	</Card>

	<Dialog v-model:open="menuItemDialogOpen">
		<DialogContent class="max-h-[90vh] overflow-y-auto max-w-2xl">
			<DialogHeader>
				<DialogTitle>{{ isNewMenuItem ? 'Add menu item' : 'Edit menu item' }}</DialogTitle>
			</DialogHeader>
			<div class="space-y-6">
				<div class="space-y-4">
					<div>
						<Label for="menu_label">Label / Title</Label>
						<Input id="menu_label" v-model="menuItemForm.label" class="mt-1 w-full" placeholder="Menu label, page title, hero title" />
					</div>
					<div>
						<Label for="menu_path">Path (e.g. /contact-us or full URL)</Label>
						<Input id="menu_path" v-model="menuItemForm.path" class="mt-1 w-full" placeholder="/path" />
					</div>
					<div v-if="isNewMenuItem">
						<Label for="menu_parent">Parent</Label>
						<select
							id="menu_parent"
							v-model.number="menuItemForm.parent_id"
							class="border-input mt-1 w-full rounded-md border px-3 py-2 text-sm">
							<option :value="null">— Top level —</option>
							<option
								v-for="item in props.menuItems"
								:key="item.id"
								:value="item.id">
								{{ item.label }}
							</option>
						</select>
					</div>
				</div>

				<div v-if="displayLinkedPage" class="space-y-4 border-t border-gray-200 pt-6">
					<div>
						<Label for="hero_description">Hero description</Label>
						<textarea
							id="hero_description"
							v-model="pageForm.hero_description"
							rows="2"
							class="border-input mt-1 w-full rounded-md border px-3 py-2 text-sm" />
					</div>
					<div>
						<Label for="contentJson">Content (JSON)</Label>
						<textarea
							id="contentJson"
							v-model="pageForm.contentJson"
							rows="10"
							class="border-input mt-1 w-full font-mono text-sm rounded-md border px-3 py-2" />
					</div>
					<div>
						<Label for="imagesJson">Images (JSON)</Label>
						<textarea
							id="imagesJson"
							v-model="pageForm.imagesJson"
							rows="4"
							class="border-input mt-1 w-full font-mono text-sm rounded-md border px-3 py-2" />
					</div>
				</div>
			</div>
			<DialogFooter>
				<Button variant="outline" @click="closeMenuDialog">Cancel</Button>
				<Button @click="saveMenuItem">Save</Button>
			</DialogFooter>
		</DialogContent>
	</Dialog>
</template>
