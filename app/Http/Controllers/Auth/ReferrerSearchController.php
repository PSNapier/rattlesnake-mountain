<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferrerSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $minLength = (int) config('referral-rewards.search.min_length', 2);
        $limit = (int) config('referral-rewards.search.limit', 10);

        $query = trim((string) $request->input('q', ''));

        if (mb_strlen($query) < $minLength) {
            return response()->json([]);
        }

        $users = User::query()
            ->whereNull('banned_at')
            ->where('is_sanctuary', false)
            ->where('name', 'like', '%'.addcslashes($query, '%_\\').'%')
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name']);

        return response()->json($users);
    }
}
