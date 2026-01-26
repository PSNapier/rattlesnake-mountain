<?php

namespace App\Http\Controllers;

use App\Enums\AdminAction;
use App\Enums\HorseState;
use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Http\Requests\UpdateUserItemRequest;
use App\Models\AdminSubmissionLog;
use App\Models\Horse;
use App\Models\Item;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function index(Request $request): Response
    {
        $horses = Horse::with(['owner', 'publicHorse', 'latestAdminLog.admin'])
            ->where(function ($query) {
                $query->where('state', HorseState::Pending)
                    ->orWhereNotNull('approved_at')
                    ->orWhereNotNull('archived_at')
                    ->orWhereNotNull('contacted_at');
            })
            ->latest()
            ->get()
            ->map(function ($horse) {
                $latestLog = $horse->latestAdminLog;

                // Determine status based on timestamps and latest log
                $status = 'pending';
                if ($horse->approved_at) {
                    $status = 'approved';
                } elseif ($horse->archived_at) {
                    $status = 'archived';
                } elseif ($horse->contacted_at) {
                    $status = 'contacted';
                }

                // Get last interaction date (contact/approval/archival)
                $lastInteractionDate = null;
                if ($latestLog) {
                    $lastInteractionDate = $latestLog->created_at->toIso8601String();
                } elseif ($horse->approved_at) {
                    $lastInteractionDate = $horse->approved_at->toIso8601String();
                }

                // Load message and comments for this horse
                $message = Message::with(['comments.user'])
                    ->where('horse_id', $horse->id)
                    ->latest()
                    ->first();

                $comments = [];
                if ($message) {
                    $comments = $message->comments->map(function ($comment) {
                        return [
                            'id' => $comment->id,
                            'body' => $comment->body,
                            'created_at' => $comment->created_at->toIso8601String(),
                            'user' => [
                                'id' => $comment->user->id,
                                'name' => $comment->user->name,
                                'is_admin' => $comment->user->isAdmin(),
                            ],
                        ];
                    })->toArray();
                }

                return [
                    'id' => $horse->id,
                    'user_id' => $horse->owner->id,
                    'user_name' => $horse->owner->name,
                    'name' => $horse->name,
                    'name_type' => 'horse',
                    'date_submitted' => $horse->created_at->toIso8601String(),
                    'status' => $status,
                    'last_contact_date' => $lastInteractionDate,
                    'last_admin_name' => $latestLog?->admin?->name,
                    'public_horse_id' => $horse->public_horse_id,
                    'is_edit' => $horse->public_horse_id !== null,
                    'design_link' => $horse->design_link,
                    'age' => $horse->age,
                    'geno' => $horse->geno,
                    'herd_id' => $horse->herd_id,
                    'message' => $message ? [
                        'id' => $message->id,
                        'subject' => $message->subject,
                        'initial_message' => $message->initial_message,
                        'admin_edits' => $message->admin_edits,
                    ] : null,
                    'comments' => $comments,
                ];
            });

        $herds = \App\Models\Herd::select('id', 'name')
            ->orderBy('name')
            ->get();

        $items = Item::orderBy('name')->get();

        return Inertia::render('admin/Index', [
            'submissions' => $horses,
            'herds' => $herds,
            'items' => $items,
        ]);
    }

    public function archive(Horse $horse, Request $request): RedirectResponse
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }

        if ($horse->state !== HorseState::Pending || $horse->approved_at) {
            abort(400, 'Only pending, unapproved horses can be archived.');
        }

        // Mark as archived (for both pending edits and new pending horses)
        // Archived pending edits are functionally disposed of (filtered out from editing)
        // but remain in the queue for historical purposes
        $horse->update([
            'archived_at' => now(),
        ]);

        AdminSubmissionLog::create([
            'horse_id' => $horse->id,
            'admin_id' => Auth::id(),
            'action' => AdminAction::Archived,
            'notes' => $request->input('notes'),
        ]);

        if ($horse->public_horse_id) {
            return redirect()->route('admin.index')
                ->with('success', 'Pending edit archived and disposed of successfully.');
        }

        return redirect()->route('admin.index')
            ->with('success', 'Submission archived successfully.');
    }

    public function contact(Horse $horse, Request $request): RedirectResponse
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }

        if ($horse->state !== HorseState::Pending || $horse->approved_at) {
            abort(400, 'Only pending, unapproved horses can be contacted.');
        }

        $horse->update([
            'contacted_at' => now(),
        ]);

        AdminSubmissionLog::create([
            'horse_id' => $horse->id,
            'admin_id' => Auth::id(),
            'action' => AdminAction::Contacted,
            'notes' => $request->input('notes'),
        ]);

        // Create a message for the owner
        $adminEdits = [];
        $adminForm = $request->only(['name', 'age', 'geno', 'herd_id', 'design_link']);
        foreach ($adminForm as $field => $value) {
            if ($value !== null && $value !== $horse->$field) {
                $adminEdits[$field] = $value;
            }
        }

        $subject = $horse->public_horse_id
            ? "Review requested for edits to {$horse->name}"
            : "Review requested for {$horse->name}";

        Message::create([
            'horse_id' => $horse->id,
            'user_id' => $horse->owner_id,
            'admin_id' => Auth::id(),
            'subject' => $subject,
            'initial_message' => $request->input('notes'),
            'admin_edits' => ! empty($adminEdits) ? $adminEdits : null,
        ]);

        return redirect()->route('admin.index')
            ->with('success', 'Owner contacted and message sent successfully.');
    }

    public function items(): Response
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }

        $items = Item::orderBy('name')->get();

        return Inertia::render('admin/Items', [
            'items' => $items,
        ]);
    }

    public function storeItem(StoreItemRequest $request): RedirectResponse
    {
        Item::create($request->validated());

        return redirect()->route('admin.items')
            ->with('success', 'Item created successfully.');
    }

    public function updateItem(UpdateItemRequest $request, Item $item): RedirectResponse
    {
        $item->update($request->validated());

        return redirect()->route('admin.index')
            ->with('success', 'Item updated successfully.');
    }

    public function destroyItem(Item $item): RedirectResponse
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }

        $item->delete();

        return redirect()->route('admin.items')
            ->with('success', 'Item deleted successfully.');
    }

    public function searchUsers(Request $request): \Illuminate\Http\JsonResponse
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }

        $query = $request->input('q', '');
        $limit = $request->input('limit', 10);

        $users = User::where('name', 'like', "%{$query}%")
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name']);

        return response()->json($users);
    }

    public function getUserInventory(User $user): \Illuminate\Http\JsonResponse
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }

        // Get all active items
        $allItems = Item::where('is_active', true)
            ->orderBy('name')
            ->get();

        // Get user's items with quantities
        $userItems = $user->items()
            ->where('is_active', true)
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->id => $item->pivot->quantity];
            });

        // Build inventory with all items, showing 0 for items not owned
        $inventory = $allItems->map(function ($item) use ($userItems) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'quantity' => $userItems->get($item->id, 0),
                'max_count' => $item->max_count,
            ];
        });

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
            'inventory' => $inventory,
        ]);
    }

    public function userItems(User $user): Response
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }

        $user->load('items');
        $allItems = Item::where('is_active', true)->orderBy('name')->get();

        $userItems = $user->items->mapWithKeys(function ($item) {
            return [$item->id => $item->pivot->quantity];
        });

        return Inertia::render('admin/UserItems', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'items' => $allItems,
            'userItems' => $userItems,
        ]);
    }

    public function updateUserItem(UpdateUserItemRequest $request, User $user): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $validated = $request->validated();

        if ($validated['quantity'] > 0) {
            $user->items()->syncWithoutDetaching([
                $validated['item_id'] => ['quantity' => $validated['quantity']],
            ]);
        } else {
            $user->items()->detach($validated['item_id']);
        }

        // Return JSON for AJAX requests
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'User inventory updated successfully.',
            ]);
        }

        return redirect()->route('admin.users.items', $user)
            ->with('success', 'User inventory updated successfully.');
    }
}
