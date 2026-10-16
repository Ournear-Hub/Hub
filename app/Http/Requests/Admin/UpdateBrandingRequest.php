<?php

namespace App\Http\Requests\Admin;

use App\Services\BrandingAssetService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UpdateBrandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageWebsite() === true;
    }

    protected function prepareForValidation(): void
    {
        $merged = [];

        foreach (['remove_logo', 'remove_wordmark', 'remove_icon', 'remove_favicon', 'remove_auth_splash'] as $key) {
            if ($this->has($key)) {
                $merged[$key] = filter_var($this->input($key), FILTER_VALIDATE_BOOLEAN);
            }
        }

        foreach (['logo_media_id', 'wordmark_media_id'] as $mediaKey) {
            if ($this->has($mediaKey) && $this->input($mediaKey) === '') {
                $merged[$mediaKey] = null;
            }
        }

        if ($merged !== []) {
            $this->merge($merged);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $image = [
            'nullable',
            'file',
            File::image()
                ->types(BrandingAssetService::allowedExtensions())
                ->max(BrandingAssetService::MAX_KILOBYTES),
            'mimetypes:'.implode(',', BrandingAssetService::allowedMimes()),
            Rule::dimensions()
                ->maxWidth(4096)
                ->maxHeight(4096),
        ];

        return [
            'website_name' => ['nullable', 'string', 'max:100'],
            'logo' => $image,
            'wordmark' => $image,
            'icon' => $image,
            'favicon' => $image,
            'remove_logo' => ['sometimes', 'boolean'],
            'remove_wordmark' => ['sometimes', 'boolean'],
            'remove_icon' => ['sometimes', 'boolean'],
            'remove_favicon' => ['sometimes', 'boolean'],
            'auth_splash' => [
                'nullable',
                'file',
                'mimetypes:video/mp4,video/quicktime,application/octet-stream',
                'max:'.BrandingAssetService::AUTH_SPLASH_MAX_KILOBYTES,
            ],
            'remove_auth_splash' => ['sometimes', 'boolean'],
            'logo_media_id' => ['nullable', 'integer', 'exists:media_assets,id'],
            'wordmark_media_id' => ['nullable', 'integer', 'exists:media_assets,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'logo.image' => 'The horizontal logo must be a JPG, PNG, or WEBP image.',
            'logo.mimetypes' => 'The horizontal logo must be a JPG, PNG, or WEBP image.',
            'logo.max' => 'The horizontal logo must not be larger than 5 MB.',
            'wordmark.image' => 'The companion wordmark must be a JPG, PNG, or WEBP image.',
            'wordmark.mimetypes' => 'The companion wordmark must be a JPG, PNG, or WEBP image.',
            'wordmark.max' => 'The companion wordmark must not be larger than 5 MB.',
            'icon.image' => 'The logo icon must be a JPG, PNG, or WEBP image.',
            'icon.mimetypes' => 'The logo icon must be a JPG, PNG, or WEBP image.',
            'icon.max' => 'The logo icon must not be larger than 5 MB.',
            'favicon.image' => 'The favicon must be a JPG, PNG, or WEBP image.',
            'favicon.mimetypes' => 'The favicon must be a JPG, PNG, or WEBP image.',
            'favicon.max' => 'The favicon must not be larger than 5 MB.',
        ];
    }
}
