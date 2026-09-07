<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface LifecycleSettings {
	horse_auto_age_next_update: string;
	horse_auto_age_frequency_unit: 'weeks' | 'months';
	horse_auto_age_frequency_value: number;
	horse_auto_age_game_years: number;
	horse_auto_health_roll_min: number;
	horse_auto_health_roll_max: number;
	npc_death_age_threshold: number;
	npc_death_base_percent: number;
	npc_death_double_every_years: number;
	npc_death_cap_percent: number;
}

interface DeathProposal {
	id: number;
	horse_id: number;
	horse_name: string | null;
	age_months_at_roll: number;
	formatted_age: string | null;
	chance_percent: number;
	rolled_at: string | null;
}

interface Props {
	settings?: LifecycleSettings;
	proposals?: DeathProposal[];
}

const props = withDefaults(defineProps<Props>(), {
	proposals: () => [],
});

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
	horse_auto_health_roll_min:
		props.settings?.horse_auto_health_roll_min ?? 0,
	horse_auto_health_roll_max:
		props.settings?.horse_auto_health_roll_max ?? 100,
	npc_death_age_threshold: props.settings?.npc_death_age_threshold ?? 15,
	npc_death_base_percent: props.settings?.npc_death_base_percent ?? 2,
	npc_death_double_every_years:
		props.settings?.npc_death_double_every_years ?? 2,
	npc_death_cap_percent: props.settings?.npc_death_cap_percent ?? 95,
});

const handleSave = (): void => {
	router.put(route('admin.lifecycle.update'), form.value, {
		preserveScroll: true,
	});
};

const handlePreview = (): void => {
	router.post(
		route('admin.lifecycle.preview'),
		{},
		{ preserveScroll: true },
	);
};

const handleRunNow = (): void => {
	if (
		!confirm(
			'Run lifecycle aging now? This will age horses and may create death proposals.',
		)
	) {
		return;
	}
	router.post(
		route('admin.lifecycle.run-now'),
		{},
		{ preserveScroll: true },
	);
};

const confirmProposal = (id: number): void => {
	router.post(
		route('admin.lifecycle.proposals.confirm', id),
		{},
		{ preserveScroll: true },
	);
};

const rejectProposal = (id: number): void => {
	router.post(
		route('admin.lifecycle.proposals.reject', id),
		{},
		{ preserveScroll: true },
	);
};
</script>

<template>
	<div class="space-y-6">
		<Card>
			<CardHeader>
				<CardTitle>Run Lifecycle</CardTitle>
			</CardHeader>
			<CardContent>
				<div class="flex flex-wrap gap-3">
					<Button
						type="button"
						variant="outline"
						@click="handlePreview"
						>Preview</Button
					>
					<Button
						type="button"
						@click="handleRunNow"
						>Run now</Button
					>
				</div>
				<p class="text-cape-palliser-600 mt-2 text-xs">
					Preview simulates aging without writing. Run now ages
					public living horses and advances the next update date.
				</p>
			</CardContent>
		</Card>

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
							The date when horses will next age
							automatically
						</p>
					</div>

					<div class="grid grid-cols-2 gap-4">
						<div>
							<Label for="frequency-value"
								>Frequency</Label
							>
							<Input
								id="frequency-value"
								v-model.number="
									form.horse_auto_age_frequency_value
								"
								type="number"
								min="1"
								:max="
									form.horse_auto_age_frequency_unit ===
									'weeks'
										? 52
										: 12
								"
								class="mt-1 w-full" />
							<p
								class="text-cape-palliser-600 mt-1 text-xs">
								How often to repeat the update
							</p>
						</div>
						<div>
							<Label for="frequency-unit"
								>Frequency Unit</Label
							>
							<Select
								id="frequency-unit"
								v-model="
									form.horse_auto_age_frequency_unit
								"
								:options="[
									{ value: 'weeks', label: 'Weeks' },
									{
										value: 'months',
										label: 'Months',
									},
								]"
								placeholder="Select unit"
								class="mt-1 w-full" />
							<p
								class="text-cape-palliser-600 mt-1 text-xs">
								Unit for frequency
							</p>
						</div>
					</div>

					<div
						v-if="
							form.horse_auto_age_frequency_unit ===
							'months'
						"
						class="rounded-md border border-blue-200 bg-blue-50 p-3">
						<p class="text-sm text-blue-800">
							<strong>Month-based updates:</strong> When
							using months, all subsequent updates will
							occur on the same day number as the next
							update date. For example, if the next update
							is set to the 1st, all future updates will be
							on the 1st of their respective months.
						</p>
					</div>

					<div>
						<Label for="game-years"
							>In-Game Age (years)</Label
						>
						<Input
							id="game-years"
							v-model.number="
								form.horse_auto_age_game_years
							"
							type="number"
							min="0.25"
							step="0.25"
							max="10"
							class="mt-1 w-full" />
						<p class="text-cape-palliser-600 mt-1 text-xs">
							How much horses age in-game per cycle (stored
							as months)
						</p>
					</div>

					<div class="flex justify-end">
						<Button @click="handleSave">Save Changes</Button>
					</div>
				</div>
			</CardContent>
		</Card>

		<Card>
			<CardHeader>
				<CardTitle>Horse Auto Health Rolls</CardTitle>
			</CardHeader>
			<CardContent>
				<div class="space-y-4">
					<p
						class="rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
						Health rolls are configured here but not applied
						yet. Application is deferred until injury /
						story-progression health exists.
					</p>
					<div class="grid grid-cols-2 gap-4">
						<div>
							<Label for="health-roll-min"
								>Minimum Roll</Label
							>
							<Input
								id="health-roll-min"
								v-model.number="
									form.horse_auto_health_roll_min
								"
								type="number"
								min="0"
								max="100"
								class="mt-1 w-full" />
							<p
								class="text-cape-palliser-600 mt-1 text-xs">
								Minimum health roll value (0-100)
							</p>
						</div>
						<div>
							<Label for="health-roll-max"
								>Maximum Roll</Label
							>
							<Input
								id="health-roll-max"
								v-model.number="
									form.horse_auto_health_roll_max
								"
								type="number"
								min="0"
								max="100"
								class="mt-1 w-full" />
							<p
								class="text-cape-palliser-600 mt-1 text-xs">
								Maximum health roll value (0-100)
							</p>
						</div>
					</div>
					<div
						v-if="
							form.horse_auto_health_roll_min >
							form.horse_auto_health_roll_max
						"
						class="text-sm text-red-600">
						Minimum cannot be greater than maximum
					</div>
					<div class="flex justify-end">
						<Button @click="handleSave">Save Changes</Button>
					</div>
				</div>
			</CardContent>
		</Card>

		<Card>
			<CardHeader>
				<CardTitle>NPC Death Roll Settings</CardTitle>
			</CardHeader>
			<CardContent>
				<div class="space-y-4">
					<div class="grid grid-cols-2 gap-4">
						<div>
							<Label for="death-threshold"
								>Age threshold (years)</Label
							>
							<Input
								id="death-threshold"
								v-model.number="
									form.npc_death_age_threshold
								"
								type="number"
								min="1"
								max="40"
								class="mt-1 w-full" />
							<p
								class="text-cape-palliser-600 mt-1 text-xs">
								Death rolls start after this many
								completed years
							</p>
						</div>
						<div>
							<Label for="death-base"
								>Base chance (%)</Label
							>
							<Input
								id="death-base"
								v-model.number="
									form.npc_death_base_percent
								"
								type="number"
								min="1"
								max="100"
								class="mt-1 w-full" />
						</div>
						<div>
							<Label for="death-double"
								>Double every (years)</Label
							>
							<Input
								id="death-double"
								v-model.number="
									form.npc_death_double_every_years
								"
								type="number"
								min="1"
								max="10"
								class="mt-1 w-full" />
						</div>
						<div>
							<Label for="death-cap">Cap chance (%)</Label>
							<Input
								id="death-cap"
								v-model.number="
									form.npc_death_cap_percent
								"
								type="number"
								min="1"
								max="100"
								class="mt-1 w-full" />
						</div>
					</div>
					<div class="flex justify-end">
						<Button @click="handleSave">Save Changes</Button>
					</div>
				</div>
			</CardContent>
		</Card>

		<Card>
			<CardHeader>
				<CardTitle>NPC Horse Deaths</CardTitle>
			</CardHeader>
			<CardContent>
				<div
					v-if="props.proposals.length === 0"
					class="text-cape-palliser-600 py-4 text-center">
					No pending death proposals
				</div>
				<ul
					v-else
					class="divide-cape-palliser-200 divide-y">
					<li
						v-for="proposal in props.proposals"
						:key="proposal.id"
						class="flex flex-wrap items-center justify-between gap-3 py-3">
						<div>
							<p class="font-medium">
								{{
									proposal.horse_name ??
									`Horse #${proposal.horse_id}`
								}}
							</p>
							<p class="text-cape-palliser-600 text-sm">
								Age {{ proposal.formatted_age }} ·
								{{ proposal.chance_percent }}% chance
							</p>
						</div>
						<div class="flex gap-2">
							<Button
								type="button"
								variant="outline"
								@click="rejectProposal(proposal.id)">
								Reject
							</Button>
							<Button
								type="button"
								@click="confirmProposal(proposal.id)">
								Confirm death
							</Button>
						</div>
					</li>
				</ul>
			</CardContent>
		</Card>
	</div>
</template>
