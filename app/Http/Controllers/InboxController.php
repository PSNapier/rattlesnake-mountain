<?php

namespace App\Http\Controllers;

use App\Enums\HorseState;
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
        $messages = Message::with(['horse', 'admin', 'comments.user'])
            ->where('user_id', Auth::id())
            ->latest()
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'subject' => $message->subject,
                    'initial_message' => $message->initial_message,
                    'is_read' => $message->is_read,
                    'status' => $message->status,
                    'created_at' => $message->created_at->toIso8601String(),
                    'horse' => [
                        'id' => $message->horse->id,
                        'name' => $message->horse->name,
                        'design_link' => $message->horse->design_link,
                        'public_horse_id' => $message->horse->public_horse_id,
                        'is_edit' => $message->horse->public_horse_id !== null,
                    ],
                    'admin' => [
                        'id' => $message->admin->id,
                        'name' => $message->admin->name,
                    ],
                    'admin_edits' => $message->admin_edits,
                    'comment_count' => $message->comments->count(),
                    'latest_comment_at' => $message->comments->last()?->created_at?->toIso8601String(),
                ];
            });

        return Inertia::render('Inbox/Index', [
            'messages' => $messages,
        ]);
    }

    public function show(Message $message): Response
    {
        if ($message->user_id !== Auth::id() && ! Auth::user()->isAdmin()) {
            abort(403);
        }

        // Mark as read if user is the recipient
        if ($message->user_id === Auth::id() && ! $message->is_read) {
            $message->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        $message->load(['horse', 'admin', 'comments.user']);

        return Inertia::render('Inbox/Show', [
            'message' => [
                'id' => $message->id,
                'subject' => $message->subject,
                'initial_message' => $message->initial_message,
                'is_read' => $message->is_read,
                'status' => $message->status,
                'created_at' => $message->created_at->toIso8601String(),
                'horse' => [
                    'id' => $message->horse->id,
                    'name' => $message->horse->name,
                    'age' => $message->horse->age,
                    'geno' => $message->horse->geno,
                    'herd_id' => $message->horse->herd_id,
                    'design_link' => $message->horse->design_link,
                    'public_horse_id' => $message->horse->public_horse_id,
                    'is_edit' => $message->horse->public_horse_id !== null,
                ],
                'admin' => [
                    'id' => $message->admin->id,
                    'name' => $message->admin->name,
                ],
                'admin_edits' => $message->admin_edits,
                'comments' => $message->comments->map(function ($comment) {
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
                }),
            ],
        ]);
    }

    public function storeComment(Message $message, Request $request): RedirectResponse
    {
        if ($message->user_id !== Auth::id() && ! Auth::user()->isAdmin()) {
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

        // If owner comments, mark message as unread for admin
        if ($message->user_id === Auth::id()) {
            // Admin will see it when they check their side
            // For now, we'll just update the message timestamp
            $message->touch();
        }

        return redirect()->route('inbox.show', $message)
            ->with('success', 'Comment added successfully.');
    }

    public function accept(Message $message, Request $request): RedirectResponse
    {
        if ($message->user_id !== Auth::id()) {
            abort(403);
        }

        if ($message->status !== 'pending') {
            abort(400, 'This message has already been responded to.');
        }

        $horse = $message->horse;

        // Apply admin edits if they exist
        if ($message->admin_edits) {
            $updateData = [];
            foreach ($message->admin_edits as $field => $value) {
                if ($value !== null) {
                    $updateData[$field] = $value;
                }
            }
            if (! empty($updateData)) {
                $horse->update($updateData);
            }
        }

        // If it's an edit, merge changes into public horse
        if ($horse->public_horse_id) {
            $publicHorse = Horse::find($horse->public_horse_id);
            if ($publicHorse) {
                // Merge pending changes (with admin edits applied) into public horse
                $publicHorse->update([
                    'name' => $horse->name,
                    'age' => $horse->age,
                    'geno' => $horse->geno,
                    'herd_id' => $horse->herd_id,
                    'design_link' => $horse->design_link,
                ]);

                // Mark pending version as approved
                $horse->update([
                    'approved_at' => now(),
                ]);
            }
        } else {
            // For new horses, publish them (with admin edits already applied)
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

        // Add a comment with the decline reason if provided
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
}
