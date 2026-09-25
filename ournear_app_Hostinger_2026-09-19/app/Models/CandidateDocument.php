<?php

namespace App\Models;

use App\Enums\BusinessIdPrefix;
use App\Enums\DocumentType;
use App\Enums\DocumentVerificationStatus;
use App\Enums\FileLifecycleStatus;
use App\Enums\FileVisibility;
use App\Models\Concerns\HasBusinessId;
use Database\Factories\CandidateDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateDocument extends Model
{
    /** @use HasFactory<CandidateDocumentFactory> */
    use HasBusinessId, HasFactory;

    protected static function newFactory(): CandidateDocumentFactory
    {
        return CandidateDocumentFactory::new();
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'candidate_profile_id',
        'document_type',
        'title',
        'original_filename',
        'storage_disk',
        'storage_path',
        'mime_type',
        'extension',
        'file_size',
        'issue_date',
        'expiry_date',
        'visibility',
        'status',
        'verification_status',
        'verified_by',
        'verified_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'verified_at' => 'datetime',
            'document_type' => DocumentType::class,
            'visibility' => FileVisibility::class,
            'status' => FileLifecycleStatus::class,
            'verification_status' => DocumentVerificationStatus::class,
        ];
    }

    public static function businessIdPrefix(): BusinessIdPrefix
    {
        return BusinessIdPrefix::Document;
    }

    /**
     * @return BelongsTo<CandidateProfile, $this>
     */
    public function candidateProfile(): BelongsTo
    {
        return $this->belongsTo(CandidateProfile::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * @return array<string, mixed>
     */
    public function toAccountArray(): array
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'document_type' => $this->document_type?->value,
            'document_type_label' => $this->document_type?->label(),
            'title' => $this->title,
            'original_filename' => $this->original_filename,
            'extension' => $this->extension,
            'file_size' => $this->file_size,
            'issue_date' => $this->issue_date?->toDateString(),
            'expiry_date' => $this->expiry_date?->toDateString(),
            'visibility' => $this->visibility?->value,
            'visibility_label' => $this->visibility?->label(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'verification_status' => $this->verification_status?->value,
            'verification_status_label' => $this->verification_status?->label(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'download_url' => route('account.documents.download', $this, false),
            'view_url' => route('account.documents.view', $this, false),
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
            'document_type' => $this->document_type?->value,
            'label' => $this->title,
            'title' => $this->title,
            'original_filename' => $this->original_filename,
            'extension' => $this->extension,
            'file_size' => $this->file_size,
            'issue_date' => $this->issue_date?->toDateString(),
            'expiry_date' => $this->expiry_date?->toDateString(),
            'visibility' => $this->visibility?->label(),
            'status' => $this->status?->label(),
            'verification_status' => $this->verification_status?->label(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'view_url' => route('admin.candidates.documents.show', ['document' => $this, 'disposition' => 'inline'], false),
            'download_url' => route('admin.candidates.documents.show', $this, false),
        ];
    }
}
