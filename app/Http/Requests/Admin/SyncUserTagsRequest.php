<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncUserTagsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        return $target instanceof User && $this->user()?->can('create', User::class) === true;
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
