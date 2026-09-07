<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

interface Option {
	value: number | string | null;
	label: string;
}

interface Props {
	modelValue?: number | string | null;
	options?: Option[];
	placeholder?: string;
	id?: string;
	minChars?: number;
	maxResults?: number;
}

const props = withDefaults(defineProps<Props>(), {
	options: () => [],
	placeholder: 'Search…',
	minChars: 2,
	maxResults: 8,
	modelValue: null,
});

const emit = defineEmits<{
	'update:modelValue': [value: number | string | null];
}>();

const query = ref('');
const open = ref(false);
const root = ref<HTMLElement | null>(null);

const selectedOption = computed(
	() =>
		props.options.find((option) => option.value === props.modelValue) ??
		null,
);

watch(
	() => props.modelValue,
	(value) => {
		if (value === null || value === undefined || value === '') {
			if (!open.value) {
				query.value = '';
			}

			return;
		}

		const match = props.options.find((option) => option.value === value);
		if (match && !open.value) {
			query.value = match.label;
		}
	},
	{ immediate: true },
);

const filteredOptions = computed(() => {
	const needle = query.value.trim().toLowerCase();
	if (needle.length < props.minChars) {
		return [];
	}

	return props.options
		.filter(
			(option) =>
				option.value !== null &&
				option.label.toLowerCase().includes(needle),
		)
		.slice(0, props.maxResults);
});

const showResults = computed(
	() => open.value && query.value.trim().length >= props.minChars,
);

function onFocus(): void {
	open.value = true;
	if (selectedOption.value) {
		query.value = '';
	}
}

function onQueryUpdate(value: string | number): void {
	query.value = String(value);
	open.value = true;

	if (
		props.modelValue !== null &&
		props.modelValue !== undefined &&
		props.modelValue !== ''
	) {
		emit('update:modelValue', null);
	}
}

function selectOption(option: Option): void {
	emit('update:modelValue', option.value);
	query.value = option.label;
	open.value = false;
}

function clearSelection(): void {
	emit('update:modelValue', null);
	query.value = '';
	open.value = false;
}

function handleClickOutside(event: Event): void {
	const target = event.target as Node;
	if (root.value && !root.value.contains(target)) {
		open.value = false;
		if (selectedOption.value) {
			query.value = selectedOption.value.label;
		} else if (
			props.modelValue === null ||
			props.modelValue === undefined ||
			props.modelValue === ''
		) {
			query.value = '';
		}
	}
}

onMounted(() => {
	document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
	document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
	<div
		ref="root"
		class="relative">
		<div class="relative">
			<Input
				:id="id"
				:model-value="query"
				type="text"
				autocomplete="off"
				:placeholder="placeholder"
				class="pr-9"
				@focus="onFocus"
				@update:model-value="onQueryUpdate" />
			<button
				v-if="
					modelValue !== null &&
					modelValue !== undefined &&
					modelValue !== ''
				"
				type="button"
				class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 hover:text-gray-600"
				aria-label="Clear selection"
				@click="clearSelection">
				×
			</button>
		</div>

		<div
			v-if="showResults"
			class="border-input bg-cape-palliser-50 text-foreground absolute right-0 left-0 z-50 mt-1 max-h-60 overflow-auto rounded-md border py-1 shadow-lg">
			<button
				v-for="option in filteredOptions"
				:key="String(option.value)"
				type="button"
				class="hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground w-full truncate px-3 py-2 text-left text-sm focus:outline-none"
				@mousedown.prevent="selectOption(option)">
				{{ option.label }}
			</button>
			<p
				v-if="filteredOptions.length === 0"
				class="text-muted-foreground px-3 py-2 text-sm">
				No matches.
			</p>
		</div>
		<p
			v-else-if="
				open &&
				query.trim().length > 0 &&
				query.trim().length < minChars
			"
			class="text-muted-foreground mt-1 text-xs">
			Type at least {{ minChars }} characters to search.
		</p>
	</div>
</template>
