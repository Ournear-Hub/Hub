<?php

namespace App\Http\Requests\Admin;

use App\Models\Conversation;
use Illuminate\Foundation\Http\FormRequest;

class ReplyMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $conversation = $this->route('conversation');

        return $conversation instanceof Conversation
            && $this->user()?->can('update', $conversation) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:10000'],
        ];
    }
}
