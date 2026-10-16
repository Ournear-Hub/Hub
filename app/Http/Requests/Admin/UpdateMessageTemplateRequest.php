<?php

namespace App\Http\Requests\Admin;

use App\Enums\MessageTemplateAudience;
use App\Enums\MessageTemplateChannel;
use App\Models\MessageTemplate;
use App\Rules\ValidMessageTemplatePlaceholders;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMessageTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $template = $this->route('messageTemplate');

        return $template instanceof MessageTemplate
            && $this->user()?->can('update', $template) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var MessageTemplate $template */
        $template = $this->route('messageTemplate');
        $channel = MessageTemplateChannel::tryFrom((string) $this->input('channel', $template->channel->value));

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'channel' => ['required', Rule::enum(MessageTemplateChannel::class)],
            'audience' => ['required', Rule::enum(MessageTemplateAudience::class)],
            'subject' => array_filter([
                $channel?->requiresSubject() ? 'required' : 'nullable',
                'string',
                'max:500',
                new ValidMessageTemplatePlaceholders($template->template_key),
            ]),
            'body' => ['required', 'string', 'max:10000', new ValidMessageTemplatePlaceholders($template->template_key)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
