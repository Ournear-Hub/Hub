<?php

namespace App\Models;

use App\Enums\MessageTemplateAudience;
use App\Enums\MessageTemplateChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessageTemplate extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'template_key',
        'name',
        'description',
        'channel',
        'audience',
        'subject',
        'body',
        'variables',
        'is_active',
        'is_system_required',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'is_active' => 'boolean',
            'is_system_required' => 'boolean',
            'channel' => MessageTemplateChannel::class,
            'audience' => MessageTemplateAudience::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @return HasMany<MessageTemplateAudit, $this>
     */
    public function audits(): HasMany
    {
        return $this->hasMany(MessageTemplateAudit::class);
    }

    /**
     * @return list<string>
     */
    public function variableList(): array
    {
        $variables = $this->variables;

        if (! is_array($variables)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($item) => is_string($item) ? trim($item) : null,
            $variables,
        )));
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        return [
            'id' => $this->id,
            'template_key' => $this->template_key,
            'name' => $this->name,
            'description' => $this->description,
            'channel' => $this->channel->value,
            'channel_label' => $this->channel->label(),
            'audience' => $this->audience->value,
            'audience_label' => $this->audience->label(),
            'subject' => $this->subject,
            'body' => $this->body,
            'variables' => $this->variableList(),
            'is_active' => $this->is_active,
            'is_system_required' => $this->is_system_required,
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
