<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHorseTransferRequest;
use App\Models\Horse;
use App\Models\HorseTransfer;
use App\Models\User;
use App\Services\HorseTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

class HorseTransferController extends Controller
{
    public function store(
        StoreHorseTransferRequest $request,
        Horse $horse,
        HorseTransferService $transfers,
    ): RedirectResponse {
        $recipient = User::query()->findOrFail($request->validated('to_user_id'));

        try {
            $transfers->request(
                $horse,
                $request->user(),
                $recipient,
                $request->validated('notes'),
            );
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'horse' => $exception->getMessage(),
            ]);
        }

        return back()->with('success', "{$horse->name} has been offered to {$recipient->name}. An admin will review it.");
    }

    public function cancel(
        Request $request,
        HorseTransfer $transfer,
        HorseTransferService $transfers,
    ): RedirectResponse {
        abort_unless((int) $transfer->from_user_id === (int) $request->user()->id, 403);

        try {
            $transfers->cancel($transfer, $request->user());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['transfer' => $exception->getMessage()]);
        }

        return back()->with('success', 'Transfer cancelled.');
    }
}
