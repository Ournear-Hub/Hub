<?php

namespace App\Services;

use App\Enums\MessageTemplateChannel;
use App\Enums\SystemMessageTemplateKey;
use App\Models\Application;
use App\Models\ApplicationInterview;
use App\Models\MessageTemplate;
use App\Models\MessageTemplateAudit;
use App\Models\User;
use App\Support\MessageTemplateRenderer;
use App\Support\MessageTemplateVariableCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MessageTemplateService
{
    public function __construct(private MessageTemplateRenderer $renderer) {}

    public function syncSystemTemplates(): void
    {
        foreach (SystemMessageTemplateKey::cases() as $key) {
            $defaults = $key->defaults();

            MessageTemplate::query()->updateOrCreate(
                ['template_key' => $key->value],
                [
                    'name' => $defaults['name'],
                    'description' => $defaults['description'],
                    'channel' => $key->channel(),
                    'audience' => $key->audience(),
                    'subject' => $defaults['subject'],
                    'body' => $defaults['body'],
                    'variables' => $defaults['variables'],
                    'is_active' => true,
                    'is_system_required' => $key->isRequired(),
                ],
            );
        }
    }

    public function findActiveByKey(string $templateKey): ?MessageTemplate
    {
        return MessageTemplate::query()
            ->where('template_key', $templateKey)
            ->where('is_active', true)
            ->first();
    }

    /**
     * @return array{subject: string|null, body: string, used_template: bool}
     */
    /**
     * @param  array<string, string>  $variableOverrides
     * @return array{subject: string|null, body: string, used_template: bool}
     */
    public function renderForApplication(
        string $templateKey,
        Application $application,
        ?ApplicationInterview $interview = null,
        ?string $fallbackSubject = null,
        ?string $fallbackBody = null,
        array $variableOverrides = [],
    ): array {
        $variables = array_merge(
            MessageTemplateVariableCatalog::forApplication($application, $interview),
            $variableOverrides,
        );
        $template = $this->findActiveByKey($templateKey);

        if (! $template instanceof MessageTemplate) {
            if ($fallbackSubject === null && $fallbackBody === null) {
                $system = SystemMessageTemplateKey::tryFrom($templateKey);
                if ($system instanceof SystemMessageTemplateKey) {
                    $defaults = $system->defaults();
                    $fallbackSubject = $defaults['subject'];
                    $fallbackBody = $defaults['body'];
                }
            }

            return [
                'subject' => $fallbackSubject,
                'body' => $this->renderString(
                    (string) ($fallbackBody ?? ''),
                    $variables,
                    MessageTemplateVariableCatalog::allowedForKey($templateKey),
                    str_ends_with($templateKey, '.email') ? MessageTemplateChannel::Email : MessageTemplateChannel::InApp,
                ),
                'used_template' => false,
            ];
        }

        $allowed = $template->variableList() ?: MessageTemplateVariableCatalog::allowedForKey($templateKey);

        return [
            'subject' => $template->subject !== null
                ? $this->renderString($template->subject, $variables, $allowed, $template->channel)
                : null,
            'body' => $this->renderString($template->body, $variables, $allowed, $template->channel),
            'used_template' => true,
        ];
    }

    /**
     * @param  array<string, string>  $variables
     * @param  list<string>  $allowedVariables
     */
    public function renderString(
        string $template,
        array $variables,
        array $allowedVariables,
        MessageTemplateChannel $channel,
    ): string {
        return $this->renderer->render($template, $variables, $allowedVariables, $channel);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveTemplate(?MessageTemplate $template, array $data, User $actor): MessageTemplate
    {
        return DB::transaction(function () use ($template, $data, $actor) {
            $isCreate = $template === null;

            $attributes = [
                'name' => trim((string) $data['name']),
                'description' => isset($data['description']) ? trim((string) $data['description']) : null,
                'channel' => $data['channel'],
                'audience' => $data['audience'],
                'subject' => isset($data['subject']) ? trim((string) $data['subject']) : null,
                'body' => trim((string) $data['body']),
                'variables' => $data['variables'] ?? null,
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : ($template?->is_active ?? true),
            ];

            if ($isCreate) {
                $attributes['template_key'] = trim((string) $data['template_key']);
                $attributes['created_by'] = $actor->id;
                $attributes['is_system_required'] = false;
                $template = MessageTemplate::query()->create($attributes);
                $this->audit($template, $actor, 'created', $attributes);

                return $template;
            }

            if ($template->is_system_required && $attributes['is_active'] === false) {
                throw ValidationException::withMessages([
                    'is_active' => 'This system template must remain active for recruitment workflows.',
                ]);
            }

            $before = $template->only(array_keys($attributes));
            $attributes['updated_by'] = $actor->id;
            $template->update($attributes);
            $this->audit($template, $actor, 'updated', [
                'before' => $before,
                'after' => $template->fresh()?->only(array_keys($attributes)),
            ]);

            return $template->fresh() ?? $template;
        });
    }

    public function setActive(MessageTemplate $template, bool $active, User $actor): MessageTemplate
    {
        if (! $active && $template->is_system_required) {
            throw ValidationException::withMessages([
                'is_active' => 'This system template must remain active for recruitment workflows.',
            ]);
        }

        $template->update([
            'is_active' => $active,
            'updated_by' => $actor->id,
        ]);

        $this->audit($template, $actor, $active ? 'activated' : 'deactivated', ['is_active' => $active]);

        return $template->fresh() ?? $template;
    }

    /**
     * @param  array<string, string|null>  $overrides
     * @return array{subject: string|null, body: string}
     */
    public function preview(
        MessageTemplateChannel $channel,
        ?string $subject,
        string $body,
        array $overrides = [],
    ): array {
        $variables = array_merge(MessageTemplateVariableCatalog::sampleValues(), $overrides);
        $allowed = MessageTemplateVariableCatalog::globalVariables();

        return [
            'subject' => $subject !== null && $subject !== ''
                ? $this->renderString($subject, $variables, $allowed, $channel)
                : null,
            'body' => $this->renderString($body, $variables, $allowed, $channel),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function audit(MessageTemplate $template, User $actor, string $action, array $payload): void
    {
        MessageTemplateAudit::query()->create([
            'message_template_id' => $template->id,
            'user_id' => $actor->id,
            'action' => $action,
            'changes' => $payload,
            'created_at' => now(),
        ]);
    }
}
