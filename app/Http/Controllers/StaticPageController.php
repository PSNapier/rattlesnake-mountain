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
                'images' => $page->images ?? [],
                'content' => $page->content ?? [],
            ],
        ]);
    }
}
