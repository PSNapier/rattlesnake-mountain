<?php

namespace App\Http\Controllers\Admin;

use App\Enums\HorseState;
use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Models\Horse;
use App\Models\Item;
use App\Models\LifecycleSetting;
use App\Models\MenuItem;
use App\Models\Message;
use App\Models\ShopListing;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
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
            ->get();

        $horseIds = $horses->pluck('id');
        $latestMessages = $horseIds->isEmpty()
            ? collect()
            : Message::with(['comments.user'])
                ->whereIn('horse_id', $horseIds)
                ->latest('created_at')
                ->get()
                ->unique('horse_id')
                ->keyBy('horse_id');

        $horses = $horses->map(function ($horse) use ($latestMessages) {
            $latestLog = $horse->latestAdminLog;

            $status = 'pending';
            if ($horse->approved_at) {
                $status = 'approved';
            } elseif ($horse->archived_at) {
                $status = 'archived';
            } elseif ($horse->contacted_at) {
                $status = 'contacted';
            }

            $lastInteractionDate = null;
            if ($latestLog) {
                $lastInteractionDate = $latestLog->created_at->toIso8601String();
            } elseif ($horse->approved_at) {
                $lastInteractionDate = $horse->approved_at->toIso8601String();
            }

            $message = $latestMessages->get($horse->id);

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
        $shopListings = ShopListing::with('item:id,name,max_count')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (ShopListing $listing) => [
                'id' => $listing->id,
                'item_id' => $listing->item_id,
                'item_name' => $listing->item->name,
                'item_max_count' => $listing->item->max_count,
                'visible_in_shop' => $listing->visible_in_shop,
                'scorpion_price' => $listing->scorpion_price,
                'shop_description' => $listing->shop_description,
                'image_path' => $listing->image_path,
                'sort_order' => $listing->sort_order,
            ])
            ->values();

        $cmsPages = CmsPage::orderBy('sort_order')->get(['id', 'slug', 'title', 'description', 'hero_title', 'hero_description', 'content', 'images', 'sort_order']);
        $menuItems = MenuItem::with('children')->whereNull('parent_id')->orderBy('sort_order')->get()
            ->map(fn (MenuItem $item) => [
                'id' => $item->id,
                'label' => $item->label,
                'path' => $item->path,
                'sort_order' => $item->sort_order,
                'children' => $item->children->map(fn (MenuItem $child) => [
                    'id' => $child->id,
                    'label' => $child->label,
                    'path' => $child->path,
                    'sort_order' => $child->sort_order,
                ])->values()->all(),
            ])->values()->all();

        $lifecycleSettings = LifecycleSetting::first();

        $usersQuery = User::query()
            ->whereNull('deleted_at')
            ->where('is_sanctuary', false)
            ->select('id', 'name', 'role', 'created_at', 'last_login_at', 'frozen_at', 'banned_at');

        $search = $request->query('user_search');
        if (is_string($search) && $search !== '') {
            $usersQuery->where('name', 'like', '%'.addcslashes($search, '%_\\').'%');
        }

        $users = $usersQuery->orderBy('name')->paginate(25)->through(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'role' => $u->role->value,
            'created_at' => $u->created_at->toIso8601String(),
            'last_login_at' => $u->last_login_at?->toIso8601String(),
            'frozen_at' => $u->frozen_at?->toIso8601String(),
            'banned_at' => $u->banned_at?->toIso8601String(),
        ]);

        return Inertia::render('admin/Index', [
            'submissions' => $horses,
            'herds' => $herds,
            'items' => $items,
            'shopListings' => $shopListings,
            'cmsPages' => $cmsPages,
            'menuItems' => $menuItems,
            'lifecycleSettings' => $lifecycleSettings ? [
                'horse_auto_age_next_update' => $lifecycleSettings->horse_auto_age_next_update->format('Y-m-d'),
                'horse_auto_age_frequency_unit' => $lifecycleSettings->horse_auto_age_frequency_unit,
                'horse_auto_age_frequency_value' => $lifecycleSettings->horse_auto_age_frequency_value,
                'horse_auto_age_game_years' => $lifecycleSettings->horse_auto_age_game_years,
                'horse_auto_health_roll_min' => $lifecycleSettings->horse_auto_health_roll_min,
                'horse_auto_health_roll_max' => $lifecycleSettings->horse_auto_health_roll_max,
            ] : null,
            'users' => $users,
            'userSearch' => $search ?? '',
        ]);
    }
}
