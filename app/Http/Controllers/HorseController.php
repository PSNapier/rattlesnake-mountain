<?php

namespace App\Http\Controllers;

use App\Enums\AdminAction;
use App\Enums\HorseState;
use App\Http\Requests\StoreHorseRequest;
use App\Http\Requests\UpdateHorseRequest;
use App\Models\AdminSubmissionLog;
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
        // Exclude pending versions that are edits (they should only show the public version)
        // Exclude approved pending versions that are edits (they're historical records)
        // Show: public horses, new pending horses (without public_horse_id), approved new horses
        $horses = Horse::with(['owner', 'bredBy', 'herd'])
            ->where('owner_id', Auth::id())
            ->where(function ($query) {
                $query->where(function ($q) {
                    // Public horses
                    $q->where('state', HorseState::Public);
                })->orWhere(function ($q) {
                    // New pending horses (not edits)
                    $q->where('state', HorseState::Pending)
                        ->whereNull('public_horse_id')
                        ->whereNull('approved_at');
                })->orWhere(function ($q) {
                    // Approved new horses (now public)
                    $q->whereNotNull('approved_at')
                        ->whereNull('public_horse_id');
                });
            })
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
            'state' => HorseState::Pending,
        ]);

        return redirect()->route('horses.show', $horse)
            ->with('success', 'Horse created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Horse $horse): Response|RedirectResponse
    {
        $this->authorize('view', $horse);

        // If trying to view an approved pending version (edit), redirect to the public horse
        if ($horse->state === HorseState::Pending && $horse->approved_at && $horse->public_horse_id) {
            return redirect()->route('horses.show', $horse->public_horse_id);
        }

        $horse->load(['owner', 'bredBy', 'herd']);

        // Check if this public horse has a pending version (excluding archived ones)
        $pendingVersion = null;
        if ($horse->state === HorseState::Public) {
            $pendingVersion = $horse->pendingVersions()
                ->where('state', HorseState::Pending)
                ->whereNull('approved_at')
                ->whereNull('archived_at')
                ->first();
        }

        return Inertia::render('Horses/Show', [
            'horse' => $horse,
            'pendingVersion' => $pendingVersion,
            'can' => [
                'update' => Auth::user()->can('update', $horse),
                'delete' => Auth::user()->can('delete', $horse),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Horse $horse): Response|RedirectResponse
    {
        $this->authorize('update', $horse);

        // If trying to edit an approved pending version, redirect to the public horse
        if ($horse->state === HorseState::Pending && $horse->approved_at && $horse->public_horse_id) {
            return redirect()->route('horses.edit', $horse->public_horse_id);
        }

        // If trying to edit an archived pending edit, redirect to the public horse
        if ($horse->state === HorseState::Pending && $horse->archived_at && $horse->public_horse_id) {
            return redirect()->route('horses.edit', $horse->public_horse_id);
        }

        $herds = Herd::where('owner_id', Auth::id())->get();

        // Check if this is a pending edit (has public_horse_id) or if the public horse has pending edits
        $isPendingEdit = false;
        $publicHorse = null;

        if ($horse->state === HorseState::Pending && $horse->public_horse_id) {
            // This is a pending edit version
            $isPendingEdit = true;
            $publicHorse = Horse::find($horse->public_horse_id);
        } elseif ($horse->state === HorseState::Public) {
            // Check if there's a pending version for this public horse (excluding archived ones)
            $pendingVersion = $horse->pendingVersions()
                ->where('state', HorseState::Pending)
                ->whereNull('approved_at')
                ->whereNull('archived_at')
                ->first();
            if ($pendingVersion) {
                // Redirect to edit the pending version instead
                return redirect()->route('horses.edit', $pendingVersion);
            }
        }

        // Load admin contact logs for pending horses
        $adminLogs = [];
        if ($horse->state === HorseState::Pending) {
            $adminLogs = $horse->adminLogs()
                ->with('admin')
                ->where('action', AdminAction::Contacted)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'admin_name' => $log->admin->name,
                        'notes' => $log->notes,
                        'created_at' => $log->created_at->toIso8601String(),
                    ];
                })
                ->toArray();
        }

        return Inertia::render('Horses/Edit', [
            'horse' => $horse,
            'herds' => $herds,
            'isPendingEdit' => $isPendingEdit,
            'publicHorse' => $publicHorse,
            'adminLogs' => $adminLogs,
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

        // Prevent updating approved pending versions (they're historical records)
        if ($horse->state === HorseState::Pending && $horse->approved_at && $horse->public_horse_id) {
            return redirect()->route('horses.edit', $horse->public_horse_id)
                ->with('error', 'Cannot edit an approved pending version. Please edit the public horse instead.');
        }

        // If the horse is public, create a pending version instead of updating directly
        if ($horse->state === HorseState::Public) {
            // Check if there's already a pending version (excluding approved and archived ones)
            $pendingVersion = $horse->pendingVersions()
                ->where('state', HorseState::Pending)
                ->whereNull('approved_at')
                ->whereNull('archived_at')
                ->first();

            if ($pendingVersion) {
                // Update existing pending version
                $pendingVersion->update([
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

                return redirect()->route('horses.show', $pendingVersion)
                    ->with('success', 'Pending changes updated. Waiting for approval.');
            }

            // Create new pending version
            $pendingVersion = Horse::create([
                'owner_id' => $horse->owner_id,
                'bred_by' => $horse->bred_by,
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
                'state' => HorseState::Pending,
                'public_horse_id' => $horse->id,
            ]);

            return redirect()->route('horses.show', $pendingVersion)
                ->with('success', 'Pending changes created. Waiting for approval.');
        }

        // For pending horses, update directly
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
            ->visibleTo(Auth::user())
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

        // Check if this public horse has a pending version
        $pendingVersion = null;
        if ($horse->state === HorseState::Public) {
            $pendingVersion = $horse->pendingVersions()->where('state', HorseState::Pending)->first();
        }

        return Inertia::render('Horses/Show', [
            'horse' => $horse,
            'pendingVersion' => $pendingVersion,
            'can' => [
                'update' => Auth::check() && Auth::user()->can('update', $horse),
                'delete' => Auth::check() && Auth::user()->can('delete', $horse),
            ],
        ]);
    }

    /**
     * Approve a pending horse version and merge it with the public version.
     */
    public function approve(Horse $horse): RedirectResponse
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }

        if ($horse->state !== HorseState::Pending || ! $horse->public_horse_id) {
            abort(400, 'Only pending versions linked to public horses can be approved.');
        }

        $publicHorse = Horse::findOrFail($horse->public_horse_id);

        // Update the public horse with pending changes
        $publicHorse->update([
            'name' => $horse->name,
            'age' => $horse->age,
            'design_link' => $horse->design_link,
            'geno' => $horse->geno,
            'herd_id' => $horse->herd_id,
            'bloodline' => $horse->bloodline,
            'progeny' => $horse->progeny,
            'stats' => $horse->stats,
            'inventory' => $horse->inventory,
            'equipment' => $horse->equipment,
        ]);

        // Mark the pending version as approved instead of deleting
        $horse->update([
            'approved_at' => now(),
        ]);

        // Log the approval action
        AdminSubmissionLog::create([
            'horse_id' => $horse->id,
            'admin_id' => Auth::id(),
            'action' => AdminAction::Approved,
            'notes' => null,
        ]);

        // If this is an Inertia request (from admin queue), redirect back to admin
        if (request()->header('X-Inertia')) {
            return redirect()->route('admin.index')
                ->with('success', 'Pending changes approved and merged successfully!');
        }

        return redirect()->route('horses.show', $publicHorse)
            ->with('success', 'Pending changes approved and merged successfully!');
    }

    /**
     * Publish a new pending horse (make it public).
     */
    public function publish(Horse $horse): RedirectResponse
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }

        if ($horse->state !== HorseState::Pending || $horse->public_horse_id !== null) {
            abort(400, 'Only new pending horses can be published.');
        }

        $horse->update([
            'state' => HorseState::Public,
            'approved_at' => now(),
        ]);

        // Log the approval action
        AdminSubmissionLog::create([
            'horse_id' => $horse->id,
            'admin_id' => Auth::id(),
            'action' => AdminAction::Approved,
            'notes' => null,
        ]);

        // If this is an Inertia request (from admin queue), redirect back to admin
        if (request()->header('X-Inertia')) {
            return redirect()->route('admin.index')
                ->with('success', 'Horse published successfully!');
        }

        return redirect()->route('horses.show', $horse)
            ->with('success', 'Horse published successfully!');
    }
}
