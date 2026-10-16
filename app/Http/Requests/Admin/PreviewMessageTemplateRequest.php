<?php

namespace App\Http\Requests\Admin;

use App\Enums\MessageTemplateChannel;
use App\Models\MessageTemplate;
use App\Rules\ValidMessageTemplatePlaceholders;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreviewMessageTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('preview', MessageTemplate::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $channel = MessageTemplateChannel::tryFrom((string) $this->input('channel'));

        return [
            'channel' => ['required', Rule::enum(MessageTemplateChannel::class)],
            'subject' => array_filter([
                $channel?->requiresSubject() ? 'nullable' : 'nullable',
                'string',
                'max:500',
                new ValidMessageTemplatePlaceholders(),
            ]),
            'body' => ['required', 'string', 'max:10000', new ValidMessageTemplatePlaceholders()],
        ];
    }
}
