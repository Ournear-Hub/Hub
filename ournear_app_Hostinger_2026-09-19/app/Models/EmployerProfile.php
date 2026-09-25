<?php

namespace App\Models;

use App\Enums\BusinessIdPrefix;
use App\Enums\EmployerSubmissionStatus;
use App\Models\Concerns\HasBusinessId;
use App\Support\RecruitmentWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class EmployerProfile extends Model
{
    use HasBusinessId;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'company_name',
        'contact_person',
        'mobile_number',
        'industry',
        'industry_id',
        'about_company',
        'company_size',
        'company_website',
        'company_details',
        'trial_job_used_at',
        'job_posting_approved_at',
        'job_posting_approved_by',
    ];

    protected function casts(): array
    {
        return [
            'trial_job_used_at' => 'datetime',
            'job_posting_approved_at' => 'datetime',
        ];
    }

    public static function businessIdPrefix(): BusinessIdPrefix
    {
        return BusinessIdPrefix::Employer;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Industry, $this>
     */
    public function classifiedIndustry(): BelongsTo
    {
        return $this->belongsTo(Industry::class, 'industry_id');
    }

    /**
     * @return HasMany<Job, $this>
     */
    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class, 'employer_id');
    }

    public function postingApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'job_posting_approved_by');
    }

    /**
     * @return HasMany<EmployerSubmission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(EmployerSubmission::class);
    }

    /**
     * @return HasMany<Placement, $this>
     */
    public function placements(): HasMany
    {
        return $this->hasMany(Placement::class);
    }

    /**
     * @return HasMany<Employee, $this>
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasMany<EmployerEnquiry, $this>
     */
    public function enquiries(): HasMany
    {
        return $this->hasMany(EmployerEnquiry::class, 'user_id', 'user_id');
    }

    /**
     * @return HasManyThrough<Application, Job, $this>
     */
    public function applications(): HasManyThrough
    {
        return $this->hasManyThrough(Application::class, Job::class, 'employer_id', 'job_id');
    }

    /**
     * @return HasMany<EmployerSubmission, $this>
     */
    public function selections(): HasMany
    {
        return $this->hasMany(EmployerSubmission::class)
            ->where('status', EmployerSubmissionStatus::Selected->value);
    }

    /**
     * @return HasMany<Replacement, $this>
     */
    public function replacements(): HasMany
    {
        return $this->hasMany(Replacement::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toAccountArray(): array
    {
        return [
            'company_name' => $this->company_name,
            'contact_person' => $this->contact_person,
            'mobile_number' => $this->mobile_number,
            'industry' => $this->industry,
            'about_company' => $this->about_company,
            'company_size' => $this->company_size,
            'company_website' => $this->company_website,
            'company_details' => $this->company_details,
        ];
    }

    public function displayIndustry(): string
    {
        return $this->classifiedIndustry?->name ?: (string) ($this->industry ?: '');
    }

    public function displayCompany(): string
    {
        return filled($this->company_name) ? (string) $this->company_name : 'Unnamed employer';
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminSummary(): array
    {
        $this->loadMissing('user');

        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'company_name' => $this->displayCompany(),
            'contact_person' => $this->contact_person ?: $this->user?->name,
            'email' => $this->user?->email,
            'mobile_number' => $this->mobile_number,
            'workspace_url' => route('admin.employers.index', ['employer' => $this->id], false),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toSelectArray(): array
    {
        $summary = $this->toAdminSummary();

        return [
            'id' => $summary['id'],
            'business_id' => $summary['business_id'],
            'company_name' => $summary['company_name'],
            'contact_person' => $summary['contact_person'],
            'email' => $summary['email'],
            'label' => trim(implode(' · ', array_filter([
                $summary['business_id'],
                $summary['company_name'],
                $summary['contact_person'],
            ]))),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing(['user', 'classifiedIndustry']);

        $verified = $this->user?->emailVerified() === true;
        $accountStatus = $this->user?->accountStatus();

        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'company_name' => $this->displayCompany(),
            'contact_person' => $this->contact_person ?: $this->user?->name,
            'email' => $this->user?->email,
            'mobile_number' => $this->mobile_number,
            'industry' => $this->displayIndustry() ?: null,
            'industry_id' => $this->industry_id,
            'location' => null,
            'verification_status' => $verified ? 'Verified' : 'Unverified',
            'email_verified' => $verified,
            'account_status' => $accountStatus?->value,
            'account_status_label' => $accountStatus?->label(),
            'job_posting_approved' => $this->job_posting_approved_at !== null,
            'job_posting_approved_at' => $this->job_posting_approved_at?->toDateTimeString(),
            'jobs_count' => $this->jobs_count ?? $this->jobs()->count(),
            'active_jobs_count' => $this->active_jobs_count ?? $this->jobs()->where('active', true)->count(),
            'applications_count' => $this->applications_count ?? $this->applications()->count(),
            'submissions_count' => $this->submissions_count ?? $this->submissions()->count(),
            'selections_count' => $this->selections_count ?? $this->selections()->count(),
            'placements_count' => $this->placements_count ?? $this->placements()->count(),
            'employees_count' => $this->employees_count ?? $this->employees()->count(),
            'replacements_count' => $this->replacements_count ?? $this->replacements()->count(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'workspace_url' => route('admin.employers.index', ['employer' => $this->id], false),
            'links' => [
                'jobs' => route('admin.jobs.index', ['employer_id' => $this->id], false),
                'applications' => route('admin.applications.index', ['employer_id' => $this->id], false),
                'submissions' => route('admin.submissions.index', ['employer_id' => $this->id], false),
                'selections' => route('admin.submissions.index', [
                    'employer_id' => $this->id,
                    'status' => EmployerSubmissionStatus::Selected->value,
                ], false),
                'placements' => route('admin.placements.index', ['employer_id' => $this->id], false),
                'employees' => route('admin.employees.index', ['employer_id' => $this->id], false),
                'replacements' => route('admin.replacements.index', ['employer_id' => $this->id], false),
            ],
            'schema_notes' => [
                'location' => RecruitmentWorkspace::NOT_RECORDED,
            ],
        ];
    }
}
