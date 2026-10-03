<?php

namespace App\Models;

use App\Enums\InternalCandidateResumeSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalCandidateResume extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'display_name',
        'source',
        'notes',
        'original_filename',
        'storage_disk',
        'storage_path',
        'mime_type',
        'extension',
        'file_size',
        'user_id',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => InternalCandidateResumeSource::class,
            'file_size' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
