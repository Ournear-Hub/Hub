<?php

namespace App\Http\Requests\Admin;

use App\Enums\ConversationContextType;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Conversation::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'recipient_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'context_type' => ['nullable', Rule::enum(ConversationContextType::class)],
            'context_id' => ['nullable', 'integer', 'required_with:context_type'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $recipient = User::query()->find($this->integer('recipient_id'));
            if ($recipient && (int) $recipient->id === (int) $this->user()?->id) {
                $validator->errors()->add('recipient_id', 'Choose another Admin recipient.');
            }
        });
    }
}
