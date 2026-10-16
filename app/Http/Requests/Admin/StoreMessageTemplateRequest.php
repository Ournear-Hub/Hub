<?php

namespace App\Http\Requests\Admin;

use App\Enums\MessageTemplateAudience;
use App\Enums\MessageTemplateChannel;
use App\Models\MessageTemplate;
use App\Rules\ValidMessageTemplatePlaceholders;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMessageTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', MessageTemplate::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $channel = MessageTemplateChannel::tryFrom((string) $this->input('channel'));
        $key = (string) $this->input('template_key');

        return [
            'template_key' => [
                'required',
                'string',
                'max:160',
                'regex:/^[a-z][a-z0-9._-]{2,120}$/',
                Rule::unique('message_templates', 'template_key'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'channel' => ['required', Rule::enum(MessageTemplateChannel::class)],
            'audience' => ['required', Rule::enum(MessageTemplateAudience::class)],
            'subject' => array_filter([
                $channel?->requiresSubject() ? 'required' : 'nullable',
                'string',
                'max:500',
                new ValidMessageTemplatePlaceholders($key !== '' ? $key : null),
            ]),
            'body' => ['required', 'string', 'max:10000', new ValidMessageTemplatePlaceholders($key !== '' ? $key : null)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
