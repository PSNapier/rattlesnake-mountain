<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NpcDeathProposalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateLifecycleSettingsRequest;
use App\Models\LifecycleSetting;
use App\Models\NpcDeathProposal;
use App\Services\LifecycleAgingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class LifecycleController extends Controller
{
    public function updateLifecycle(UpdateLifecycleSettingsRequest $request): RedirectResponse
    {
        $settings = LifecycleSetting::firstOrFail();
        $settings->update($request->validated());

        return redirect()->back()->with('success', 'Lifecycle settings saved.');
    }

    public function preview(LifecycleAgingService $service): RedirectResponse
    {
        try {
            $result = $service->run(mode: 'preview', dryRun: true, force: true);
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', sprintf(
            'Preview: would age %d horses, propose %d deaths, %d survived rolls.',
            $result['aged_count'],
            $result['proposed_count'],
            $result['survived_count'],
        ));
    }

    public function runNow(LifecycleAgingService $service): RedirectResponse
    {
        try {
            $result = $service->run(mode: 'run_now', dryRun: false, force: true);
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', sprintf(
            'Lifecycle run complete: aged %d, proposed %d, survived %d. Next update %s.',
            $result['aged_count'],
            $result['proposed_count'],
            $result['survived_count'],
            $result['next_update'] ?? 'n/a',
        ));
    }

    public function confirmProposal(NpcDeathProposal $proposal, LifecycleAgingService $service): RedirectResponse
    {
        if ($proposal->status !== NpcDeathProposalStatus::Pending) {
            return redirect()->back()->with('error', 'Proposal is not pending.');
        }

        try {
            $service->confirmProposal($proposal, (int) Auth::id());
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'NPC death confirmed.');
    }

    public function rejectProposal(NpcDeathProposal $proposal, LifecycleAgingService $service): RedirectResponse
    {
        if ($proposal->status !== NpcDeathProposalStatus::Pending) {
            return redirect()->back()->with('error', 'Proposal is not pending.');
        }

        try {
            $service->rejectProposal($proposal, (int) Auth::id());
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'NPC death proposal rejected.');
    }
}
