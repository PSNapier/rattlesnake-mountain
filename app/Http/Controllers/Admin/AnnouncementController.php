<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnnouncementRequest;
use App\Http\Requests\UpdateAnnouncementRequest;
use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        Announcement::create(array_merge($request->validated(), [
            'author_id' => $request->user()->id,
        ]));

        return redirect()->back()->with('success', 'Announcement created successfully.');
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        $announcement->update($request->validated());

        return redirect()->back()->with('success', 'Announcement updated successfully.');
    }

    public function destroy(Request $request, Announcement $announcement): RedirectResponse
    {
        if (! $request->user()?->can('admin.cms')) {
            abort(403);
        }

        $announcement->delete();

        return redirect()->back()->with('success', 'Announcement deleted successfully.');
    }
}
