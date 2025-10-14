<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';

interface Herd {
	id: number;
	name: string;
}

interface Horse {
	id: number;
	name: string;
	age: number;
	design_link?: string;
	geno: string;
	herd_id?: number;
	bloodline: number[];
	progeny: number[];
	stats: any[];
	inventory: any[];
	equipment: any[];
}

interface Props {
	horse: Horse;
	herds: Herd[];
}

const props = defineProps<Props>();

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
	age: props.horse.age,
	design_link: props.horse.design_link || '',
	geno: props.horse.geno,
	herd_id: props.horse.herd_id,
	bloodline: props.horse.bloodline,
	progeny: props.horse.progeny,
	stats: props.horse.stats,
	inventory: props.horse.inventory,
	equipment: props.horse.equipment,
});

const submit = () => {
	form.put(route('horses.update', props.horse.id));
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

							<div>
								<Label for="age">Age</Label>
								<Input
									id="age"
									v-model.number="form.age"
									type="number"
									min="0"
									max="50"
									placeholder="Enter horse age"
									:class="{
										'border-red-500':
											form.errors.age,
									}"
									required />
								<p
									v-if="form.errors.age"
									class="mt-1 text-sm text-red-500">
									{{ form.errors.age }}
								</p>
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
									>Design Link (Optional)</Label
								>
								<Input
									id="design_link"
									v-model="form.design_link"
									type="url"
									placeholder="Enter design image URL"
									:class="{
										'border-red-500':
											form.errors.design_link,
									}" />
								<p
									v-if="form.errors.design_link"
									class="mt-1 text-sm text-red-500">
									{{ form.errors.design_link }}
								</p>
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
											? 'Updating...'
											: 'Update Horse'
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
