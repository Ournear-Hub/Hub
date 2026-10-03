<?php

namespace App\Models;

use App\Enums\BusinessIdPrefix;
use App\Enums\DocumentVerificationStatus;
use App\Enums\EmployerDocumentType;
use App\Enums\FileLifecycleStatus;
use App\Models\Concerns\HasBusinessId;
use Database\Factories\EmployerDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployerDocument extends Model
{
    /** @use HasFactory<EmployerDocumentFactory> */
    use HasBusinessId, HasFactory;

    protected static function newFactory(): EmployerDocumentFactory
    {
        return EmployerDocumentFactory::new();
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'employer_profile_id',
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
        'status',
        'verification_status',
        'verified_by',
        'verified_at',
        'review_notes',
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
            'document_type' => EmployerDocumentType::class,
            'status' => FileLifecycleStatus::class,
            'verification_status' => DocumentVerificationStatus::class,
        ];
    }

    public static function businessIdPrefix(): BusinessIdPrefix
    {
        return BusinessIdPrefix::EmployerDocument;
    }

    /**
     * @return BelongsTo<EmployerProfile, $this>
     */
    public function employerProfile(): BelongsTo
    {
        return $this->belongsTo(EmployerProfile::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function expiryState(): ?string
    {
        if ($this->expiry_date === null) {
            return null;
        }

        if ($this->expiry_date->isPast()) {
            return 'expired';
        }

        if ($this->expiry_date->lte(now()->addDays(30))) {
            return 'expiring';
        }

        return null;
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
            'expiry_state' => $this->expiryState(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'verification_status' => $this->verification_status?->value,
            'verification_status_label' => $this->verification_status?->label(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'download_url' => route('account.employer.documents.download', $this, false),
            'view_url' => route('account.employer.documents.view', $this, false),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing('verifier');

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
            'expiry_state' => $this->expiryState(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'verification_status' => $this->verification_status?->value,
            'verification_status_label' => $this->verification_status?->label(),
            'verified_by_name' => $this->verifier?->name,
            'verified_at' => $this->verified_at?->toDateTimeString(),
            'review_notes' => $this->review_notes,
            'created_at' => $this->created_at?->toDateTimeString(),
            'view_url' => route('admin.employers.documents.show', [
                'employer' => $this->employer_profile_id,
                'document' => $this,
                'disposition' => 'inline',
            ], false),
            'download_url' => route('admin.employers.documents.show', [
                'employer' => $this->employer_profile_id,
                'document' => $this,
            ], false),
        ];
    }
}
