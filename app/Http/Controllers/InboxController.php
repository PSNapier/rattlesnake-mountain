<?php

namespace App\Http\Controllers;

use App\Enums\AdminAction;
use App\Enums\HorseState;
use App\Enums\MessageType;
use App\Models\AdminSubmissionLog;
use App\Models\Horse;
use App\Models\Message;
use App\Models\MessageComment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class InboxController extends Controller
{
    public function index(): Response
    {
        $messages = Message::with(['horse', 'admin', 'breedingRequest', 'comments.user'])
            ->where('user_id', Auth::id())
            ->latest()
            ->get()
            ->map(function (Message $message) {
                return $this->mapMessageSummary($message);
            });

        return Inertia::render('Inbox/Index', [
            'messages' => $messages,
        ]);
    }

    public function show(Message $message): Response
    {
        if ($message->user_id !== Auth::id() && ! Auth::user()->can('admin.submissions')) {
            abort(403);
        }

        if ($message->user_id === Auth::id() && ! $message->is_read) {
            $message->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        $message->load(['horse', 'admin', 'breedingRequest', 'comments.user']);

        return Inertia::render('Inbox/Show', [
            'message' => $this->mapMessageDetail($message),
        ]);
    }

    public function storeComment(Message $message, Request $request): RedirectResponse
    {
        if ($message->type !== MessageType::HorseSubmission) {
            abort(400, 'Comments are not available for this message type.');
        }

        if ($message->user_id !== Auth::id() && ! Auth::user()->can('admin.submissions')) {
            abort(403);
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        MessageComment::create([
            'message_id' => $message->id,
            'user_id' => Auth::id(),
            'body' => $validated['body'],
        ]);

        if ($message->user_id === Auth::id()) {
            $horse = $message->horse;

            if ($horse && $horse->contacted_at && ! $horse->approved_at && ! $horse->archived_at) {
                $horse->update([
                    'contacted_at' => null,
                ]);

                AdminSubmissionLog::create([
                    'horse_id' => $horse->id,
                    'admin_id' => $message->admin_id,
                    'action' => AdminAction::Contacted,
                    'notes' => "Owner replied: {$validated['body']}",
                ]);
            }

            $message->touch();
        }

        return redirect()->route('inbox.show', $message)
            ->with('success', 'Comment added successfully.');
    }

    public function accept(Message $message, Request $request): RedirectResponse
    {
        if ($message->type !== MessageType::HorseSubmission) {
            abort(400, 'This message cannot be accepted.');
        }

        if ($message->user_id !== Auth::id()) {
            abort(403);
        }

        if ($message->status !== 'pending') {
            abort(400, 'This message has already been responded to.');
        }

        $horse = $message->horse;

        if ($message->admin_edits) {
            $updateData = [];
            foreach ($message->admin_edits as $field => $value) {
                if ($value !== null) {
                    $updateData[$field] = $value;
                }
            }
            $allowed = ['name', 'age_years', 'age_months', 'geno', 'herd_id', 'design_link'];
            $updateData = array_intersect_key($updateData, array_flip($allowed));

            if (array_key_exists('age_years', $updateData) || array_key_exists('age_months', $updateData)) {
                $years = (int) ($updateData['age_years'] ?? $horse->age_years);
                $months = (int) ($updateData['age_months'] ?? $horse->age_months_part);
                $updateData['age_months'] = Horse::monthsFromYearsAndMonths($years, $months);
                unset($updateData['age_years']);
            }

            if (! empty($updateData)) {
                $horse->update($updateData);
            }
        }

        if ($horse->public_horse_id) {
            $publicHorse = Horse::find($horse->public_horse_id);
            if ($publicHorse) {
                $publicHorse->update([
                    'name' => $horse->name,
                    'geno' => $horse->geno,
                    'herd_id' => $horse->herd_id,
                    'design_link' => $horse->design_link,
                ]);

                $horse->update([
                    'approved_at' => now(),
                ]);
            }
        } else {
            $horse->update([
                'state' => HorseState::Public,
                'approved_at' => now(),
            ]);
        }

        $message->update([
            'status' => 'accepted',
            'responded_at' => now(),
        ]);

        return redirect()->route('inbox.show', $message)
            ->with('success', 'Admin edits accepted successfully.');
    }

    public function decline(Message $message, Request $request): RedirectResponse
    {
        if ($message->type !== MessageType::HorseSubmission) {
            abort(400, 'This message cannot be declined.');
        }

        if ($message->user_id !== Auth::id()) {
            abort(403);
        }

        if ($message->status !== 'pending') {
            abort(400, 'This message has already been responded to.');
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $message->update([
            'status' => 'declined',
            'responded_at' => now(),
        ]);

        if (! empty($validated['reason'])) {
            MessageComment::create([
                'message_id' => $message->id,
                'user_id' => Auth::id(),
                'body' => 'Declined: '.$validated['reason'],
            ]);
        }

        return redirect()->route('inbox.show', $message)
            ->with('success', 'Admin edits declined.');
    }

    /**
     * @return array<string, mixed>
     */
    private function mapMessageSummary(Message $message): array
    {
        $type = $message->type ?? MessageType::HorseSubmission;

        $base = [
            'id' => $message->id,
            'type' => $type->value,
            'subject' => $message->subject,
            'initial_message' => $message->initial_message,
            'is_read' => $message->is_read,
            'status' => $message->status,
            'created_at' => $message->created_at->toIso8601String(),
            'admin' => $message->admin ? [
                'id' => $message->admin->id,
                'name' => $message->admin->name,
            ] : null,
            'admin_edits' => null,
            'comment_count' => $message->comments->count(),
            'latest_comment_at' => $message->comments->last()?->created_at?->toIso8601String(),
            'horse' => null,
            'breeding_request_id' => $message->breeding_request_id,
            'breeding_url' => null,
        ];

        if ($type === MessageType::BreedingResult) {
            $base['breeding_url'] = route('breedings.index');
            $base['status'] = 'informational';

            return $base;
        }

        $base['admin_edits'] = $message->admin_edits;
        $base['horse'] = $message->horse ? [
            'id' => $message->horse->id,
            'name' => $message->horse->name,
            'design_link' => $message->horse->design_link,
            'public_horse_id' => $message->horse->public_horse_id,
            'is_edit' => $message->horse->public_horse_id !== null,
        ] : null;

        return $base;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapMessageDetail(Message $message): array
    {
        $type = $message->type ?? MessageType::HorseSubmission;

        $base = [
            'id' => $message->id,
            'type' => $type->value,
            'subject' => $message->subject,
            'initial_message' => $message->initial_message,
            'is_read' => $message->is_read,
            'status' => $message->status,
            'created_at' => $message->created_at->toIso8601String(),
            'admin' => $message->admin ? [
                'id' => $message->admin->id,
                'name' => $message->admin->name,
            ] : null,
            'admin_edits' => null,
            'comments' => [],
            'horse' => null,
            'breeding_request_id' => $message->breeding_request_id,
            'breeding_url' => null,
        ];

        if ($type === MessageType::BreedingResult) {
            $base['status'] = 'informational';
            $base['breeding_url'] = route('breedings.index');

            return $base;
        }

        $base['admin_edits'] = $message->admin_edits;
        $base['horse'] = $message->horse ? [
            'id' => $message->horse->id,
            'name' => $message->horse->name,
            'age_years' => $message->horse->age_years,
            'age_months' => $message->horse->age_months_part,
            'formatted_age' => $message->horse->formatted_age,
            'geno' => $message->horse->geno,
            'herd_id' => $message->horse->herd_id,
            'design_link' => $message->horse->design_link,
            'public_horse_id' => $message->horse->public_horse_id,
            'is_edit' => $message->horse->public_horse_id !== null,
        ] : null;
        $base['comments'] = $message->comments->map(function ($comment) {
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
        })->values()->all();

        return $base;
    }
}
