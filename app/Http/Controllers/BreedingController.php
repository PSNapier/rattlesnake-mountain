<?php

namespace App\Http\Controllers;

use App\Enums\BreedingSlotStatus;
use App\Enums\HorseSex;
use App\Enums\HorseState;
use App\Http\Requests\CreateFoalFromBreedingRequest;
use App\Http\Requests\StoreBreedingRequestRequest;
use App\Models\BreedingRequest;
use App\Models\BreedingSlot;
use App\Models\BreedingSlotTransfer;
use App\Models\Herd;
use App\Models\Horse;
use App\Services\BreedingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class BreedingController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $user = $request->user();

        $requests = BreedingRequest::query()
            ->with([
                'sire:id,name,sex,geno,age_months',
                'dam:id,name,sex,geno,age_months',
                'foal:id,name,sex,geno,state',
            ])
            ->where('requester_id', $user->id)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $herds = Herd::query()
            ->where('owner_id', $user->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $slots = BreedingSlot::query()
            ->with(['horse:id,name,sex,owner_id'])
            ->where('holder_id', $user->id)
            ->where('status', '!=', BreedingSlotStatus::Consumed)
            ->orderBy('horse_id')
            ->orderBy('sequence')
            ->get();

        $incomingTransfers = BreedingSlotTransfer::query()
            ->with(['slot.horse:id,name', 'fromUser:id,name'])
            ->where('to_user_id', $user->id)
            ->where('status', 'pending')
            ->latest()
            ->get();

        $outgoingTransfers = BreedingSlotTransfer::query()
            ->with(['slot.horse:id,name', 'toUser:id,name'])
            ->where('from_user_id', $user->id)
            ->where('status', 'pending')
            ->latest()
            ->get();

        $eligibleSires = $this->eligibleParentsQuery($user->id, HorseSex::Stallion)->get();
        $eligibleDams = $this->eligibleParentsQuery($user->id, HorseSex::Mare)->get();

        return Inertia::render('Breedings/Index', [
            'requests' => $requests,
            'herds' => $herds,
            'slots' => $slots,
            'incomingTransfers' => $incomingTransfers,
            'outgoingTransfers' => $outgoingTransfers,
            'eligibleSires' => $eligibleSires,
            'eligibleDams' => $eligibleDams,
            'phenotypePlaceholder' => config('breeding.phenotype_placeholder'),
        ]);
    }

    public function store(
        StoreBreedingRequestRequest $request,
        BreedingService $breedingService,
    ): RedirectResponse {
        $this->authorize('create', BreedingRequest::class);
        $this->ensureNotRateLimited('breeding-request', (int) config('breeding.request_rate_limit.max_attempts', 10), (int) config('breeding.request_rate_limit.decay_seconds', 3600));

        $sire = Horse::query()->findOrFail($request->validated('sire_id'));
        $dam = Horse::query()->findOrFail($request->validated('dam_id'));

        try {
            $created = $breedingService->submitRequest(
                $request->user(),
                $sire,
                $dam,
                $request->validated('evidence_url'),
                $request->validated('notes'),
            );
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'sire_id' => $exception->getMessage(),
            ]);
        }

        RateLimiter::hit($this->throttleKey('breeding-request'), (int) config('breeding.request_rate_limit.decay_seconds', 3600));

        return redirect()
            ->route('breedings.index')
            ->with('success', 'Breeding request submitted. Awaiting staff roll.');
    }

    public function cancel(BreedingRequest $breedingRequest, BreedingService $breedingService): RedirectResponse
    {
        $this->authorize('cancel', $breedingRequest);

        try {
            $breedingService->cancelRequest($breedingRequest, Auth::user());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['breeding' => $exception->getMessage()]);
        }

        return redirect()
            ->route('breedings.index')
            ->with('success', 'Breeding request cancelled.');
    }

    public function createFoal(
        BreedingRequest $breedingRequest,
        CreateFoalFromBreedingRequest $request,
        BreedingService $breedingService,
    ): RedirectResponse {
        $this->authorize('createFoal', $breedingRequest);

        try {
            $foal = $breedingService->createFoal(
                $breedingRequest,
                $request->user(),
                (int) $request->validated('option_index'),
                [
                    'name' => $request->validated('name'),
                    'sex' => $request->validated('sex'),
                    'design_link' => $request->validated('design_link'),
                    'herd_id' => $request->validated('herd_id'),
                ],
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['foal' => $exception->getMessage()]);
        }

        return redirect()
            ->route('horses.show', $foal)
            ->with('success', 'Pending foal created from breeding results.');
    }

    private function eligibleParentsQuery(int $holderId, HorseSex $sex)
    {
        return Horse::query()
            ->select('horses.id', 'horses.name', 'horses.sex', 'horses.geno', 'horses.age_months')
            ->join('breeding_slots', 'breeding_slots.horse_id', '=', 'horses.id')
            ->where('breeding_slots.holder_id', $holderId)
            ->where('breeding_slots.status', BreedingSlotStatus::Available->value)
            ->where('horses.state', HorseState::Public)
            ->whereNull('horses.died_at')
            ->where('horses.sex', $sex->value)
            ->where('horses.age_months', '>=', (int) config('breeding.minimum_age_months', 24))
            ->groupBy('horses.id', 'horses.name', 'horses.sex', 'horses.geno', 'horses.age_months')
            ->orderBy('horses.name');
    }

    private function ensureNotRateLimited(string $action, int $maxAttempts, int $decaySeconds): void
    {
        $key = $this->throttleKey($action);
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'breeding' => "Too many attempts. Try again in {$seconds} seconds.",
            ]);
        }
    }

    private function throttleKey(string $action): string
    {
        return $action.':'.Auth::id();
    }
}
