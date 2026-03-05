<?php

namespace App\Http\Controllers;

use App\Models\Herd;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class LeaderboardController extends Controller
{
    public function index(): Response
    {
        $mostHorses = User::withCount('horses')
            ->having('horses_count', '>', 0)
            ->orderByDesc('horses_count')
            ->limit(20)
            ->get(['id', 'name', 'avatar']);

        $largestHerds = Herd::withCount('horses')
            ->with('owner:id,name,avatar')
            ->having('horses_count', '>', 0)
            ->orderByDesc('horses_count')
            ->limit(20)
            ->get();

        $mostScorpions = User::withSum(['items as scorpions_sum' => fn ($q) => $q->where('name', 'Scorpion')], 'user_items.quantity')
            ->having('scorpions_sum', '>', 0)
            ->orderByDesc('scorpions_sum')
            ->limit(20)
            ->get(['id', 'name', 'avatar']);

        return Inertia::render('Leaderboard/Index', [
            'mostHorses' => $mostHorses,
            'largestHerds' => $largestHerds,
            'mostScorpions' => $mostScorpions,
        ]);
    }
}
