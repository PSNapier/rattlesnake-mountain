<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { Copy, Dices } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface HorseRollResult {
	breed: string;
	sex: string;
	age: number;
	benefits: string[];
	detriments: string[];
	social_rank: string;
	health_issues: string;
	phenotype: string;
	face_markings: string;
	leg_markings: { RF: string; LF: string; RB: string; LB: string };
	other_marks: string;
}

interface MissingSexHorse {
	id: number;
	name: string;
	geno: string;
}

interface SanctuarySlot {
	id: number;
	horse_id: number;
	horse_name?: string | null;
	sequence: number;
}

interface GrantableUser {
	id: number;
	name: string;
}

interface Props {
	horsesMissingSex?: MissingSexHorse[];
	sanctuarySlots?: SanctuarySlot[];
	grantableUsers?: GrantableUser[];
}

const props = withDefaults(defineProps<Props>(), {
	horsesMissingSex: () => [],
	sanctuarySlots: () => [],
	grantableUsers: () => [],
});

const page = usePage();
const result = ref<HorseRollResult | null>(null);
const isRolling = ref(false);
const copySuccess = ref(false);

const ageMin = ref(0);
const ageMax = ref(30);
const benefitDoubleUp = ref(5);
const detrimentDoubleUp = ref(2);
const healthyRollMin = ref(57);

const grantForm = useForm({
	breeding_slot_id: null as number | null,
	to_user_id: null as number | null,
	notes: '',
});

watch(
	() => (page.props.flash as { rollResult?: HorseRollResult })?.rollResult,
	(rollResult) => {
		if (rollResult) result.value = rollResult;
	},
	{ immediate: true },
);

const rollHorse = (): void => {
	isRolling.value = true;
	result.value = null;
	router.post(
		route('admin.rollers.horse-randomizer.roll'),
		{
			age_min: ageMin.value,
			age_max: ageMax.value,
			benefit_double_up_threshold: benefitDoubleUp.value,
			detriment_double_up_threshold: detrimentDoubleUp.value,
			healthy_roll_min: healthyRollMin.value,
		},
		{
			preserveScroll: true,
			onFinish: () => {
				isRolling.value = false;
			},
		},
	);
};

const formatForCopy = (): string => {
	if (!result.value) return '';

	const r = result.value;
	const lines = [
		`Breed,${r.breed}`,
		`Sex,${r.sex}`,
		`Age,${r.age}`,
		'',
		`Benefits,${r.benefits.join(', ')}`,
		`Detriments,${r.detriments.join(', ')}`,
		`Social Rank,${r.social_rank}`,
		`Health Issues,${r.health_issues}`,
		'',
		`Phenotype,${r.phenotype || '—'}`,
		'',
		`Face Markings,${r.face_markings}`,
		`RF Leg Markings,${r.leg_markings.RF}`,
		`LF Leg Markings,${r.leg_markings.LF}`,
		`RB Leg Markings,${r.leg_markings.RB}`,
		`LB Leg Markings,${r.leg_markings.LB}`,
		`Other Marks,${r.other_marks}`,
	];

	return lines.join('\n');
};

const copyToClipboard = async (): Promise<void> => {
	const text = formatForCopy();
	if (!text) return;

	try {
		await navigator.clipboard.writeText(text);
		copySuccess.value = true;
		setTimeout(() => {
			copySuccess.value = false;
		}, 2000);
	} catch {
		// ignore
	}
};

const setSex = (horseId: number, sex: string): void => {
	router.put(
		route('admin.horses.sex.update', horseId),
		{ sex },
		{ preserveScroll: true },
	);
};

const grantSlot = (): void => {
	grantForm.post(route('admin.breeding-slots.grant'), {
		preserveScroll: true,
	});
};
</script>

<template>
	<div class="space-y-6">
		<Card>
			<CardHeader>
				<CardTitle>Horse Randomizer</CardTitle>
				<p class="text-cape-palliser-600 mt-1 text-sm">
					Randomize horses for encounters, NPCs, and other
					events.
				</p>
			</CardHeader>
			<CardContent class="space-y-4">
				<div
					class="border-shakespeare-200 bg-shakespeare-50/30 rounded-md border p-4">
					<p
						class="text-cape-palliser-700 mb-3 text-sm font-medium">
						Settings
					</p>
					<div
						class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
						<div>
							<Label for="age-min">Age min</Label>
							<Input
								id="age-min"
								v-model.number="ageMin"
								type="number"
								min="0"
								class="mt-1" />
						</div>
						<div>
							<Label for="age-max">Age max</Label>
							<Input
								id="age-max"
								v-model.number="ageMax"
								type="number"
								min="0"
								class="mt-1" />
						</div>
						<div>
							<Label for="benefit-double"
								>Benefit double-up (≤)</Label
							>
							<Input
								id="benefit-double"
								v-model.number="benefitDoubleUp"
								type="number"
								min="1"
								max="6"
								class="mt-1" />
							<p
								class="text-cape-palliser-500 mt-0.5 text-xs">
								D6 roll for second benefit
							</p>
						</div>
						<div>
							<Label for="detriment-double"
								>Detriment double-up (≤)</Label
							>
							<Input
								id="detriment-double"
								v-model.number="detrimentDoubleUp"
								type="number"
								min="1"
								max="6"
								class="mt-1" />
							<p
								class="text-cape-palliser-500 mt-0.5 text-xs">
								D6 roll for second detriment
							</p>
						</div>
						<div>
							<Label for="healthy-roll"
								>Healthy threshold (≥)</Label
							>
							<Input
								id="healthy-roll"
								v-model.number="healthyRollMin"
								type="number"
								min="1"
								max="100"
								class="mt-1" />
							<p
								class="text-cape-palliser-500 mt-0.5 text-xs">
								D100 roll for healthy
							</p>
						</div>
					</div>
				</div>

				<div class="flex flex-wrap items-center gap-2">
					<Button
						:disabled="isRolling"
						@click="rollHorse">
						<Dices class="mr-2 size-4" />
						{{ isRolling ? 'Rolling…' : 'Roll' }}
					</Button>
					<Button
						v-if="result"
						variant="outline"
						size="sm"
						@click="copyToClipboard">
						<Copy class="mr-2 size-4" />
						{{ copySuccess ? 'Copied!' : 'Copy' }}
					</Button>
				</div>

				<div
					v-if="result"
					class="border-shakespeare-200 bg-shakespeare-50/50 columns-2 gap-8 space-y-1 rounded-md border p-4 font-mono text-sm">
					<div>
						<span class="text-cape-palliser-600">Breed:</span>
						{{ result.breed }}
					</div>
					<div>
						<span class="text-cape-palliser-600">Sex:</span>
						{{ result.sex }}
					</div>
					<div>
						<span class="text-cape-palliser-600">Age:</span>
						{{ result.age }}
					</div>
					<div>
						<span class="text-cape-palliser-600"
							>Social Rank:</span
						>
						{{ result.social_rank }}
					</div>
					<div>
						<span class="text-cape-palliser-600"
							>Benefits:</span
						>
						{{ result.benefits.join(', ') || '—' }}
					</div>
					<div>
						<span class="text-cape-palliser-600"
							>Detriments:</span
						>
						{{ result.detriments.join(', ') || '—' }}
					</div>
					<div>
						<span class="text-cape-palliser-600"
							>Health:</span
						>
						{{ result.health_issues }}
					</div>
					<div>
						<span class="text-cape-palliser-600">Face:</span>
						{{ result.face_markings }}
					</div>
					<div>
						<span class="text-cape-palliser-600"
							>RF Leg:</span
						>
						{{ result.leg_markings.RF }}
					</div>
					<div>
						<span class="text-cape-palliser-600"
							>LF Leg:</span
						>
						{{ result.leg_markings.LF }}
					</div>
					<div>
						<span class="text-cape-palliser-600"
							>RB Leg:</span
						>
						{{ result.leg_markings.RB }}
					</div>
					<div>
						<span class="text-cape-palliser-600"
							>LB Leg:</span
						>
						{{ result.leg_markings.LB }}
					</div>
					<div>
						<span class="text-cape-palliser-600">Other:</span>
						{{ result.other_marks }}
					</div>
					<div v-if="result.phenotype">
						<span class="text-cape-palliser-600"
							>Phenotype:</span
						>
						{{ result.phenotype }}
					</div>
				</div>
			</CardContent>
		</Card>

		<Card>
			<CardHeader>
				<CardTitle>Story Progression</CardTitle>
			</CardHeader>
			<CardContent>
				<p class="text-cape-palliser-600 text-sm">
					Manage story progression rolls, checkpoints, and travel
					events.
				</p>
				<p class="text-cape-palliser-500 mt-2 text-xs">
					Placeholder – coming soon
				</p>
			</CardContent>
		</Card>

		<Card>
			<CardHeader>
				<CardTitle>Breeding Tools</CardTitle>
				<p class="text-cape-palliser-600 mt-1 text-sm">
					Backfill sex and grant Sanctuary slots. Pending
					breeding requests live under Submissions.
				</p>
			</CardHeader>
			<CardContent class="space-y-6">
				<div class="space-y-3">
					<p class="text-cape-palliser-700 text-sm font-medium">
						Horses missing sex
					</p>
					<div
						v-if="!props.horsesMissingSex?.length"
						class="text-cape-palliser-500 text-sm">
						No public horses missing sex.
					</div>
					<div
						v-for="horse in props.horsesMissingSex || []"
						:key="horse.id"
						class="flex flex-wrap items-center justify-between gap-2 rounded border p-3 text-sm">
						<div>
							<p class="font-medium">{{ horse.name }}</p>
							<p class="font-mono text-xs">
								{{ horse.geno }}
							</p>
						</div>
						<div class="flex gap-2">
							<Button
								size="sm"
								variant="outline"
								@click="setSex(horse.id, 'mare')">
								Mare
							</Button>
							<Button
								size="sm"
								variant="outline"
								@click="setSex(horse.id, 'stallion')">
								Stallion
							</Button>
						</div>
					</div>
				</div>

				<div class="space-y-3 border-t pt-4">
					<p class="text-cape-palliser-700 text-sm font-medium">
						Grant Sanctuary slot
					</p>
					<div class="grid gap-3 md:grid-cols-3">
						<div>
							<Label>Slot</Label>
							<Select
								v-model="grantForm.breeding_slot_id"
								:options="[
									{
										value: null,
										label: 'Select slot',
									},
									...(
										props.sanctuarySlots || []
									).map((slot) => ({
										value: slot.id,
										label: `${slot.horse_name} #${slot.sequence}`,
									})),
								]" />
						</div>
						<div>
							<Label>Recipient</Label>
							<Select
								v-model="grantForm.to_user_id"
								:options="[
									{
										value: null,
										label: 'Select user',
									},
									...(
										props.grantableUsers || []
									).map((user) => ({
										value: user.id,
										label: user.name,
									})),
								]" />
						</div>
						<div class="flex items-end">
							<Button
								:disabled="grantForm.processing"
								@click="grantSlot">
								Grant
							</Button>
						</div>
					</div>
					<p
						v-if="grantForm.errors.breeding_slot_id"
						class="text-sm text-red-500">
						{{ grantForm.errors.breeding_slot_id }}
					</p>
				</div>
			</CardContent>
		</Card>
	</div>
</template>
