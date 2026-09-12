<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminHorseTransferRequest;
use App\Models\Horse;
use App\Models\HorseTransfer;
use App\Models\User;
use App\Services\HorseTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

class HorseTransferController extends Controller
{
    public function approve(
        Request $request,
        HorseTransfer $transfer,
        HorseTransferService $transfers,
    ): RedirectResponse {
        try {
            $transfers->approve($transfer, $request->user());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['transfer' => $exception->getMessage()]);
        }

        return redirect()->route('admin.index')->with('success', 'Transfer approved.');
    }

    public function reject(
        Request $request,
        HorseTransfer $transfer,
        HorseTransferService $transfers,
    ): RedirectResponse {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $transfers->reject($transfer, $request->user(), $validated['reason']);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['transfer' => $exception->getMessage()]);
        }

        return redirect()->route('admin.index')->with('success', 'Transfer rejected and the sender notified.');
    }

    /**
     * Move a horse outright. Deliberately gated on `horses` rather than `submissions`:
     * approving queued rows is a different power from rewriting ownership at will.
     */
    public function transfer(
        StoreAdminHorseTransferRequest $request,
        Horse $horse,
        HorseTransferService $transfers,
    ): RedirectResponse {
        $recipient = User::query()->findOrFail($request->validated('to_user_id'));

        try {
            $transfers->transferNow($horse, $request->user(), $recipient, $request->validated('reason'));
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'to_user_id' => $exception->getMessage(),
            ]);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['transfer' => $exception->getMessage()]);
        }

        return redirect()->route('admin.index')
            ->with('success', "{$horse->name} now belongs to {$recipient->name}.");
    }

    /**
     * Name search for the Horses tab picker. Unlike the player-facing lists this sees
     * every horse, since the whole point of the tab is reaching ones the rules hide.
     */
    public function search(Request $request): JsonResponse
    {
        $query = (string) $request->input('q', '');
        $limit = min((int) $request->input('limit', 10), 25);

        $horses = Horse::query()
            ->with('owner:id,name')
            ->where('name', 'like', '%'.addcslashes($query, '%_\\').'%')
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'owner_id', 'state', 'died_at'])
            ->map(fn (Horse $horse) => [
                'id' => $horse->id,
                'name' => $horse->name,
                'owner_id' => $horse->owner_id,
                'owner_name' => $horse->owner?->name,
                'state' => $horse->state?->value,
                'is_dead' => $horse->died_at !== null,
            ])
            ->values();

        return response()->json($horses);
    }
}
