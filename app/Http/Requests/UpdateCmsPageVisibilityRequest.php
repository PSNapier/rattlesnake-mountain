<?php

namespace App\Http\Requests;

use App\Models\CmsPage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCmsPageVisibilityRequest extends FormRequest
{
    /**
     * Home and news are guarded here rather than in the UI alone: hiding them
     * must be impossible through any route, not just hard to click.
     */
    public function authorize(): bool
    {
        if (! ($this->user()?->can('admin.cms') ?? false)) {
            return false;
        }

        $page = $this->route('page');

        return ! ($page instanceof CmsPage && $page->isProtected());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'visibility' => ['required', 'string', 'in:'.CmsPage::VISIBILITY_LIVE.','.CmsPage::VISIBILITY_HIDDEN],
        ];
    }
}
