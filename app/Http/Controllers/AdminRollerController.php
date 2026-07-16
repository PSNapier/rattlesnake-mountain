<?php

namespace App\Http\Controllers;

use App\Services\HorseRandomizerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminRollerController extends Controller
{
    public function rollHorse(Request $request, HorseRandomizerService $service): RedirectResponse
    {
        $options = [];
        if ($request->has('age_min')) {
            $options['age_min'] = (int) $request->input('age_min');
        }
        if ($request->has('age_max')) {
            $options['age_max'] = (int) $request->input('age_max');
        }
        if ($request->has('benefit_double_up_threshold')) {
            $options['benefit_double_up_threshold'] = (int) $request->input('benefit_double_up_threshold');
        }
        if ($request->has('detriment_double_up_threshold')) {
            $options['detriment_double_up_threshold'] = (int) $request->input('detriment_double_up_threshold');
        }
        if ($request->has('healthy_roll_min')) {
            $options['healthy_roll_min'] = (int) $request->input('healthy_roll_min');
        }

        return redirect()->back()->with('rollResult', $service->rollHorse($options));
    }
}
