<?php

namespace App\Services;

class HorseRandomizerService
{
    /**
     * @param  array{age_min?: int, age_max?: int, benefit_double_up_threshold?: int, detriment_double_up_threshold?: int, healthy_roll_min?: int}  $options
     * @return array{breed: string, sex: string, age: int, benefits: string[], detriments: string[], social_rank: string, health_issues: string, phenotype: string, face_markings: string, leg_markings: array{RF: string, LF: string, RB: string, LB: string}, other_marks: string}
     */
    public function rollHorse(array $options = []): array
    {
        $breeds = config('horse-randomizer.breed-age-rank.breeds', ['Purebred']);
        $ageConfig = config('horse-randomizer.breed-age-rank.age', ['min' => 0, 'max' => 30]);
        $herdRanks = config('horse-randomizer.breed-age-rank.herd_ranks', []);
        $benefitThreshold = $options['benefit_double_up_threshold'] ?? config('horse-randomizer.breed-age-rank.double_up.benefit_threshold', 5);
        $detrimentThreshold = $options['detriment_double_up_threshold'] ?? config('horse-randomizer.breed-age-rank.double_up.detriment_threshold', 2);
        $ageMin = $options['age_min'] ?? $ageConfig['min'];
        $ageMax = $options['age_max'] ?? $ageConfig['max'];
        $healthyRollMin = $options['healthy_roll_min'] ?? config('horse-randomizer.health.healthy_roll_min', 57);

        $benefits = $this->rollBenefits($benefitThreshold);
        $detriments = $this->rollDetriments($detrimentThreshold);

        return [
            'breed' => $this->pick($breeds),
            'sex' => $this->pick(['Mare', 'Stallion']),
            'age' => random_int(max(0, $ageMin), max($ageMin, $ageMax)),
            'benefits' => $benefits,
            'detriments' => $detriments,
            'social_rank' => $this->pick($herdRanks),
            'health_issues' => $this->rollHealth($healthyRollMin),
            'phenotype' => $this->rollPhenotype(),
            'face_markings' => $this->rollFaceMarking(),
            'leg_markings' => $this->rollLegMarkings(),
            'other_marks' => $this->rollOtherMarking(),
        ];
    }

    /**
     * @return string[]
     */
    private function rollBenefits(int $doubleUpThreshold): array
    {
        $benefits = [$this->rollTrait('horse-randomizer.benefits.by_roll')];
        if ($this->rollD6() <= $doubleUpThreshold) {
            $benefits[] = $this->rollTrait('horse-randomizer.benefits.by_roll');
        }

        return array_values(array_unique(array_filter($benefits)));
    }

    /**
     * @return string[]
     */
    private function rollDetriments(int $doubleUpThreshold): array
    {
        $detriments = [$this->rollTrait('horse-randomizer.detriments.by_roll')];
        if ($this->rollD6() <= $doubleUpThreshold) {
            $detriments[] = $this->rollTrait('horse-randomizer.detriments.by_roll');
        }

        return array_values(array_unique(array_filter($detriments)));
    }

    private function rollTrait(string $configKey): string
    {
        $byRoll = config($configKey, []);
        $roll = random_int(1, 100);
        $trait = $byRoll[$roll] ?? null;

        while ($trait === null && $roll > 0) {
            $roll--;
            $trait = $byRoll[$roll] ?? null;
        }

        return $trait ?? 'NONE';
    }

    private function rollHealth(int $healthyMin = 57): string
    {
        $byRoll = config('horse-randomizer.health.by_roll', []);
        $roll = random_int(1, 100);

        if ($roll >= $healthyMin) {
            return 'Healthy';
        }

        $trait = $byRoll[$roll] ?? null;
        while ($trait === null && $roll > 0) {
            $roll--;
            $trait = $byRoll[$roll] ?? null;
        }

        return $trait ?? 'Healthy';
    }

    private function rollFaceMarking(): string
    {
        $options = config('horse-randomizer.markings.face', []);

        return $this->pick($options) ?? 'None';
    }

    /**
     * @return array{RF: string, LF: string, RB: string, LB: string}
     */
    private function rollLegMarkings(): array
    {
        $options = config('horse-randomizer.markings.leg', []);

        return [
            'RF' => $this->formatLegMarking($options),
            'LF' => $this->formatLegMarking($options),
            'RB' => $this->formatLegMarking($options),
            'LB' => $this->formatLegMarking($options),
        ];
    }

    private function formatLegMarking(array $options): string
    {
        $primary = $this->pick($options) ?? 'Regular';
        $secondary = $this->pick($options) ?? 'None';
        if ($secondary === 'None' || $secondary === $primary) {
            return $primary;
        }

        return "{$primary}, {$secondary}";
    }

    private function rollOtherMarking(): string
    {
        $options = config('horse-randomizer.markings.other', []);

        return $this->pick($options) ?? 'None';
    }

    /**
     * @param  array<mixed>  $items
     */
    private function pick(array $items): mixed
    {
        if (empty($items)) {
            return null;
        }

        return $items[array_rand($items)];
    }

    private function rollD6(): int
    {
        return random_int(1, 6);
    }

    private function rollPhenotype(): string
    {
        $modifiers = config('horse-randomizer.genetics.modifiers', []);
        $baseCoat = config('horse-randomizer.genetics.base_coat', []);

        $phenotypeModifiers = [];
        $patternModifiers = ['Tobiano', 'Overo', 'Splash', 'Rabicano', 'KIT'];

        foreach ($modifiers as $name => $config) {
            $rollMax = $config['roll_max'] ?? 0;
            if ($rollMax > 0 && random_int(1, 100) <= $rollMax) {
                $displayName = str_replace('_', ' ', $name);
                if ($name === 'KIT') {
                    $phenotypeModifiers[] = 'Dominant White on';
                } elseif (in_array($name, $patternModifiers, true)) {
                    $phenotypeModifiers[] = $displayName;
                }
            }
        }

        $baseRoll = random_int(0, min(100, count($baseCoat) - 1));
        $basePhenotype = $baseCoat[$baseRoll] ?? 'Chestnut';

        return trim(implode(' ', array_merge($phenotypeModifiers, [$basePhenotype])));
    }
}
