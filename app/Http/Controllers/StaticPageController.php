<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StaticPageController extends Controller
{
    public function show(Request $request): Response
    {
        $slug = $request->route('slug');
        $page = CmsPage::query()->where('slug', $slug)->first();

        if (! $page) {
            return Inertia::render('NotFound');
        }

        // A hidden page is invisible to everyone but the people who can edit
        // it, and they get a banner saying so.
        $mayPreview = $request->user()?->can('admin.cms') ?? false;

        if (! $page->isLive() && ! $mayPreview) {
            return Inertia::render('NotFound');
        }

        return Inertia::render('cms/Show', [
            'page' => [
                'id' => $page->id,
                'slug' => $page->slug,
                'title' => $page->title,
                'description' => $page->description,
                'hero' => [
                    'title' => $page->hero_title,
                    'description' => $page->hero_description,
                ],
                'coming_soon' => (bool) $page->coming_soon,
                'visibility' => $page->visibility,
                'not_public' => ! $page->isLive(),
                'content' => $page->content ?? [],
            ],
        ]);
    }
}
