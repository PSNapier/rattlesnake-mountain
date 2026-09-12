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
import { router } from '@inertiajs/vue3';
import { GripVertical } from 'lucide-vue-next';
import Sortable from 'sortablejs';
import { onMounted, ref } from 'vue';

interface CmsBox {
	id: string;
	span: 1 | 2 | 3;
	style: 'box' | 'box-alt' | 'box-centered';
	html: string;
}

interface CmsMenuLink {
	id: number;
	label: string;
	path: string;
}

interface CmsPage {
	id: number;
	slug: string;
	title: string;
	description?: string | null;
	hero_title: string;
	hero_description: string | null;
	content: CmsBox[];
	coming_soon: boolean;
	visibility: 'live' | 'hidden';
	sort_order: number;
	menu_links: CmsMenuLink[];
}

interface Props {
	cmsPages: CmsPage[];
}

const props = defineProps<Props>();

const pagesListRef = ref<HTMLElement | null>(null);
let sortablePages: Sortable | null = null;

onMounted(() => {
	if (pagesListRef.value) {
		sortablePages = Sortable.create(pagesListRef.value, {
			handle: '.drag-handle',
			animation: 150,
			dataIdAttr: 'data-id',
			onEnd() {
				const order =
					sortablePages?.toArray().map((id) => Number(id)) ?? [];
				if (order.length) {
					router.post(
						route('admin.cms.pages.reorder'),
						{ order },
						{ preserveScroll: true },
					);
				}
			},
		});
	}
});

function toggleVisibility(page: CmsPage) {
	const visibility = page.visibility === 'live' ? 'hidden' : 'live';
	router.patch(
		route('admin.cms.pages.visibility', page.id),
		{ visibility },
		{ preserveScroll: true },
	);
}

const deletePageDialogOpen = ref(false);
const pageToDelete = ref<CmsPage | null>(null);

function openDeletePage(page: CmsPage) {
	pageToDelete.value = page;
	deletePageDialogOpen.value = true;
}

function closeDeletePageDialog() {
	deletePageDialogOpen.value = false;
	setTimeout(() => {
		pageToDelete.value = null;
	}, 250);
}

function confirmDeletePage() {
	if (!pageToDelete.value) return;
	router.delete(route('admin.cms.pages.destroy', pageToDelete.value.id), {
		preserveScroll: true,
		onSuccess: closeDeletePageDialog,
	});
}
</script>

<template>
	<Card>
		<CardHeader>
			<CardTitle>Pages</CardTitle>
		</CardHeader>
		<CardContent>
			<ul
				ref="pagesListRef"
				class="space-y-2">
				<li
					v-for="page in props.cmsPages"
					:key="page.id"
					:data-id="page.id"
					class="flex items-center gap-3 rounded border border-gray-200 p-3">
					<span
						class="drag-handle cursor-grab text-gray-400 active:cursor-grabbing">
						<GripVertical class="size-4" />
					</span>
					<span class="flex-1 font-medium">{{
						page.title
					}}</span>
					<span class="text-cape-palliser-600 text-sm"
						>/{{ page.slug }}</span
					>
					<div class="flex gap-2">
						<Button
							v-if="page.slug !== 'home'"
							variant="outline"
							size="sm"
							:title="
								page.visibility === 'live'
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
						<Button
							v-if="page.slug !== 'home'"
							variant="destructive"
							size="sm"
							@click="openDeletePage(page)"
							>Delete</Button
						>
					</div>
				</li>
			</ul>
		</CardContent>
	</Card>

	<Dialog v-model:open="deletePageDialogOpen">
		<DialogContent>
			<DialogHeader>
				<DialogTitle
					>Delete "{{ pageToDelete?.title }}"?</DialogTitle
				>
			</DialogHeader>
			<div class="space-y-3">
				<p>This deletion can be recovered by staff if needed.</p>
				<div v-if="pageToDelete?.menu_links.length">
					<p class="font-medium">
						These menu items link to this page and will
						<span class="font-bold">not</span> be removed:
					</p>
					<ul class="list-inside list-disc">
						<li
							v-for="link in pageToDelete.menu_links"
							:key="link.id">
							{{ link.label }}
						</li>
					</ul>
				</div>
			</div>
			<DialogFooter>
				<Button
					variant="outline"
					@click="closeDeletePageDialog"
					>Cancel</Button
				>
				<Button
					variant="destructive"
					@click="confirmDeletePage"
					>Delete</Button
				>
			</DialogFooter>
		</DialogContent>
	</Dialog>
</template>
