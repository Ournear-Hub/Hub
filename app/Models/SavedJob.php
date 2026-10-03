<?php

namespace App\Models;

use Database\Factories\SavedJobFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedJob extends Model
{
    /** @use HasFactory<SavedJobFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'job_id',
    ];

    protected static function newFactory(): SavedJobFactory
    {
        return SavedJobFactory::new();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Job, $this>
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toAccountArray(): array
    {
        $job = $this->job;
        $open = $job?->isPubliclyVisible() ?? false;
        $closed = $job?->isClosed() ?? false;

        return [
            'title' => $job?->title ?? 'Role',
            'slug' => $job?->slug,
            'project' => $job?->project,
            'location' => $job?->location,
            'employment_type' => $job?->employment_type,
            'status' => $open ? 'open' : 'closed',
            'status_label' => $open ? 'Open' : ($closed ? 'Closed' : 'Unavailable'),
            'saved_at' => $this->created_at?->toDateTimeString(),
            'can_view' => $open,
        ];
    }
}
