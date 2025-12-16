<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHorseRequest;
use App\Http\Requests\UpdateHorseRequest;
use App\Models\Herd;
use App\Models\Horse;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class HorseController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Horse::class);

        // Only show the current user's horses on their personal page
        $horses = Horse::with(['owner', 'bredBy', 'herd'])
            ->where('owner_id', Auth::id())
            ->latest()
            ->get();

        return Inertia::render('Horses/Index', [
            'horses' => $horses,
            'can' => [
                'create' => Auth::user()->can('create', Horse::class),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->authorize('create', Horse::class);

        $herds = Herd::where('owner_id', Auth::id())->get();

        return Inertia::render('Horses/Create', [
            'herds' => $herds,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreHorseRequest $request): RedirectResponse
    {
        $this->authorize('create', Horse::class);

        $horse = Horse::create([
            'owner_id' => Auth::id(),
            'bred_by' => Auth::id(),
            'name' => $request->name,
            'age' => $request->age,
            'design_link' => $request->design_link,
            'geno' => $request->geno,
            'herd_id' => $request->herd_id,
            'bloodline' => $request->bloodline ?? [],
            'progeny' => $request->progeny ?? [],
            'stats' => $request->stats ?? [],
            'inventory' => $request->inventory ?? [],
            'equipment' => $request->equipment ?? [],
        ]);

        return redirect()->route('horses.show', $horse)
            ->with('success', 'Horse created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Horse $horse): Response
    {
        $this->authorize('view', $horse);

        $horse->load(['owner', 'bredBy', 'herd']);

        return Inertia::render('Horses/Show', [
            'horse' => $horse,
            'can' => [
                'update' => Auth::user()->can('update', $horse),
                'delete' => Auth::user()->can('delete', $horse),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Horse $horse): Response
    {
        $this->authorize('update', $horse);

        $herds = Herd::where('owner_id', Auth::id())->get();

        return Inertia::render('Horses/Edit', [
            'horse' => $horse,
            'herds' => $herds,
            'can' => [
                'update' => Auth::user()->can('update', $horse),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateHorseRequest $request, Horse $horse): RedirectResponse
    {
        $this->authorize('update', $horse);

        $horse->update([
            'name' => $request->name,
            'age' => $request->age,
            'design_link' => $request->design_link,
            'geno' => $request->geno,
            'herd_id' => $request->herd_id,
            'bloodline' => $request->bloodline ?? [],
            'progeny' => $request->progeny ?? [],
            'stats' => $request->stats ?? [],
            'inventory' => $request->inventory ?? [],
            'equipment' => $request->equipment ?? [],
        ]);

        return redirect()->route('horses.show', $horse)
            ->with('success', 'Horse updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Horse $horse): RedirectResponse
    {
        $this->authorize('delete', $horse);

        $horse->delete();

        return redirect()->route('horses.index')
            ->with('success', 'Horse deleted successfully!');
    }

    /**
     * Display a public listing of a user's horses.
     */
    public function publicIndex(User $user): Response
    {
        $horses = Horse::with(['owner', 'bredBy', 'herd'])
            ->where('owner_id', $user->id)
            ->latest()
            ->get();

        return Inertia::render('Horses/PublicIndex', [
            'user' => $user,
            'horses' => $horses,
        ]);
    }

    /**
     * Display a public view of a specific horse.
     */
    public function publicShow(User $user, Horse $horse): Response
    {
        // Ensure the horse belongs to the user
        if ($horse->owner_id !== $user->id) {
            abort(404);
        }

        $this->authorize('view', $horse);

        $horse->load(['owner', 'bredBy', 'herd']);

        return Inertia::render('Horses/Show', [
            'horse' => $horse,
            'can' => [
                'update' => Auth::check() && Auth::user()->can('update', $horse),
                'delete' => Auth::check() && Auth::user()->can('delete', $horse),
            ],
        ]);
    }
}
