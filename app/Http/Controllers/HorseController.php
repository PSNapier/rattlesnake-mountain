<?php

namespace App\Http\Controllers;

use App\Enums\AdminAction;
use App\Enums\HorseState;
use App\Http\Requests\HorseImageUploadRequest;
use App\Http\Requests\StoreHorseRequest;
use App\Http\Requests\UpdateHorseRequest;
use App\Models\AdminSubmissionLog;
use App\Models\Herd;
use App\Models\Horse;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

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
    public function approve(Horse $horse, Request $request): RedirectResponse
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }

        if ($horse->state !== HorseState::Pending || ! $horse->public_horse_id) {
            abort(400, 'Only pending versions linked to public horses can be approved.');
        }

        $publicHorse = Horse::findOrFail($horse->public_horse_id);

        // Use admin-edited values if provided, otherwise use pending version values
        $updateData = [
            'name' => $request->input('name', $horse->name),
            'age' => $request->input('age', $horse->age),
            'design_link' => $request->input('design_link', $horse->design_link),
            'geno' => $request->input('geno', $horse->geno),
            'herd_id' => $request->input('herd_id', $horse->herd_id),
            'bloodline' => $request->input('bloodline', $horse->bloodline ?? []),
            'progeny' => $request->input('progeny', $horse->progeny ?? []),
            'stats' => $request->input('stats', $horse->stats ?? []),
            'inventory' => $request->input('inventory', $horse->inventory ?? []),
            'equipment' => $request->input('equipment', $horse->equipment ?? []),
        ];

        // Update the public horse with pending changes (or admin edits)
        $publicHorse->update($updateData);

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
    public function publish(Horse $horse, Request $request): RedirectResponse
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }

        if ($horse->state !== HorseState::Pending || $horse->public_horse_id !== null) {
            abort(400, 'Only new pending horses can be published.');
        }

        // Use admin-edited values if provided, otherwise use existing values
        $updateData = [
            'name' => $request->input('name', $horse->name),
            'age' => $request->input('age', $horse->age),
            'design_link' => $request->input('design_link', $horse->design_link),
            'geno' => $request->input('geno', $horse->geno),
            'herd_id' => $request->input('herd_id', $horse->herd_id),
            'bloodline' => $request->input('bloodline', $horse->bloodline ?? []),
            'progeny' => $request->input('progeny', $horse->progeny ?? []),
            'stats' => $request->input('stats', $horse->stats ?? []),
            'inventory' => $request->input('inventory', $horse->inventory ?? []),
            'equipment' => $request->input('equipment', $horse->equipment ?? []),
            'state' => HorseState::Public,
            'approved_at' => now(),
        ];

        $horse->update($updateData);

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

    /**
     * Upload a horse design image.
     */
    public function uploadImage(HorseImageUploadRequest $request): JsonResponse
    {
        try {
            $file = $request->file('image');

            // Generate unique filename
            $filename = Str::random(40).'.webp';

            // Create image manager
            $manager = new ImageManager(new Driver);

            // Process and store main image
            $image = $manager->read($file);
            $width = $image->width();
            $height = $image->height();

            // Resize if too large (max 1200x1200)
            if ($width > 1200 || $height > 1200) {
                $image->scaleDown(1200);
            }

            // Convert to WebP and store
            $webpData = $image->toWebp(85);
            Storage::disk('public')->put('horse-images/'.$filename, $webpData);

            // Generate URL using a route (we'll create this route)
            $url = route('horse-images.serve', $filename);

            return response()->json([
                'success' => true,
                'url' => $url,
                'message' => 'Image uploaded successfully!',
            ]);

        } catch (\Exception $e) {
            // Clean up any uploaded files on error
            if (isset($filename) && Storage::disk('public')->exists('horse-images/'.$filename)) {
                Storage::disk('public')->delete('horse-images/'.$filename);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image. Please try again.',
            ], 500);
        }
    }
}
