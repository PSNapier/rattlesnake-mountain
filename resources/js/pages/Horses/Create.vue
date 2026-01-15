<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import ImageUpload from '@/components/ImageUpload.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Herd {
	id: number;
	name: string;
}

interface Props {
	herds: Herd[];
}

const props = defineProps<Props>();

const page = usePage<SharedData>();
const isAdmin = computed(() => page.props.auth.user?.role === 'admin');
const maxFileSize = computed(() => (isAdmin.value ? 10 : 2) * 1024 * 1024);
const fileTypeHint = computed(() =>
	isAdmin.value
		? 'PNG, JPG, or JPEG files only, max 10MB'
		: 'PNG, JPG, or JPEG files only, max 2MB',
);

const breadcrumbs: BreadcrumbItem[] = [
	{
		title: 'Horses',
		href: '/horses',
	},
	{
		title: 'Add New Horse',
		href: '/horses/create',
	},
];

const form = useForm({
	name: '',
	age: 0,
	design_link: '',
	geno: '',
	herd_id: null,
	bloodline: [],
	progeny: [],
	stats: [],
	inventory: [],
	equipment: [],
});

const submit = () => {
	form.post(route('horses.store'));
};

const handleImageUploadSuccess = (data: { url: string }) => {
	form.design_link = data.url;
};
</script>

<template>
	<Head title="Add New Horse" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="mx-auto max-w-2xl">
			<div class="space-y-6">
				<div>
					<h1 class="text-3xl font-bold">Add New Horse</h1>
					<p class="text-gray-600">
						Register a new horse to your collection.
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
									>Design Image (Optional)</Label
								>
								<div class="space-y-2">
									<ImageUpload
										:upload-url="route('horses.upload-image')"
										accept="image/png,image/jpeg,image/jpg"
										:max-size="maxFileSize"
										drag-drop-text="Drop your horse design image here"
										:file-type-hint="fileTypeHint"
										@success="handleImageUploadSuccess" />
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
												form.errors.design_link,
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
											class="h-32 w-full rounded object-contain border" />
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
											? 'Creating...'
											: 'Add Horse'
									}}
								</Button>
								<Button
									type="button"
									variant="outline"
									@click="router.visit(route('horses.index'))">
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
