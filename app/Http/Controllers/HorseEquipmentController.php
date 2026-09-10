<?php

namespace App\Http\Controllers;

use App\Models\Horse;
use App\Services\EquipmentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class HorseEquipmentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly EquipmentService $equipment) {}

    public function store(Request $request, Horse $horse): RedirectResponse
    {
        $this->authorize('update', $horse);

        $validated = $request->validate([
            'item_id' => ['required', 'integer', 'exists:items,id'],
        ]);

        try {
            $this->equipment->equip($horse, (int) $validated['item_id']);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['item_id' => $exception->getMessage()]);
        }

        return back()->with('success', 'Item equipped.');
    }

    public function destroy(Horse $horse, string $uid): RedirectResponse
    {
        $this->authorize('update', $horse);

        try {
            $item = $this->equipment->dequip($horse, $uid);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['equipment' => $exception->getMessage()]);
        }

        return back()->with('success', "{$item->name} returned to your inventory.");
    }

    public function use(Horse $horse, string $uid): RedirectResponse
    {
        $this->authorize('update', $horse);

        try {
            $result = $this->equipment->consume($horse, $uid);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['equipment' => $exception->getMessage()]);
        }

        $message = $result['consumed']
            ? "{$result['item']->name} was used up."
            : "{$result['item']->name} used. {$result['uses_remaining']} uses left.";

        return back()->with('success', $message);
    }
}
