<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
	{
		title: 'Herds',
		href: '/herds',
	},
	{
		title: 'Create Herd',
		href: '/herds/create',
	},
];

const form = useForm({
	name: '',
	herd_leader_id: null,
	herd_members: [],
	inventory: [],
	equipment: [],
});

const submit = () => {
	form.post(route('herds.store'));
};
</script>

<template>
	<Head title="Create New Herd" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="mx-auto max-w-2xl">
			<div class="space-y-6">
				<div>
					<h1 class="text-3xl font-bold">Create New Herd</h1>
					<p class="text-gray-600">
						Set up a new herd for your horses.
					</p>
				</div>

				<Card>
					<CardHeader>
						<CardTitle>Herd Details</CardTitle>
					</CardHeader>
					<CardContent>
						<form
							@submit.prevent="submit"
							class="space-y-4">
							<div>
								<Label for="name">Herd Name</Label>
								<Input
									id="name"
									v-model="form.name"
									type="text"
									placeholder="Enter herd name"
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
								<Label for="herd_leader_id"
									>Herd Leader (Optional)</Label
								>
								<Input
									id="herd_leader_id"
									v-model="form.herd_leader_id"
									type="number"
									placeholder="Horse ID"
									:class="{
										'border-red-500':
											form.errors
												.herd_leader_id,
									}" />
								<p
									v-if="form.errors.herd_leader_id"
									class="mt-1 text-sm text-red-500">
									{{ form.errors.herd_leader_id }}
								</p>
							</div>

							<div class="flex gap-4 pt-4">
								<Button
									type="submit"
									:disabled="form.processing">
									{{
										form.processing
											? 'Creating...'
											: 'Create Herd'
									}}
								</Button>
								<Button
									type="button"
									variant="outline"
									@click="
										$inertia.visit(
											route('herds.index'),
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
