<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserItemRequest;
use App\Http\Requests\UpdateUserRoleRequest;
use App\Models\Item;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function searchUsers(Request $request): JsonResponse
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }

        $query = $request->input('q', '');
        $limit = $request->input('limit', 10);

        $users = User::whereNull('deleted_at')
            ->where('is_sanctuary', false)
            ->where('name', 'like', '%'.addcslashes($query, '%_\\').'%')
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name']);

        return response()->json($users);
    }

    public function getUserInventory(User $user): JsonResponse
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }

        $allItems = Item::where('is_active', true)
            ->orderBy('name')
            ->get();

        $userItems = $user->items()
            ->where('is_active', true)
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->id => $item->pivot->quantity];
            });

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
            ],
            'items' => $allItems,
            'userItems' => $userItems,
        ]);
    }

    public function updateUserItem(UpdateUserItemRequest $request, User $user): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();

        if ($validated['quantity'] > 0) {
            $user->items()->syncWithoutDetaching([
                $validated['item_id'] => ['quantity' => $validated['quantity']],
            ]);
        } else {
            $user->items()->detach($validated['item_id']);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'User inventory updated successfully.',
            ]);
        }

        return redirect()->route('admin.users.items', $user)
            ->with('success', 'User inventory updated successfully.');
    }

    public function freezeUser(User $user): RedirectResponse
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }
        if ($user->is_sanctuary || $user->deleted_at) {
            abort(400, 'Cannot freeze this user.');
        }
        $user->update(['frozen_at' => now()]);

        return redirect()->back()->with('success', "Frozen {$user->name}.");
    }

    public function unfreezeUser(User $user): RedirectResponse
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }
        $user->update(['frozen_at' => null]);

        return redirect()->back()->with('success', "Unfroze {$user->name}.");
    }

    public function banUser(User $user): RedirectResponse
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }
        if ($user->is_sanctuary || $user->deleted_at || $user->isAdmin()) {
            abort(400, 'Cannot ban this user.');
        }
        $user->update(['banned_at' => now()]);

        return redirect()->back()->with('success', "Banned {$user->name}.");
    }

    public function unbanUser(User $user): RedirectResponse
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }
        $user->update(['banned_at' => null]);

        return redirect()->back()->with('success', "Unbanned {$user->name}.");
    }

    public function updateUserRole(UpdateUserRoleRequest $request, User $user): RedirectResponse
    {
        if ($user->is_sanctuary || $user->deleted_at) {
            abort(400, 'Cannot change role for this user.');
        }
        $user->update(['role' => $request->validated('role')]);

        return redirect()->back()->with('success', "Updated {$user->name}'s role.");
    }

    public function deleteUser(User $user): RedirectResponse
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }
        if ($user->is_sanctuary || $user->deleted_at || $user->isAdmin()) {
            abort(400, 'Cannot delete this user.');
        }

        $sanctuary = User::sanctuary();

        $user->herds()->update(['owner_id' => $sanctuary->id, 'created_by' => $sanctuary->id]);
        $user->createdHerds()->update(['created_by' => $sanctuary->id]);
        $user->horses()->update(['owner_id' => $sanctuary->id]);
        $user->bredHorses()->update(['bred_by' => $sanctuary->id]);

        $user->items()->detach();

        $name = $user->name;
        $user->forceFill([
            'name' => 'Deleted User',
            'email' => 'deleted_'.$user->id.'@deleted.local',
            'password' => bcrypt(Str::random(64)),
            'bio' => null,
            'avatar' => null,
            'referred_by_username' => null,
        ])->save();

        $user->delete();

        return redirect()->back()->with('success', "Deleted {$name}.");
    }
}
