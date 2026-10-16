<?php

namespace App\Http\Requests\Admin;

use App\Services\MediaLibraryService;
use Illuminate\Foundation\Http\FormRequest;
class StoreShowcaseCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageWebsite() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'employer_profile_id' => ['nullable', 'integer', 'exists:employer_profiles,id'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'show_on_home' => ['sometimes', 'boolean'],
            'show_on_talent_pool' => ['sometimes', 'boolean'],
            'show_on_staffing' => ['sometimes', 'boolean'],
            'media_asset_id' => ['nullable', 'integer', 'exists:media_assets,id'],
            'logo' => ['nullable', 'file', 'max:'.MediaLibraryService::MAX_KILOBYTES, 'mimes:jpg,jpeg,png,webp'],
        ];
    }
}
