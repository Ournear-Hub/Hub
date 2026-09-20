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
        return $this->user()?->isAdmin() === true;
    }

    protected function prepareForValidation(): void
    {
        $merged = [];

        foreach (['remove_logo', 'remove_icon', 'remove_favicon'] as $key) {
            if ($this->has($key)) {
                $merged[$key] = filter_var($this->input($key), FILTER_VALIDATE_BOOLEAN);
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
            'icon' => $image,
            'favicon' => $image,
            'remove_logo' => ['sometimes', 'boolean'],
            'remove_icon' => ['sometimes', 'boolean'],
            'remove_favicon' => ['sometimes', 'boolean'],
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
            'icon.image' => 'The logo icon must be a JPG, PNG, or WEBP image.',
            'icon.mimetypes' => 'The logo icon must be a JPG, PNG, or WEBP image.',
            'icon.max' => 'The logo icon must not be larger than 5 MB.',
            'favicon.image' => 'The favicon must be a JPG, PNG, or WEBP image.',
            'favicon.mimetypes' => 'The favicon must be a JPG, PNG, or WEBP image.',
            'favicon.max' => 'The favicon must not be larger than 5 MB.',
        ];
    }
}
