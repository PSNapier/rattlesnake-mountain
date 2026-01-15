<?php

namespace App\Http\Controllers;

use App\Enums\AdminAction;
use App\Enums\HorseState;
use App\Models\AdminSubmissionLog;
use App\Models\Horse;
use App\Models\Message;
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
                ];
            });

        $herds = \App\Models\Herd::select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/Index', [
            'submissions' => $horses,
            'herds' => $herds,
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
}
