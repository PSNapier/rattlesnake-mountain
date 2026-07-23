<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBreedingSlotTransferRequest;
use App\Models\BreedingSlot;
use App\Models\BreedingSlotTransfer;
use App\Models\User;
use App\Services\BreedingSlotService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class BreedingSlotTransferController extends Controller
{
    use AuthorizesRequests;

    public function store(
        StoreBreedingSlotTransferRequest $request,
        BreedingSlotService $slotService,
    ): RedirectResponse {
        $this->authorize('create', BreedingSlotTransfer::class);
        $this->ensureNotRateLimited();

        $slot = BreedingSlot::query()->findOrFail($request->validated('breeding_slot_id'));
        $recipient = User::query()->findOrFail($request->validated('to_user_id'));

        try {
            $slotService->offerTransfer(
                $slot,
                $request->user(),
                $recipient,
                $request->validated('notes'),
            );
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'breeding_slot_id' => $exception->getMessage(),
            ]);
        }

        RateLimiter::hit($this->throttleKey(), (int) config('breeding.transfer_rate_limit.decay_seconds', 3600));

        return back()->with('success', 'Breeding slot transfer offered.');
    }

    public function accept(
        BreedingSlotTransfer $transfer,
        BreedingSlotService $slotService,
    ): RedirectResponse {
        $this->authorize('accept', $transfer);

        try {
            $slotService->acceptTransfer($transfer, Auth::user());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['transfer' => $exception->getMessage()]);
        }

        return back()->with('success', 'Breeding slot transfer accepted.');
    }

    public function decline(
        BreedingSlotTransfer $transfer,
        BreedingSlotService $slotService,
    ): RedirectResponse {
        $this->authorize('decline', $transfer);

        try {
            $slotService->declineTransfer($transfer, Auth::user());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['transfer' => $exception->getMessage()]);
        }

        return back()->with('success', 'Breeding slot transfer declined.');
    }

    public function cancel(
        BreedingSlotTransfer $transfer,
        BreedingSlotService $slotService,
    ): RedirectResponse {
        $this->authorize('cancel', $transfer);

        try {
            $slotService->cancelTransfer($transfer, Auth::user());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['transfer' => $exception->getMessage()]);
        }

        return back()->with('success', 'Breeding slot transfer cancelled.');
    }

    private function ensureNotRateLimited(): void
    {
        $key = $this->throttleKey();
        $max = (int) config('breeding.transfer_rate_limit.max_attempts', 20);
        if (RateLimiter::tooManyAttempts($key, $max)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'transfer' => "Too many transfer attempts. Try again in {$seconds} seconds.",
            ]);
        }
    }

    private function throttleKey(): string
    {
        return 'breeding-transfer:'.Auth::id();
    }
}
