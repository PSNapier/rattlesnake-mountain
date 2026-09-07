<script setup lang="ts">
import ImageUpload from '@/components/ImageUpload.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { useUploadLimit } from '@/composables/useUploadLimit';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Herd {
	id: number;
	name: string;
}

interface Horse {
	id: number;
	name: string;
	sex?: string | null;
	age_years: number;
	age_months_part: number;
	formatted_age: string;
	design_link?: string;
	geno: string;
	herd_id?: number;
	bloodline: number[];
	progeny: number[];
	stats: any[];
	inventory: any[];
	equipment: any[];
}

interface AdminLog {
	id: number;
	admin_name: string;
	notes: string | null;
	created_at: string;
}

interface Props {
	horse: Horse;
	herds: Herd[];
	isPendingEdit?: boolean;
	publicHorse?: Horse | null;
	adminLogs?: AdminLog[];
}

const props = defineProps<Props>();

const page = usePage<SharedData>();
const isStaff = computed(() => {
	const role = page.props.auth.user?.role;
	return !!role && role !== 'user';
});
const { maxBytes: maxFileSize, sizeHint } = useUploadLimit();
const fileTypeHint = computed(
	() => `PNG, JPG, or JPEG files only, ${sizeHint.value}`,
);

const breadcrumbs: BreadcrumbItem[] = [
	{
		title: 'Horses',
		href: '/horses',
	},
	{
		title: props.horse.name,
		href: `/horses/${props.horse.id}`,
	},
	{
		title: 'Edit',
		href: `/horses/${props.horse.id}/edit`,
	},
];

const form = useForm({
	name: props.horse.name,
	sex: props.horse.sex ?? '',
	age_years: props.horse.age_years,
	age_months: props.horse.age_months_part,
	design_link: props.horse.design_link || '',
	geno: props.horse.geno,
	herd_id: props.horse.herd_id,
	stats: props.horse.stats,
	inventory: props.horse.inventory,
	equipment: props.horse.equipment,
});

const canEditSex = computed(() => !props.horse.sex || isStaff.value);

const submit = () => {
	form.transform((data) => {
		if (!canEditSex.value) {
			const { sex: _sex, ...rest } = data;

			return rest;
		}

		return data;
	}).put(route('horses.update', props.horse.id));
};

const handleImageUploadSuccess = (data: { url: string }) => {
	form.design_link = data.url;
};
</script>

<template>
	<Head title="Edit Horse" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="mx-auto max-w-2xl">
			<div class="space-y-6">
				<div>
					<h1 class="text-3xl font-bold">Edit Horse</h1>
					<p class="text-gray-600">
						Update your horse information.
					</p>
					<div
						v-if="props.isPendingEdit"
						class="mt-4 rounded-md border border-yellow-200 bg-yellow-50 p-4">
						<div class="flex">
							<div class="flex-shrink-0">
								<svg
									class="h-5 w-5 text-yellow-400"
									viewBox="0 0 20 20"
									fill="currentColor">
									<path
										fill-rule="evenodd"
										d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
										clip-rule="evenodd" />
								</svg>
							</div>
							<div class="ml-3">
								<h3
									class="text-sm font-medium text-yellow-800">
									Pending Edits
								</h3>
								<div
									class="mt-2 text-sm text-yellow-700">
									<p>
										You have pending edits for
										this horse waiting for admin
										approval. The public version
										remains unchanged until your
										edits are approved.
									</p>
								</div>
							</div>
						</div>
					</div>

					<div
						v-if="
							props.adminLogs && props.adminLogs.length > 0
						"
						class="mt-4 space-y-3">
						<h2 class="text-lg font-semibold">
							Admin Messages
						</h2>
						<div
							v-for="log in props.adminLogs"
							:key="log.id"
							class="rounded-md border border-blue-200 bg-blue-50 p-4">
							<div
								class="flex items-start justify-between">
								<div class="flex-1">
									<div
										class="text-sm font-medium text-blue-900">
										Message from
										{{ log.admin_name }}
									</div>
									<div
										class="mt-1 text-sm text-blue-800">
										{{ log.notes }}
									</div>
									<div
										class="mt-2 text-xs text-blue-600">
										{{
											new Date(
												log.created_at,
											).toLocaleDateString()
										}}
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<Card>
					<CardHeader>
						<CardTitle>Horse Details</CardTitle>
					</CardHeader>
					<CardContent>
						<form
							@submit.prevent="submit"
							class="space-y-4">
							<div>
								<Label for="name">Horse Name</Label>
								<Input
									id="name"
									v-model="form.name"
									type="text"
									placeholder="Enter horse name"
									:class="{
										'border-red-500':
											form.errors.name,
									}"
									required />
								<p
									v-if="form.errors.name"
									class="mt-1 text-sm text-red-500">
									{{ form.errors.name }}
								</p>
							</div>

							<div v-if="canEditSex">
								<Label for="sex">Sex</Label>
								<Select
									id="sex"
									v-model="form.sex"
									:options="[
										{
											value: 'mare',
											label: 'Mare',
										},
										{
											value: 'stallion',
											label: 'Stallion',
										},
									]" />
								<p
									v-if="form.errors.sex"
									class="mt-1 text-sm text-red-500">
									{{ form.errors.sex }}
								</p>
							</div>
							<div
								v-else
								class="text-sm text-gray-600">
								<strong>Sex:</strong>
								{{ props.horse.sex ?? 'Unset' }}
								(staff only to change)
							</div>

							<div class="grid grid-cols-2 gap-4">
								<div>
									<Label for="age_years"
										>Age (years)</Label
									>
									<Input
										id="age_years"
										v-model.number="
											form.age_years
										"
										type="number"
										min="0"
										max="50"
										placeholder="Years"
										:class="{
											'border-red-500':
												form.errors
													.age_years,
										}"
										required />
									<p
										v-if="form.errors.age_years"
										class="mt-1 text-sm text-red-500">
										{{ form.errors.age_years }}
									</p>
								</div>
								<div>
									<Label for="age_months"
										>Age (months)</Label
									>
									<Input
										id="age_months"
										v-model.number="
											form.age_months
										"
										type="number"
										min="0"
										max="11"
										placeholder="Months"
										:class="{
											'border-red-500':
												form.errors
													.age_months,
										}"
										required />
									<p
										v-if="form.errors.age_months"
										class="mt-1 text-sm text-red-500">
										{{ form.errors.age_months }}
									</p>
								</div>
							</div>

							<div>
								<Label for="geno">Geno String</Label>
								<Input
									id="geno"
									v-model="form.geno"
									type="text"
									placeholder="Enter geno string"
									:class="{
										'border-red-500':
											form.errors.geno,
									}"
									required />
								<p
									v-if="form.errors.geno"
									class="mt-1 text-sm text-red-500">
									{{ form.errors.geno }}
								</p>
							</div>

							<div>
								<Label for="design_link"
									>Design Image (Optional)</Label
								>
								<div class="space-y-2">
									<ImageUpload
										:upload-url="
											route(
												'horses.upload-image',
											)
										"
										accept="image/png,image/jpeg,image/jpg"
										:max-size="maxFileSize"
										drag-drop-text="Drop your horse design image here"
										:file-type-hint="fileTypeHint"
										@success="
											handleImageUploadSuccess
										" />
									<div class="text-sm text-gray-500">
										Or enter a URL manually:
									</div>
									<Input
										id="design_link"
										v-model="form.design_link"
										type="url"
										placeholder="Enter design image URL"
										:class="{
											'border-red-500':
												form.errors
													.design_link,
										}" />
									<p
										v-if="form.errors.design_link"
										class="mt-1 text-sm text-red-500">
										{{ form.errors.design_link }}
									</p>
									<div
										v-if="form.design_link"
										class="mt-2">
										<img
											:src="form.design_link"
											alt="Horse design preview"
											class="h-32 w-full rounded border object-contain" />
									</div>
								</div>
							</div>

							<div>
								<Label for="herd_id"
									>Assign to Herd (Optional)</Label
								>
								<Select
									v-model="form.herd_id"
									:options="[
										{
											value: null,
											label: 'No herd',
										},
										...props.herds.map(
											(herd) => ({
												value: herd.id,
												label: herd.name,
											}),
										),
									]"
									placeholder="Select a herd" />
								<p
									v-if="form.errors.herd_id"
									class="mt-1 text-sm text-red-500">
									{{ form.errors.herd_id }}
								</p>
							</div>

							<div class="flex gap-4 pt-4">
								<Button
									type="submit"
									:disabled="form.processing">
									{{
										form.processing
											? 'Submitting...'
											: 'Submit Edits'
									}}
								</Button>
								<Button
									type="button"
									variant="outline"
									@click="
										$inertia.visit(
											route(
												'horses.show',
												props.horse.id,
											),
										)
									">
									Cancel
								</Button>
							</div>
						</form>
					</CardContent>
				</Card>
			</div>
		</div>
	</AppLayout>
</template>
