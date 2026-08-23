<?php

namespace App\Http\Controllers;

use App\Enums\TradeStatus;
use App\Http\Requests\StoreTradeRequest;
use App\Models\Trade;
use App\Models\User;
use App\Services\TradeService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class TradeController extends Controller
{
    use AuthorizesRequests;

    public function index(): Response
    {
        $user = Auth::user();

        $trades = Trade::query()
            ->with(['items.item:id,name', 'fromUser:id,name', 'toUser:id,name'])
            ->where(function ($query) use ($user) {
                $query->where('from_user_id', $user->id)
                    ->orWhere('to_user_id', $user->id);
            })
            ->latest('id')
            ->paginate(25)
            ->through(fn (Trade $trade) => [
                'id' => $trade->id,
                'status' => $trade->status->value,
                'note' => $trade->note,
                'direction' => $trade->from_user_id === $user->id ? 'outgoing' : 'incoming',
                'from_user' => ['id' => $trade->from_user_id, 'name' => $trade->fromUser?->name],
                'to_user' => ['id' => $trade->to_user_id, 'name' => $trade->toUser?->name],
                'created_at' => $trade->created_at?->toIso8601String(),
                'resolved_at' => $trade->resolved_at?->toIso8601String(),
                'items' => $trade->items->map(fn ($line) => [
                    'item_id' => $line->item_id,
                    'name' => $line->item?->name,
                    'quantity' => $line->quantity,
                ])->values()->all(),
            ]);

        $inventory = $user->items()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'quantity' => (int) $item->pivot->quantity,
                'max_count' => $item->max_count,
            ])
            ->values();

        // Players cannot see each other's numeric ids anywhere in the UI, so the
        // page needs a name-to-id list to make the recipient field usable.
        $recipients = User::query()
            ->whereKeyNot($user->id)
            ->whereNull('banned_at')
            ->where('is_sanctuary', false)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $candidate) => [
                'id' => $candidate->id,
                'name' => $candidate->name,
            ])
            ->values();

        return Inertia::render('Trades/Index', [
            'trades' => $trades,
            'inventory' => $inventory,
            'recipients' => $recipients,
            'pendingIncoming' => Trade::query()
                ->where('to_user_id', $user->id)
                ->where('status', TradeStatus::Pending)
                ->count(),
        ]);
    }

    public function store(StoreTradeRequest $request, TradeService $trades): RedirectResponse
    {
        $this->authorize('create', Trade::class);
        $this->ensureNotRateLimited();

        // Count the attempt, not the success. Rejected offers are just as cheap
        // to spam as accepted ones.
        RateLimiter::hit($this->throttleKey(), (int) config('trading.rate_limit.decay_seconds', 3600));

        $recipient = User::query()->findOrFail($request->validated('to_user_id'));

        try {
            $trades->offer(
                $request->user(),
                $recipient,
                $request->validated('items'),
                $request->validated('note'),
            );
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'items' => $exception->getMessage(),
            ]);
        }

        return back()->with('success', 'Trade offered.');
    }

    public function accept(Trade $trade, TradeService $trades): RedirectResponse
    {
        $this->authorize('accept', $trade);

        try {
            $trades->accept($trade, Auth::user());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['trade' => $exception->getMessage()]);
        } catch (QueryException) {
            // Deadlock or lock timeout after the transaction's own retries.
            return back()->withErrors([
                'trade' => 'That trade could not be processed just now. Please try again.',
            ]);
        }

        return back()->with('success', 'Trade accepted.');
    }

    public function decline(Trade $trade, TradeService $trades): RedirectResponse
    {
        $this->authorize('decline', $trade);

        try {
            $trades->decline($trade, Auth::user());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['trade' => $exception->getMessage()]);
        } catch (QueryException) {
            // Deadlock or lock timeout after the transaction's own retries.
            return back()->withErrors([
                'trade' => 'That trade could not be processed just now. Please try again.',
            ]);
        }

        return back()->with('success', 'Trade declined.');
    }

    public function cancel(Trade $trade, TradeService $trades): RedirectResponse
    {
        $this->authorize('cancel', $trade);

        try {
            $trades->cancel($trade, Auth::user());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['trade' => $exception->getMessage()]);
        } catch (QueryException) {
            // Deadlock or lock timeout after the transaction's own retries.
            return back()->withErrors([
                'trade' => 'That trade could not be processed just now. Please try again.',
            ]);
        }

        return back()->with('success', 'Trade cancelled.');
    }

    private function ensureNotRateLimited(): void
    {
        $key = $this->throttleKey();
        $max = (int) config('trading.rate_limit.max_attempts', 30);

        if (RateLimiter::tooManyAttempts($key, $max)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'trade' => "Too many trade offers. Try again in {$seconds} seconds.",
            ]);
        }
    }

    private function throttleKey(): string
    {
        return 'trade-offer:'.Auth::id();
    }
}
