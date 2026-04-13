<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateLifecycleSettingsRequest;
use App\Models\LifecycleSetting;
use Illuminate\Http\RedirectResponse;

class LifecycleController extends Controller
{
    public function updateLifecycle(UpdateLifecycleSettingsRequest $request): RedirectResponse
    {
        $settings = LifecycleSetting::firstOrFail();
        $settings->update($request->validated());

        return redirect()->back()->with('success', 'Lifecycle settings saved.');
    }
}
