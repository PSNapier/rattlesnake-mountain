<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { ref } from 'vue';

interface LifecycleSettings {
	horse_auto_age_next_update: string;
	horse_auto_age_frequency_unit: 'weeks' | 'months';
	horse_auto_age_frequency_value: number;
	horse_auto_age_game_years: number;
	horse_auto_health_roll_min: number;
	horse_auto_health_roll_max: number;
}

interface Props {
	settings?: LifecycleSettings;
}

const props = defineProps<Props>();

// Default next update to 4 months from today
const getDefaultNextUpdate = (): string => {
	const date = new Date();
	date.setMonth(date.getMonth() + 4);
	return date.toISOString().split('T')[0];
};

const form = ref<LifecycleSettings>({
	horse_auto_age_next_update:
		props.settings?.horse_auto_age_next_update ?? getDefaultNextUpdate(),
	horse_auto_age_frequency_unit:
		props.settings?.horse_auto_age_frequency_unit ?? 'months',
	horse_auto_age_frequency_value:
		props.settings?.horse_auto_age_frequency_value ?? 4,
	horse_auto_age_game_years: props.settings?.horse_auto_age_game_years ?? 1,
	horse_auto_health_roll_min: props.settings?.horse_auto_health_roll_min ?? 0,
	horse_auto_health_roll_max: props.settings?.horse_auto_health_roll_max ?? 100,
});

const handleSave = (): void => {
	// TODO: Wire up to backend route when ready
	// router.put(route('admin.lifecycle.update'), form.value, {
	// 	onSuccess: () => {
	// 		// Show success message
	// 	},
	// 	onError: () => {
	// 		// Show error message
	// 	},
	// });
	console.log('Lifecycle settings to save:', form.value);
};
</script>

<template>
	<div class="space-y-6">
		<!-- Horse Auto Age Section -->
		<Card>
			<CardHeader>
				<CardTitle>Horse Auto Age</CardTitle>
			</CardHeader>
			<CardContent>
				<div class="space-y-4">
					<div>
						<Label for="next-update">Next Update Date</Label>
						<Input
							id="next-update"
							v-model="form.horse_auto_age_next_update"
							type="date"
							class="mt-1 w-full" />
						<p class="text-cape-palliser-600 mt-1 text-xs">
							The date when horses will next age automatically
						</p>
					</div>

					<div class="grid grid-cols-2 gap-4">
						<div>
							<Label for="frequency-value">Frequency</Label>
							<Input
								id="frequency-value"
								v-model.number="form.horse_auto_age_frequency_value"
								type="number"
								min="1"
								:max="form.horse_auto_age_frequency_unit === 'weeks' ? 52 : 12"
								class="mt-1 w-full" />
							<p class="text-cape-palliser-600 mt-1 text-xs">
								How often to repeat the update
							</p>
						</div>
						<div>
							<Label for="frequency-unit">Frequency Unit</Label>
							<Select
								id="frequency-unit"
								v-model="form.horse_auto_age_frequency_unit"
								:options="[
									{ value: 'weeks', label: 'Weeks' },
									{ value: 'months', label: 'Months' },
								]"
								placeholder="Select unit"
								class="mt-1 w-full" />
							<p class="text-cape-palliser-600 mt-1 text-xs">
								Unit for frequency
							</p>
						</div>
					</div>

					<div
						v-if="form.horse_auto_age_frequency_unit === 'months'"
						class="rounded-md border border-blue-200 bg-blue-50 p-3">
						<p class="text-blue-800 text-sm">
							<strong>Month-based updates:</strong> When using months, all
							subsequent updates will occur on the same day number as the next
							update date. For example, if the next update is set to the 1st,
							all future updates will be on the 1st of their respective months.
						</p>
					</div>

					<div>
						<Label for="game-years">In-Game Age (years)</Label>
						<Input
							id="game-years"
							v-model.number="form.horse_auto_age_game_years"
							type="number"
							min="0.25"
							step="0.25"
							max="10"
							class="mt-1 w-full" />
						<p class="text-cape-palliser-600 mt-1 text-xs">
							How much horses age in-game per cycle
						</p>
					</div>

					<div class="flex justify-end">
						<Button @click="handleSave">Save Changes</Button>
					</div>
				</div>
			</CardContent>
		</Card>

		<!-- Horse Auto Health Rolls Section -->
		<Card>
			<CardHeader>
				<CardTitle>Horse Auto Health Rolls</CardTitle>
			</CardHeader>
			<CardContent>
				<div class="space-y-4">
					<div class="grid grid-cols-2 gap-4">
						<div>
							<Label for="health-roll-min">Minimum Roll</Label>
							<Input
								id="health-roll-min"
								v-model.number="form.horse_auto_health_roll_min"
								type="number"
								min="0"
								max="100"
								class="mt-1 w-full" />
							<p class="text-cape-palliser-600 mt-1 text-xs">
								Minimum health roll value (0-100)
							</p>
						</div>
						<div>
							<Label for="health-roll-max">Maximum Roll</Label>
							<Input
								id="health-roll-max"
								v-model.number="form.horse_auto_health_roll_max"
								type="number"
								min="0"
								max="100"
								class="mt-1 w-full" />
							<p class="text-cape-palliser-600 mt-1 text-xs">
								Maximum health roll value (0-100)
							</p>
						</div>
					</div>
					<div
						v-if="form.horse_auto_health_roll_min > form.horse_auto_health_roll_max"
						class="text-red-600 text-sm">
						Minimum cannot be greater than maximum
					</div>
					<div class="flex justify-end">
						<Button @click="handleSave">Save Changes</Button>
					</div>
				</div>
			</CardContent>
		</Card>

		<!-- NPC Horse Deaths Section -->
		<Card>
			<CardHeader>
				<CardTitle>NPC Horse Deaths</CardTitle>
			</CardHeader>
			<CardContent>
				<div class="text-cape-palliser-600 py-4 text-center">
					Placeholder - Coming soon
				</div>
			</CardContent>
		</Card>
	</div>
</template>
