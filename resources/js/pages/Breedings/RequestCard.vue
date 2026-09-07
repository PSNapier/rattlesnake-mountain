<script setup lang="ts">
import ImageUpload from '@/components/ImageUpload.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface HorseSummary {
	id: number;
	name: string;
	sex?: string | null;
	geno?: string;
	age_months?: number;
	state?: string;
}

interface BreedingRequestRow {
	id: number;
	status: string;
	evidence_url: string;
	notes?: string | null;
	result_options?: { geno: string; phenotype: string }[] | null;
	selected_option_index?: number | null;
	sire?: HorseSummary | null;
	dam?: HorseSummary | null;
	foal?: HorseSummary | null;
	created_at: string;
}

interface Herd {
	id: number;
	name: string;
}

interface Props {
	request: BreedingRequestRow;
	herds: Herd[];
	phenotypePlaceholder: string;
}

const props = defineProps<Props>();

const selectedOption = ref(0);
const foalForm = useForm({
	option_index: 0,
	name: '',
	sex: 'mare',
	design_link: '',
	herd_id: null as number | null,
});

const cancelRequest = (): void => {
	router.post(
		route('breedings.cancel', props.request.id),
		{},
		{ preserveScroll: true },
	);
};

const createFoal = (): void => {
	foalForm.option_index = selectedOption.value;
	foalForm.post(route('breedings.foal', props.request.id));
};

const handleImageUploadSuccess = (data: { url: string }): void => {
	foalForm.design_link = data.url;
};
</script>

<template>
	<div class="space-y-4 rounded border p-4">
		<div class="flex items-start justify-between gap-2">
			<div>
				<p class="font-medium">
					{{ props.request.sire?.name }} ×
					{{ props.request.dam?.name }}
				</p>
				<p class="text-sm text-gray-600">
					Status: {{ props.request.status }}
				</p>
			</div>
			<Button
				v-if="props.request.status === 'pending_staff'"
				size="sm"
				variant="destructive"
				@click="cancelRequest">
				Cancel
			</Button>
		</div>

		<div class="grid gap-4 md:grid-cols-2">
			<div>
				<p class="text-sm font-medium">
					Sire: {{ props.request.sire?.name }}
				</p>
				<p class="text-xs text-gray-600">
					{{ props.request.sire?.sex }} ·
					{{ props.request.sire?.geno }}
				</p>
			</div>
			<div>
				<p class="text-sm font-medium">
					Dam: {{ props.request.dam?.name }}
				</p>
				<p class="text-xs text-gray-600">
					{{ props.request.dam?.sex }} ·
					{{ props.request.dam?.geno }}
				</p>
			</div>
		</div>

		<div class="text-sm">
			<p>
				Evidence:
				<a
					:href="props.request.evidence_url"
					class="text-shakespeare-600 underline"
					target="_blank"
					rel="noopener">
					{{ props.request.evidence_url }}
				</a>
			</p>
			<p
				v-if="props.request.notes"
				class="mt-1 text-gray-600">
				Notes: {{ props.request.notes }}
			</p>
		</div>

		<div
			v-if="props.request.result_options?.length"
			class="space-y-2">
			<p class="text-sm font-medium">Genotype Options</p>
			<label
				v-for="(option, index) in props.request.result_options"
				:key="index"
				class="flex cursor-pointer items-start gap-3 rounded border p-3">
				<input
					v-model="selectedOption"
					type="radio"
					:value="index"
					:disabled="props.request.status !== 'results_ready'"
					class="mt-1" />
				<div>
					<p class="font-mono font-medium">{{ option.geno }}</p>
					<p class="text-sm text-gray-600">
						{{
							option.phenotype ||
							props.phenotypePlaceholder
						}}
					</p>
				</div>
			</label>
		</div>

		<form
			v-if="props.request.status === 'results_ready'"
			class="space-y-4 border-t pt-4"
			@submit.prevent="createFoal">
			<p class="text-sm font-medium">Create Pending Foal</p>
			<div>
				<Label :for="`foal_name_${props.request.id}`">Name</Label>
				<Input
					:id="`foal_name_${props.request.id}`"
					v-model="foalForm.name"
					required />
			</div>
			<div>
				<Label :for="`foal_sex_${props.request.id}`">Sex</Label>
				<Select
					:id="`foal_sex_${props.request.id}`"
					v-model="foalForm.sex"
					:options="[
						{ value: 'mare', label: 'Mare' },
						{ value: 'stallion', label: 'Stallion' },
					]" />
			</div>
			<div>
				<Label>Design Image (optional)</Label>
				<ImageUpload
					:upload-url="route('horses.upload-image')"
					accept="image/png,image/jpeg,image/jpg"
					@success="handleImageUploadSuccess" />
				<Input
					v-model="foalForm.design_link"
					class="mt-2"
					type="url"
					placeholder="Or paste design URL" />
			</div>
			<div>
				<Label :for="`herd_id_${props.request.id}`"
					>Herd (optional)</Label
				>
				<Select
					:id="`herd_id_${props.request.id}`"
					v-model="foalForm.herd_id"
					:options="[
						{ value: null, label: 'No herd' },
						...props.herds.map((herd) => ({
							value: herd.id,
							label: herd.name,
						})),
					]" />
			</div>
			<p
				v-if="foalForm.errors.foal"
				class="text-sm text-red-500">
				{{ foalForm.errors.foal }}
			</p>
			<Button
				type="submit"
				:disabled="foalForm.processing">
				Create Pending Foal
			</Button>
		</form>

		<div
			v-if="props.request.foal"
			class="border-t pt-3 text-sm">
			<span class="font-medium">Foal: </span>
			<Link
				:href="route('horses.show', props.request.foal.id)"
				class="text-shakespeare-600 underline">
				{{ props.request.foal.name }}
			</Link>
		</div>
	</div>
</template>
