<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHomepageStatisticsRequest extends FormRequest
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
            'registered_companies' => ['required', 'integer', 'min:0', 'max:999999999'],
            'talent_pool_companies' => ['required', 'integer', 'min:0', 'max:999999999'],
            'staffing_companies' => ['required', 'integer', 'min:0', 'max:999999999'],
            'registered_users' => ['required', 'integer', 'min:0', 'max:999999999'],
        ];
    }
}
