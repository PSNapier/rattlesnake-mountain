<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\GrantSanctuaryBreedingSlotRequest;
use App\Http\Requests\UpdateHorseSexRequest;
use App\Models\BreedingRequest;
use App\Models\BreedingSlot;
use App\Models\Horse;
use App\Models\User;
use App\Services\BreedingService;
use App\Services\BreedingSlotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

class BreedingController extends Controller
{
    public function roll(BreedingRequest $breedingRequest, BreedingService $breedingService): RedirectResponse
    {
        abort_unless(request()->user()?->can('admin.rollers'), 403);

        try {
            $breedingService->rollRequest($breedingRequest, request()->user());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['breeding' => $exception->getMessage()]);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['breeding' => $exception->getMessage()]);
        }

        return back()->with('success', 'Breeding results published.');
    }

    public function reject(BreedingRequest $breedingRequest, BreedingService $breedingService): RedirectResponse
    {
        abort_unless(request()->user()?->can('admin.rollers'), 403);

        try {
            $breedingService->rejectRequest($breedingRequest, request()->user());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['breeding' => $exception->getMessage()]);
        }

        return back()->with('success', 'Breeding request rejected.');
    }

    public function updateSex(Horse $horse, UpdateHorseSexRequest $request): RedirectResponse
    {
        $horse->update([
            'sex' => $request->validated('sex'),
        ]);

        return back()->with('success', 'Horse sex updated.');
    }

    public function grantSlot(
        GrantSanctuaryBreedingSlotRequest $request,
        BreedingSlotService $slotService,
    ): RedirectResponse {
        $slot = BreedingSlot::query()->findOrFail($request->validated('breeding_slot_id'));
        $recipient = User::query()->findOrFail($request->validated('to_user_id'));

        try {
            $slotService->grantSanctuarySlot(
                $slot,
                $recipient,
                $request->user(),
                $request->validated('notes'),
            );
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'breeding_slot_id' => $exception->getMessage(),
            ]);
        }

        return back()->with('success', 'Sanctuary breeding slot granted.');
    }
}
