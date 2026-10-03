<?php

namespace App\Http\Requests\Admin;

use App\Models\CandidateProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncCandidateTagsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $profile = $this->route('candidate');

        return $profile instanceof CandidateProfile && $this->user()?->can('update', $profile) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tag_ids' => ['present', 'array'],
            'tag_ids.*' => ['integer', Rule::exists('tags', 'id')],
        ];
    }

    /**
     * @return list<int>
     */
    public function tagIds(): array
    {
        return $this->collect('tag_ids')->map(fn ($id) => (int) $id)->all();
    }
}
