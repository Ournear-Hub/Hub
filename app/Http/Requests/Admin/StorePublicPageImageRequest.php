<?php

namespace App\Http\Requests\Admin;

use App\Services\MediaLibraryService;
use App\Support\PublicPageImageRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class StorePublicPageImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageWebsite() === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'page_slug' => ['required', 'string', 'max:64'],
            'slot_key' => ['required', 'string', 'max:64'],
            'title' => ['required', 'string', 'max:120'],
            'alt_text' => ['required', 'string', 'max:180'],
            'is_active' => ['required', 'boolean'],
            'image' => $this->imageRules(required: true),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $page = (string) $this->input('page_slug');
            $slot = (string) $this->input('slot_key');
            if ($page !== '' && $slot !== '' && ! PublicPageImageRegistry::has($page, $slot)) {
                $validator->errors()->add('slot_key', 'Choose a supported page section.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.required' => 'Please choose an image to upload.',
            'image.image' => 'The file must be a JPG, PNG, or WEBP image.',
            'image.mimetypes' => 'The file must be a JPG, PNG, or WEBP image.',
            'image.max' => 'The image must not be larger than 5 MB.',
            'image.dimensions' => 'The image must be at most 4096×4096 pixels.',
        ];
    }

    /**
     * @return list<mixed>
     */
    protected function imageRules(bool $required): array
    {
        return [
            $required ? 'required' : 'nullable',
            'file',
            File::image()
                ->types(MediaLibraryService::allowedExtensions())
                ->max(MediaLibraryService::MAX_KILOBYTES),
            'mimetypes:'.implode(',', MediaLibraryService::allowedMimes()),
            Rule::dimensions()
                ->maxWidth(MediaLibraryService::MAX_WIDTH)
                ->maxHeight(MediaLibraryService::MAX_HEIGHT),
        ];
    }
}
