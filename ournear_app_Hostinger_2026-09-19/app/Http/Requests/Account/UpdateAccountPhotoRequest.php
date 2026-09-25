<?php

namespace App\Http\Requests\Account;

use App\Services\UserPhotoService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAccountMember() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'photo' => [
                'required',
                'file',
                'max:'.UserPhotoService::MAX_KILOBYTES,
                'mimes:'.implode(',', UserPhotoService::allowedExtensions()),
                'mimetypes:'.implode(',', UserPhotoService::allowedMimes()),
            ],
        ];
    }
}
