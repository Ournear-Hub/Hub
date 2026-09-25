<?php

namespace App\Models;

use App\Enums\BusinessIdPrefix;
use App\Enums\FileLifecycleStatus;
use App\Enums\FileVisibility;
use App\Models\Concerns\HasBusinessId;
use Database\Factories\CandidateResumeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateResume extends Model
{
    /** @use HasFactory<CandidateResumeFactory> */
    use HasBusinessId, HasFactory;

    protected static function newFactory(): CandidateResumeFactory
    {
        return CandidateResumeFactory::new();
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'candidate_profile_id',
        'title',
        'original_filename',
        'storage_disk',
        'storage_path',
        'mime_type',
        'extension',
        'file_size',
        'is_primary',
        'primary_owner_id',
        'visibility',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'is_primary' => 'boolean',
            'visibility' => FileVisibility::class,
            'status' => FileLifecycleStatus::class,
        ];
    }

    public static function businessIdPrefix(): BusinessIdPrefix
    {
        return BusinessIdPrefix::Resume;
    }

    /**
     * @return BelongsTo<CandidateProfile, $this>
     */
    public function candidateProfile(): BelongsTo
    {
        return $this->belongsTo(CandidateProfile::class);
    }

    public function isActive(): bool
    {
        return $this->status === FileLifecycleStatus::Active;
    }

    /**
     * @return array<string, mixed>
     */
    public function toAccountArray(): array
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'title' => $this->title,
            'original_filename' => $this->original_filename,
            'extension' => $this->extension,
            'file_size' => $this->file_size,
            'is_primary' => (bool) $this->is_primary,
            'visibility' => $this->visibility?->value,
            'visibility_label' => $this->visibility?->label(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'download_url' => route('account.resumes.download', $this, false),
            'view_url' => route('account.resumes.view', $this, false),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'title' => $this->title,
            'original_filename' => $this->original_filename,
            'extension' => $this->extension,
            'file_size' => $this->file_size,
            'is_primary' => (bool) $this->is_primary,
            'visibility' => $this->visibility?->label(),
            'status' => $this->status?->label(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'view_url' => route('admin.candidates.resumes.show', ['resume' => $this, 'disposition' => 'inline'], false),
            'download_url' => route('admin.candidates.resumes.show', $this, false),
        ];
    }
}
