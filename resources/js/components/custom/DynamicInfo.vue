<script setup lang="ts">
import {
	boxClasses,
	segmentBoxes,
	type CmsAnnouncement,
	type CmsBox,
	type CmsNewsArchive as CmsNewsArchiveData,
} from '@/components/custom/cms/boxes';
import CmsBoxEditor from '@/components/custom/cms/CmsBoxEditor.vue';
import CmsEditBar from '@/components/custom/cms/CmsEditBar.vue';
import CmsNewsArchive from '@/components/custom/cms/CmsNewsArchive.vue';
import CmsNewsSlot from '@/components/custom/cms/CmsNewsSlot.vue';
import CmsNewsSlotEditor from '@/components/custom/cms/CmsNewsSlotEditor.vue';
import PlainTextEdit from '@/components/custom/cms/PlainTextEdit.vue';
import type { InlineEditor } from '@/components/custom/cms/useInlineEditor';
import Layout from '@/components/custom/Layout.vue';
import { Settings } from 'lucide-vue-next';
import Sortable from 'sortablejs';
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';

const props = withDefaults(
	defineProps<{
		hero: { title: string; description: string | null };
		content: CmsBox[];
		comingSoon?: boolean;
		notPublic?: boolean;
		/** Browser tab title. Falls back to the hero title. */
		title?: string | null;
		/** Live feed for the home page's News slot. */
		announcements?: CmsAnnouncement[];
		/** Paginated archive, present on a page carrying the archive slot. */
		newsArchive?: CmsNewsArchiveData | null;
		isHome?: boolean;
		/**
		 * Present only when the viewer may edit this page inline. Omitted (or
		 * null) everywhere else, which leaves this component exactly as it was.
		 */
		inline?: InlineEditor | null;
	}>(),
	{
		comingSoon: false,
		notPublic: false,
		title: null,
		announcements: () => [],
		newsArchive: null,
		isHome: false,
		inline: null,
	},
);

const segments = computed(() => segmentBoxes(props.content));

// Home's boxes stretch to a shared row height, as the old landing page did.
// Layout has several root nodes, so the home hook class lives on each grid.
const gridAlign = computed(() => (props.isHome ? 'cms-home' : 'items-start'));

const canEdit = computed(() => props.inline !== null);
const editing = computed(() => props.inline?.editing.value ?? false);
const draft = computed(() => props.inline?.draft.value ?? null);

// The News slot mirrors the old landing box, which had no block spacing.
function newsClasses(box: CmsBox): string[] {
	return boxClasses(box).filter((c) => c !== 'space-y-2');
}

const gridRef = ref<HTMLElement | null>(null);
let sortableBoxes: Sortable | null = null;

function destroySortable() {
	sortableBoxes?.destroy();
	sortableBoxes = null;
}

watch(editing, async (isEditing) => {
	destroySortable();
	if (!isEditing) return;
	await nextTick();
	if (!gridRef.value) return;
	sortableBoxes = Sortable.create(gridRef.value, {
		handle: '.drag-handle',
		animation: 150,
		dataIdAttr: 'data-id',
		onEnd(evt: {
			oldIndex?: number;
			newIndex?: number;
			from: HTMLElement;
			item: HTMLElement;
		}) {
			const from = evt.oldIndex;
			const to = evt.newIndex;
			if (from === undefined || to === undefined || from === to)
				return;
			// Sortable has already moved the node. Put the DOM back the way
			// Vue left it and let the draft array drive the re-render, or
			// Vue's keyed patch and the real DOM disagree about the order.
			const list = evt.from;
			const node = evt.item;
			list.removeChild(node);
			list.insertBefore(node, list.children[from] ?? null);
			props.inline?.moveBox(from, to);
		},
	});
});

onBeforeUnmount(destroySortable);
</script>

<template>
	<Layout
		:title="title ?? hero.title"
		class="info-page">
		<template #hero>
			<!-- Edit mode: hero title and description are flat text columns, so
			     they get a plain-text field, not a rich text editor. -->
			<div
				v-if="editing && draft"
				class="w-full max-w-2xl space-y-2 text-left"
				@click.stop>
				<PlainTextEdit
					v-model="draft.hero_title"
					tag="h1"
					placeholder="Hero title" />
				<PlainTextEdit
					v-model="draft.hero_description"
					tag="p"
					placeholder="Hero description" />
			</div>

			<!-- Admin, not editing: same hero plus the cog that enters edit mode. -->
			<template v-else-if="canEdit">
				<div class="flex items-center justify-center gap-2">
					<h1>{{ hero.title }}</h1>
					<button
						type="button"
						title="Edit this page"
						aria-label="Edit this page"
						class="text-cape-palliser-500 hover:text-cape-palliser-700 cursor-pointer"
						@click.stop="props.inline?.start()">
						<Settings class="size-6" />
					</button>
				</div>
				<p>{{ hero.description }}</p>
			</template>

			<!-- Everyone else: untouched. -->
			<template v-else>
				<h1>{{ hero.title }}</h1>
				<p>{{ hero.description }}</p>
			</template>
		</template>

		<!-- Staff-only visibility notice: shown when an admin previews a hidden page. -->
		<div
			v-if="notPublic"
			class="max-container p-4 pb-0">
			<div
				class="border-cape-palliser-500 bg-cape-palliser-50 text-cape-palliser-950 rounded-xl border p-4">
				<p class="font-bold">Hidden Page</p>
				<p>
					This page is hidden from the public. Only staff can see
					it right now.
				</p>
			</div>
		</div>

		<!-- Deferred feature notice: the page below is rules documentation, not a
		     playable in-app system yet. -->
		<div
			v-if="comingSoon"
			class="max-container p-4 pb-0">
			<div
				class="border-new-orleans-500 bg-new-orleans-50 text-cape-palliser-950 rounded-xl border p-4">
				<p class="font-bold">Coming Soon</p>
				<p>
					This part of the game has not moved onto the site yet.
					The rules below are here to read, but play still runs
					through our
					<a
						href="https://discord.gg/rArZNnkCfE"
						target="_blank"
						rel="noopener noreferrer"
						class="text-shakespeare-500 underline">
						Discord </a
					>. Nothing on this page submits a turn.
				</p>
			</div>
		</div>

		<!-- Edit mode: one flat grid, so Sortable indices match the draft array.
		     Band boxes show their strip as a ring instead. -->
		<div
			v-if="editing && draft"
			ref="gridRef"
			class="max-container grid grid-cols-1 gap-4 p-4 pb-32 lg:grid-cols-6"
			:class="gridAlign">
			<template
				v-for="(box, index) in draft.content"
				:key="box.id">
				<CmsNewsSlotEditor
					v-if="box.kind"
					v-model="draft.content[index]"
					:data-id="box.id"
					:announcements="announcements"
					:archive="newsArchive" />
				<CmsBoxEditor
					v-else
					v-model="draft.content[index]"
					:data-id="box.id"
					@remove="props.inline?.removeBox(box.id)" />
			</template>
		</div>

		<!-- View mode: runs of band boxes break out into a full-bleed strip. -->
		<template v-else>
			<template
				v-for="(segment, segmentIndex) in segments"
				:key="`${segment.band ? 'band' : 'grid'}-${segment.boxes[0].id}-${segmentIndex}`">
				<div
					v-if="segment.band"
					class="bg-cape-palliser-500">
					<div
						class="max-container grid grid-cols-1 gap-4 p-4 py-8 lg:grid-cols-6"
						:class="gridAlign">
						<template
							v-for="box in segment.boxes"
							:key="box.id">
							<div
								v-if="box.kind === 'news'"
								:class="newsClasses(box)">
								<CmsNewsSlot
									:announcements="announcements" />
							</div>
							<div
								v-else-if="box.kind === 'news-archive'"
								:class="[
									...boxClasses(box),
									'space-y-6',
								]">
								<CmsNewsArchive
									:archive="newsArchive" />
							</div>
							<div
								v-else
								:class="boxClasses(box)"
								v-html="box.html"></div>
						</template>
					</div>
				</div>
				<div
					v-else
					class="max-container grid grid-cols-1 gap-4 p-4 lg:grid-cols-6"
					:class="gridAlign">
					<template
						v-for="box in segment.boxes"
						:key="box.id">
						<div
							v-if="box.kind === 'news'"
							:class="newsClasses(box)">
							<CmsNewsSlot
								:announcements="announcements" />
						</div>
						<div
							v-else-if="box.kind === 'news-archive'"
							:class="[...boxClasses(box), 'space-y-6']">
							<CmsNewsArchive :archive="newsArchive" />
						</div>
						<div
							v-else
							:class="boxClasses(box)"
							v-html="box.html"></div>
					</template>
				</div>
			</template>
		</template>

		<CmsEditBar
			v-if="editing && draft && props.inline"
			v-model:title="draft.title"
			:dirty="props.inline.isDirty.value"
			:saving="props.inline.saving.value"
			:revisions="props.inline.revisions.value"
			@save="props.inline.save()"
			@discard="props.inline.discard()"
			@add-box="props.inline.addBox()"
			@restore="(id) => props.inline?.restore(id)" />
	</Layout>
</template>
