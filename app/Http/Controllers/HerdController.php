<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHerdRequest;
use App\Http\Requests\UpdateHerdRequest;
use App\Models\Herd;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class HerdController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Herd::class);

        // Only show the current user's herds on their personal page
        $herds = Herd::with(['owner', 'herdLeader'])
            ->where('owner_id', Auth::id())
            ->latest()
            ->get();

        return Inertia::render('Herds/Index', [
            'herds' => $herds,
            'can' => [
                'create' => Auth::user()->can('create', Herd::class),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->authorize('create', Herd::class);

        return Inertia::render('Herds/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreHerdRequest $request): RedirectResponse
    {
        $this->authorize('create', Herd::class);

        $herd = Herd::create([
            'owner_id' => Auth::id(),
            'created_by' => Auth::id(),
            'name' => $request->name,
            'herd_leader_id' => $request->herd_leader_id,
            'herd_members' => $request->herd_members ?? [],
            'inventory' => $request->inventory ?? [],
            'equipment' => $request->equipment ?? [],
        ]);

        return redirect()->route('herds.show', $herd)
            ->with('success', 'Herd created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Herd $herd): Response
    {
        $this->authorize('view', $herd);

        $herd->load(['owner', 'createdBy', 'herdLeader', 'horses']);

        return Inertia::render('Herds/Show', [
            'herd' => $herd,
            'can' => [
                'update' => Auth::user()->can('update', $herd),
                'delete' => Auth::user()->can('delete', $herd),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Herd $herd): Response
    {
        $this->authorize('update', $herd);

        return Inertia::render('Herds/Edit', [
            'herd' => $herd,
            'can' => [
                'update' => Auth::user()->can('update', $herd),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateHerdRequest $request, Herd $herd): RedirectResponse
    {
        $this->authorize('update', $herd);

        $herd->update([
            'name' => $request->name,
            'herd_leader_id' => $request->herd_leader_id,
            'herd_members' => $request->herd_members ?? [],
            'inventory' => $request->inventory ?? [],
            'equipment' => $request->equipment ?? [],
        ]);

        return redirect()->route('herds.show', $herd)
            ->with('success', 'Herd updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Herd $herd): RedirectResponse
    {
        $this->authorize('delete', $herd);

        $herd->delete();

        return redirect()->route('herds.index')
            ->with('success', 'Herd deleted successfully!');
    }

    /**
     * Display a public listing of a user's herds.
     */
    public function publicIndex(User $user): Response
    {
        $herds = Herd::with(['owner', 'herdLeader'])
            ->where('owner_id', $user->id)
            ->latest()
            ->get();

        return Inertia::render('Herds/PublicIndex', [
            'user' => $user,
            'herds' => $herds,
        ]);
    }
}
