<?php

namespace App\Http\Requests\Admin;

use App\Models\CandidateProfile;
use Illuminate\Foundation\Http\FormRequest;

class StoreSavedSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', CandidateProfile::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'filters' => ['required', 'array'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        $filters = $this->input('filters', []);

        return is_array($filters) ? $filters : [];
    }
}
