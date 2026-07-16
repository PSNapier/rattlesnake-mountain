<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminAction;
use App\Enums\HorseState;
use App\Http\Controllers\Controller;
use App\Models\AdminSubmissionLog;
use App\Models\Horse;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubmissionController extends Controller
{
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

    public function unarchive(Horse $horse): RedirectResponse
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }

        if (! $horse->archived_at) {
            abort(400, 'Only archived horses can be unarchived.');
        }

        if ($horse->state !== HorseState::Pending || $horse->approved_at) {
            abort(400, 'Only pending, unapproved horses can be unarchived.');
        }

        $horse->update([
            'archived_at' => null,
            'contacted_at' => null,
        ]);

        AdminSubmissionLog::create([
            'horse_id' => $horse->id,
            'admin_id' => Auth::id(),
            'action' => AdminAction::Unarchived,
        ]);

        return redirect()->route('admin.index')
            ->with('success', 'Submission unarchived and restored to pending.');
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
