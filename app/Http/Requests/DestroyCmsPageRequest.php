<?php

namespace App\Http\Requests;

use App\Models\CmsPage;
use Illuminate\Foundation\Http\FormRequest;

class DestroyCmsPageRequest extends FormRequest
{
    /**
     * Home is guarded here rather than in the UI alone: deleting the front
     * door must be impossible through any route, not just hard to click.
     */
    public function authorize(): bool
    {
        if (! ($this->user()?->can('admin.cms') ?? false)) {
            return false;
        }

        $page = $this->route('page');

        return ! ($page instanceof CmsPage && $page->isHome());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
