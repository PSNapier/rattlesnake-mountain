<?php

namespace App\Http\Controllers;

use App\Enums\HorseState;
use App\Models\Horse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function index(Request $request): Response
    {
        $pendingHorses = Horse::with(['owner', 'publicHorse'])
            ->where('state', HorseState::Pending)
            ->latest()
            ->get()
            ->map(function ($horse) {
                return [
                    'id' => $horse->id,
                    'user_id' => $horse->owner->id,
                    'user_name' => $horse->owner->name,
                    'name' => $horse->name,
                    'name_type' => 'horse',
                    'date_submitted' => $horse->created_at->toIso8601String(),
                    'status' => 'pending',
                    'last_contact_date' => null,
                    'public_horse_id' => $horse->public_horse_id,
                    'is_edit' => $horse->public_horse_id !== null,
                ];
            });

        return Inertia::render('admin/Index', [
            'submissions' => $pendingHorses,
        ]);
    }
}
