<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BreedingRequestStatus;
use App\Enums\BreedingSlotStatus;
use App\Enums\HorseState;
use App\Enums\NpcDeathProposalStatus;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\BreedingRequest;
use App\Models\BreedingSlot;
use App\Models\CmsPage;
use App\Models\Herd;
use App\Models\Horse;
use App\Models\HorseTransfer;
use App\Models\Item;
use App\Models\LifecycleSetting;
use App\Models\MenuItem;
use App\Models\Message;
use App\Models\NpcDeathProposal;
use App\Models\Role;
use App\Models\ShopListing;
use App\Models\User;
use App\Services\RoleCapabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request, RoleCapabilityService $roleCapabilities): Response
    {
        $user = $request->user();
        $capabilities = $user->adminCapabilities();

        $props = [
            'adminCapabilities' => $capabilities,
            'canManageRoleMatrix' => false,
            'roleCapabilityMatrix' => null,
            'capabilityAreas' => [],
        ];

        if ($user->can('admin.submissions')) {
            $props['submissions'] = $this->submissions();
            $props['herds'] = Herd::select('id', 'name')
                ->orderBy('name')
                ->get();
            $props['horseTransfers'] = $this->horseTransfers();
        }

        if ($user->can('admin.horses')) {
            // The Sanctuary is in this list and nowhere else: handing a horse over to
            // it is how an admin publishes the horse to the claimable pool.
            $props['transferableUsers'] = User::query()
                ->whereNull('deleted_at')
                ->orderByDesc('is_sanctuary')
                ->orderBy('name')
                ->limit(500)
                ->get(['id', 'name', 'is_sanctuary'])
                ->map(fn (User $candidate) => [
                    'id' => $candidate->id,
                    'name' => $candidate->name,
                    'is_sanctuary' => (bool) $candidate->is_sanctuary,
                ])
                ->values()
                ->all();
        }

        if ($user->can('admin.items')) {
            $props['items'] = Item::orderBy('name')->get();
        }

        if ($user->can('admin.shop')) {
            $props['shopListings'] = ShopListing::with('item:id,name,max_count')
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
                    'shop_flavor_text' => $listing->shop_flavor_text,
                    'image_path' => $listing->image_path,
                    'sort_order' => $listing->sort_order,
                ])
                ->values();
        }

        if ($user->can('admin.cms')) {
            $menuPaths = MenuItem::query()->get(['id', 'label', 'path'])->groupBy('path');

            $props['cmsPages'] = CmsPage::orderBy('sort_order')
                ->get(['id', 'slug', 'title', 'description', 'hero_title', 'hero_description', 'content', 'coming_soon', 'visibility', 'sort_order'])
                ->map(fn (CmsPage $page) => array_merge($page->toArray(), [
                    // `MenuItem.path` is free text, so this is the only link
                    // between a menu row and a page: the delete confirm lists
                    // what would be left pointing at a dead slug.
                    'menu_links' => $menuPaths->get('/'.$page->slug, collect())
                        ->map(fn (MenuItem $item) => [
                            'id' => $item->id,
                            'label' => $item->label,
                            'path' => $item->path,
                        ])->values()->all(),
                ]))
                ->values();
            $props['menuItems'] = MenuItem::with('children')->whereNull('parent_id')->orderBy('sort_order')->get()
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

            $props['announcements'] = Announcement::query()
                ->with('author:id,name')
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->limit(50)
                ->get()
                ->map(fn (Announcement $announcement) => [
                    'id' => $announcement->id,
                    'title' => $announcement->title,
                    'body' => $announcement->body,
                    'published_at' => $announcement->published_at?->toIso8601String(),
                    'author_name' => $announcement->author?->name,
                ])
                ->values()
                ->all();
        }

        if ($user->can('admin.lifecycle')) {
            $lifecycleSettings = LifecycleSetting::first();
            $props['lifecycleSettings'] = $lifecycleSettings ? [
                'horse_auto_age_next_update' => $lifecycleSettings->horse_auto_age_next_update->format('Y-m-d'),
                'horse_auto_age_frequency_unit' => $lifecycleSettings->horse_auto_age_frequency_unit,
                'horse_auto_age_frequency_value' => $lifecycleSettings->horse_auto_age_frequency_value,
                'horse_auto_age_game_years' => $lifecycleSettings->horse_auto_age_game_years,
                'horse_auto_health_roll_min' => $lifecycleSettings->horse_auto_health_roll_min,
                'horse_auto_health_roll_max' => $lifecycleSettings->horse_auto_health_roll_max,
                'npc_death_age_threshold' => $lifecycleSettings->npc_death_age_threshold,
                'npc_death_base_percent' => $lifecycleSettings->npc_death_base_percent,
                'npc_death_double_every_years' => $lifecycleSettings->npc_death_double_every_years,
                'npc_death_cap_percent' => $lifecycleSettings->npc_death_cap_percent,
            ] : null;

            $props['npcDeathProposals'] = NpcDeathProposal::query()
                ->with(['horse:id,name,age_months,owner_id'])
                ->where('status', NpcDeathProposalStatus::Pending)
                ->latest('rolled_at')
                ->get()
                ->map(fn (NpcDeathProposal $proposal) => [
                    'id' => $proposal->id,
                    'horse_id' => $proposal->horse_id,
                    'horse_name' => $proposal->horse?->name,
                    'age_months_at_roll' => $proposal->age_months_at_roll,
                    'formatted_age' => Horse::formatAgeMonths($proposal->age_months_at_roll),
                    'chance_percent' => $proposal->chance_percent,
                    'rolled_at' => $proposal->rolled_at?->toIso8601String(),
                ])
                ->values()
                ->all();
        }

        if ($user->can('admin.users')) {
            if ($user->isAdmin()) {
                $props['canManageRoleMatrix'] = true;
                $props['roleCapabilityMatrix'] = $roleCapabilities->matrix();
                $props['capabilityAreas'] = Role::areas();
            }

            $usersQuery = User::query()
                ->whereNull('deleted_at')
                ->where('is_sanctuary', false)
                ->select('id', 'name', 'role', 'created_at', 'last_login_at', 'frozen_at', 'banned_at');

            $search = $request->query('user_search');
            if (is_string($search) && $search !== '') {
                $usersQuery->where('name', 'like', '%'.addcslashes($search, '%_\\').'%');
            }

            $props['users'] = $usersQuery->orderBy('name')->paginate(25)->through(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'role' => $u->role->value,
                'created_at' => $u->created_at->toIso8601String(),
                'last_login_at' => $u->last_login_at?->toIso8601String(),
                'frozen_at' => $u->frozen_at?->toIso8601String(),
                'banned_at' => $u->banned_at?->toIso8601String(),
            ]);
            $props['userSearch'] = $search ?? '';
        }

        if ($user->can('admin.rollers')) {
            $props['breedingRequests'] = BreedingRequest::query()
                ->with([
                    'requester:id,name',
                    'sire:id,name,sex,geno',
                    'dam:id,name,sex,geno',
                ])
                ->where('status', BreedingRequestStatus::PendingStaff)
                ->latest()
                ->paginate(15, ['*'], 'breeding_page')
                ->withQueryString()
                ->through(fn (BreedingRequest $breedingRequest) => [
                    'id' => $breedingRequest->id,
                    'requester_name' => $breedingRequest->requester?->name,
                    'sire_id' => $breedingRequest->sire_id,
                    'sire_name' => $breedingRequest->sire?->name,
                    'sire_sex' => $breedingRequest->sire?->sex?->value,
                    'sire_geno' => $breedingRequest->sire?->geno,
                    'dam_id' => $breedingRequest->dam_id,
                    'dam_name' => $breedingRequest->dam?->name,
                    'dam_sex' => $breedingRequest->dam?->sex?->value,
                    'dam_geno' => $breedingRequest->dam?->geno,
                    'evidence_url' => $breedingRequest->evidence_url,
                    'notes' => $breedingRequest->notes,
                    'created_at' => $breedingRequest->created_at?->toIso8601String(),
                ]);

            $props['horsesMissingSex'] = Horse::query()
                ->where('state', HorseState::Public)
                ->whereNull('sex')
                ->orderBy('name')
                ->limit(50)
                ->get(['id', 'name', 'geno'])
                ->map(fn (Horse $horse) => [
                    'id' => $horse->id,
                    'name' => $horse->name,
                    'geno' => $horse->geno,
                ])
                ->values()
                ->all();

            $sanctuaryId = User::query()->where('is_sanctuary', true)->value('id');
            $props['sanctuarySlots'] = $sanctuaryId
                ? BreedingSlot::query()
                    ->with(['horse:id,name'])
                    ->where('holder_id', $sanctuaryId)
                    ->where('status', BreedingSlotStatus::Available)
                    ->orderBy('horse_id')
                    ->orderBy('sequence')
                    ->limit(100)
                    ->get()
                    ->map(fn (BreedingSlot $slot) => [
                        'id' => $slot->id,
                        'horse_id' => $slot->horse_id,
                        'horse_name' => $slot->horse?->name,
                        'sequence' => $slot->sequence,
                    ])
                    ->values()
                    ->all()
                : [];

            $props['grantableUsers'] = User::query()
                ->whereNull('deleted_at')
                ->where('is_sanctuary', false)
                ->orderBy('name')
                ->limit(100)
                ->get(['id', 'name']);
        }

        return Inertia::render('admin/Index', $props);
    }

    /**
     * Transfers are a third `kind` in the unified Submissions list, so they ship as a
     * third flat source array rather than getting a list of their own.
     *
     * @return array<int, array<string, mixed>>
     */
    private function horseTransfers(): array
    {
        return HorseTransfer::query()
            ->with(['horse:id,name', 'fromUser:id,name', 'toUser:id,name', 'actedBy:id,name'])
            ->latest()
            ->limit(200)
            ->get()
            ->map(fn (HorseTransfer $transfer) => [
                'id' => $transfer->id,
                'horse_id' => $transfer->horse_id,
                'horse_name' => $transfer->horse?->name ?? 'Deleted horse',
                'from_user_id' => $transfer->from_user_id,
                'from_user_name' => $transfer->fromUser?->name ?? 'Unknown',
                'to_user_id' => $transfer->to_user_id,
                'to_user_name' => $transfer->toUser?->name ?? 'Unknown',
                'notes' => $transfer->notes,
                'reason' => $transfer->reason,
                'status' => $transfer->status->value,
                'created_at' => $transfer->created_at?->toIso8601String(),
                'resolved_at' => $transfer->resolved_at?->toIso8601String(),
                'acted_by_name' => $transfer->actedBy?->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function submissions()
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

        return $horses->map(function ($horse) use ($latestMessages) {
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
                            'is_staff' => $comment->user->isStaff(),
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
                'is_high_priority' => (bool) $horse->is_high_priority,
                'intended_as_npc' => (bool) $horse->intended_as_npc,
                'design_link' => $horse->design_link,
                'age_years' => $horse->age_years,
                'age_months' => $horse->age_months_part,
                'age_months_total' => $horse->age_months,
                'formatted_age' => $horse->formatted_age,
                'geno' => $horse->geno,
                'sex' => $horse->sex?->value,
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
    }
}
