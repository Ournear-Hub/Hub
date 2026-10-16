<?php

namespace App\Http\Requests\Account;

use App\Services\EmployerLogoService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountCompanyLogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->canAccessHiringWorkspace();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'logo' => [
                'required',
                'file',
                'max:'.EmployerLogoService::MAX_KILOBYTES,
                'mimes:'.implode(',', EmployerLogoService::allowedExtensions()),
                'mimetypes:'.implode(',', EmployerLogoService::allowedMimes()),
            ],
        ];
    }
}
