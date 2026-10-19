<?php

namespace App\Http\Requests\Admin;

class UpdatePublicPageImageRequest extends StorePublicPageImageRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'alt_text' => ['required', 'string', 'max:180'],
            'is_active' => ['required', 'boolean'],
            'image' => $this->imageRules(required: false),
        ];
    }
}
