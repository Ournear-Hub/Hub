<?php

namespace App\Http\Requests\Admin;

use App\Services\MediaLibraryService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreMediaRequest extends FormRequest
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
            'image' => [
                'required',
                'file',
                File::image()
                    ->types(MediaLibraryService::allowedExtensions())
                    ->max(MediaLibraryService::MAX_KILOBYTES),
                'mimetypes:'.implode(',', MediaLibraryService::allowedMimes()),
                Rule::dimensions()
                    ->maxWidth(MediaLibraryService::MAX_WIDTH)
                    ->maxHeight(MediaLibraryService::MAX_HEIGHT),
            ],
        ];
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
}
