<?php

namespace App\Services\Admin;

use App\Enums\AccountStatus;
use App\Enums\CampaignStatus;
use App\Enums\ChannelPartnerStatus;
use App\Enums\CommissionEntryStatus;
use App\Enums\CrmOpportunityStatus;
use App\Enums\DocumentVerificationStatus;
use App\Enums\EmployerAttributionStatus;
use App\Enums\EmployerSubmissionStatus;
use App\Enums\FileLifecycleStatus;
use App\Enums\InterviewLifecycleStatus;
use App\Enums\InterviewOutcome;
use App\Enums\InvestorInterestStatus;
use App\Enums\InvoiceStatus;
use App\Enums\JobPublishingStatus;
use App\Enums\PermissionName;
use App\Enums\PlacementStatus;
use App\Enums\ReferralConversionType;
use App\Enums\RelationshipReviewDirection;
use App\Enums\RelationshipReviewStatus;
use App\Enums\ReplacementStatus;
use App\Enums\ScreeningDecision;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Http\Requests\Admin\AnalyticsPeriodRequest;
use App\Models\User;
use App\Support\Money;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OperationalAnalyticsService
{
    private CarbonInterface $start;

    private CarbonInterface $end;

    private string $fromDate;

    private string $toDate;

    /**
     * @param  array{from?: string|null, to?: string|null}  $input
     * @return array<string, mixed>
     */
    public function payload(User $user, array $input = []): array
    {
        $period = $this->resolvePeriod($input);
        $this->start = $period['start'];
        $this->end = $period['end'];
        $this->fromDate = $period['from'];
        $this->toDate = $period['to'];

        $canJobs = $this->canSee($user, PermissionName::JobsView);
        $canApplications = $this->canSee($user, PermissionName::ApplicationsView);
        $canCandidates = $this->canSee($user, PermissionName::CandidatesView);
        $canSubmissions = $this->canSee($user, PermissionName::SubmissionsView);
        $canPlacements = $this->canSee($user, PermissionName::PlacementsView);
        $canEmployees = $this->canSee($user, PermissionName::EmployeesView);
        $canReplacements = $this->canSee($user, PermissionName::ReplacementsView);
        $canEmployers = $this->canSee($user, PermissionName::EmployersView);
        $canFinance = $this->canSee($user, PermissionName::FinanceView);
        $canCampaigns = $this->canSee($user, PermissionName::CampaignsView);
        $canInvestors = $this->canSee($user, PermissionName::InvestorsView);
        $canUsers = $this->canSee($user, PermissionName::UsersView);

        $payload = [
            'period' => [
                'from' => $period['from'],
                'to' => $period['to'],
                'timezone' => (string) config('app.timezone'),
                'is_default' => $period['is_default'],
                'max_span_days' => AnalyticsPeriodRequest::MAX_SPAN_DAYS,
                'definition' => 'Inclusive calendar dates in the application timezone. Default is the first day of the current month through today.',
                'presets' => $period['presets'],
            ],
            'permissions' => [
                'jobs' => $canJobs,
                'applications' => $canApplications,
                'candidates' => $canCandidates,
                'submissions' => $canSubmissions,
                'placements' => $canPlacements,
                'employees' => $canEmployees,
                'replacements' => $canReplacements,
                'employers' => $canEmployers,
                'finance' => $canFinance,
                'campaigns' => $canCampaigns,
                'investors' => $canInvestors,
                'users' => $canUsers,
            ],
        ];

        $overview = $this->overview($canApplications, $canSubmissions, $canPlacements, $canEmployees, $canReplacements);
        if ($overview !== null) {
            $payload['overview'] = $overview;
        }

        if ($canApplications || $canSubmissions || $canPlacements || $canEmployees) {
            $payload['recruitment'] = $this->recruitment($canApplications, $canSubmissions, $canPlacements, $canEmployees);
        }

        $conversions = $this->conversions($canSubmissions, $canPlacements, $canEmployees);
        if ($conversions !== null) {
            $payload['conversions'] = $conversions;
        }

        $timeMetrics = $this->timeMetrics($canApplications, $canSubmissions, $canPlacements, $canEmployees);
        if ($timeMetrics !== null) {
            $payload['timeMetrics'] = $timeMetrics;
        }

        if ($canJobs) {
            $payload['jobs'] = $this->jobs($canApplications, $canSubmissions, $canPlacements, $canEmployees);
        }

        if ($canEmployers) {
            $payload['employers'] = $this->employers($canJobs, $canApplications, $canSubmissions, $canPlacements, $canEmployees);
            $payload['crm'] = $this->crm();
            $payload['partners'] = $this->partners($canFinance);
            $payload['documents'] = $this->documents();
        }

        if ($canCandidates) {
            $payload['candidates'] = $this->candidates($canApplications, $canPlacements, $canEmployees);
        }

        if ($canApplications) {
            $payload['screening'] = $this->screening();
            $payload['interviews'] = $this->interviews();
        }

        if ($canSubmissions) {
            $payload['submissions'] = $this->submissions();
        }

        if ($canPlacements || $canEmployees) {
            $payload['placements'] = $this->placements($canPlacements, $canEmployees);
        }

        if ($canReplacements) {
            $payload['replacements'] = $this->replacements($canEmployees);
        }

        if ($canInvestors) {
            $payload['investors'] = $this->investors();
        }

        if ($canCampaigns) {
            $payload['campaigns'] = $this->campaigns();
        }

        if ($canEmployees) {
            $payload['reviews'] = $this->reviews();
        }

        if ($canFinance) {
            $payload['finance'] = $this->finance();
        }

        return $payload;
    }

    /**
     * @param  array{from?: string|null, to?: string|null}  $input
     * @return array{from: string, to: string, start: CarbonInterface, end: CarbonInterface, is_default: bool, presets: list<array{id: string, label: string, from: string, to: string}>}
     */
    private function resolvePeriod(array $input): array
    {
        $timezone = (string) config('app.timezone');
        $today = Carbon::now($timezone)->startOfDay();
        $defaultFrom = $today->copy()->startOfMonth();
        $isDefault = blank($input['from'] ?? null) && blank($input['to'] ?? null);

        $from = $isDefault
            ? $defaultFrom->toDateString()
            : Carbon::parse((string) $input['from'], $timezone)->toDateString();
        $to = $isDefault
            ? $today->toDateString()
            : Carbon::parse((string) $input['to'], $timezone)->toDateString();

        $start = Carbon::parse($from, $timezone)->startOfDay();
        $end = Carbon::parse($to, $timezone)->endOfDay();

        $weekStart = $today->copy()->startOfWeek(CarbonInterface::MONDAY);
        $lastMonthStart = $today->copy()->subMonthNoOverflow()->startOfMonth();
        $lastMonthEnd = $today->copy()->subMonthNoOverflow()->endOfMonth();

        return [
            'from' => $from,
            'to' => $to,
            'start' => $start,
            'end' => $end,
            'is_default' => $isDefault,
            'presets' => [
                ['id' => 'today', 'label' => 'Today', 'from' => $today->toDateString(), 'to' => $today->toDateString()],
                ['id' => 'this_week', 'label' => 'This week', 'from' => $weekStart->toDateString(), 'to' => $today->toDateString()],
                ['id' => 'this_month', 'label' => 'This month', 'from' => $defaultFrom->toDateString(), 'to' => $today->toDateString()],
                ['id' => 'last_month', 'label' => 'Last month', 'from' => $lastMonthStart->toDateString(), 'to' => $lastMonthEnd->toDateString()],
                ['id' => 'ytd', 'label' => 'YTD', 'from' => $today->copy()->startOfYear()->toDateString(), 'to' => $today->toDateString()],
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function overview(
        bool $canApplications,
        bool $canSubmissions,
        bool $canPlacements,
        bool $canEmployees,
        bool $canReplacements,
    ): ?array {
        $metrics = [];

        if ($canApplications) {
            $metrics['applications'] = $this->periodCount('applications', 'created_at', 'Applications created in the selected period.');
        }
        if ($canSubmissions) {
            $metrics['submissions'] = $this->periodCount('employer_submissions', 'submitted_at', 'Employer submissions with submitted_at in the selected period.');
            $metrics['selected'] = $this->selectedSubmissionsMetric();
        }
        if ($canPlacements) {
            $metrics['placements'] = $this->periodCount('placements', 'created_at', 'Placements created in the selected period.');
        }
        if ($canEmployees) {
            $metrics['joined_employees'] = $this->periodCount('employees', 'joined_at', 'Employees with joined_at in the selected period.');
        }
        if ($canReplacements) {
            $metrics['replacements'] = $this->periodCount('replacements', 'requested_at', 'Replacement cases with requested_at in the selected period.');
        }

        return $metrics === [] ? null : $metrics;
    }

    /**
     * @return array<string, mixed>
     */
    private function recruitment(
        bool $canApplications,
        bool $canSubmissions,
        bool $canPlacements,
        bool $canEmployees,
    ): array {
        $data = [
            'note' => 'Period counts use the event timestamp named in each metric. Missing timestamps are excluded, not inferred.',
        ];

        if ($canApplications) {
            $data['applications'] = $this->periodCount('applications', 'created_at', 'Applications created in the selected period.');
            $data['shortlisted'] = $this->metric(
                $this->countTable('application_screenings', function (Builder $query): void {
                    $this->inPeriod($query, 'shortlisted_at');
                }),
                'period',
                'Screening records with shortlisted_at in the selected period. Applications shortlisted without shortlisted_at are omitted.',
            );
        }

        if ($canSubmissions) {
            $data['submissions'] = $this->periodCount('employer_submissions', 'submitted_at', 'Submissions with submitted_at in the selected period.');
            $data['selected_submissions'] = $this->selectedSubmissionsMetric();
        }

        if ($canPlacements) {
            $data['placements'] = $this->periodCount('placements', 'created_at', 'Placements created in the selected period.');
        }

        if ($canEmployees) {
            $data['joined_employees'] = $this->periodCount('employees', 'joined_at', 'Employees with joined_at in the selected period.');
        }

        return $data;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function conversions(bool $canSubmissions, bool $canPlacements, bool $canEmployees): ?array
    {
        $data = [
            'note' => 'These are pairwise ratios of period counts, not a single-cohort funnel from application through join.',
        ];
        $has = false;

        if ($canSubmissions) {
            $submitted = $this->countTable('employer_submissions', function (Builder $query): void {
                $this->inPeriod($query, 'submitted_at');
            });
            $selected = $this->countTable('employer_submissions', function (Builder $query): void {
                $this->inPeriod($query, 'submitted_at');
                $query->where('status', EmployerSubmissionStatus::Selected->value);
            });
            $data['submission_to_selection'] = $this->conversion(
                label: 'Submission → Selection',
                formula: 'Selected submissions ÷ Submitted submissions × 100',
                numerator: $selected,
                denominator: $submitted,
                numeratorLabel: 'Selected submissions',
                denominatorLabel: 'Submitted submissions',
                emptyReason: 'No submitted submissions in the selected period.',
            );
            $has = true;
        }

        if ($canPlacements && $canEmployees) {
            $placements = $this->countTable('placements', function (Builder $query): void {
                $this->inPeriod($query, 'created_at');
            });
            $joined = $this->countTable('employees', function (Builder $query): void {
                $this->inPeriod($query, 'joined_at');
            });
            $data['placement_to_join'] = $this->conversion(
                label: 'Placement → Join',
                formula: 'Joined employees ÷ Placements × 100',
                numerator: $joined,
                denominator: $placements,
                numeratorLabel: 'Joined employees',
                denominatorLabel: 'Placements',
                emptyReason: 'No placements in the selected period.',
            );
            $has = true;
        }

        return $has ? $data : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function timeMetrics(
        bool $canApplications,
        bool $canSubmissions,
        bool $canPlacements,
        bool $canEmployees,
    ): ?array {
        if (! $canApplications || ! $this->hasTable('applications')) {
            return null;
        }

        $data = [
            'note' => 'Averages include only records where both endpoints exist. Record-update time is never used as stage-entry time. Median is omitted because it is not portable across SQLite and MySQL here.',
            'primary' => 'Application → Join',
        ];
        $has = false;

        if ($canSubmissions && $this->hasTable('employer_submissions')) {
            $data['time_to_submission'] = $this->averageDays(
                DB::table('employer_submissions')
                    ->join('applications', 'applications.id', '=', 'employer_submissions.application_id')
                    ->whereNotNull('employer_submissions.submitted_at')
                    ->whereNotNull('applications.created_at')
                    ->whereColumn('employer_submissions.submitted_at', '>=', 'applications.created_at'),
                'employer_submissions.submitted_at',
                'applications.created_at',
                'employer_submissions.submitted_at',
                'Average days from application created_at to employer_submissions.submitted_at for submissions in the selected period.',
            );
            $has = true;

            $data['time_to_selection'] = $this->averageDays(
                DB::table('employer_submissions')
                    ->join('applications', 'applications.id', '=', 'employer_submissions.application_id')
                    ->where('employer_submissions.status', EmployerSubmissionStatus::Selected->value)
                    ->whereNotNull('employer_submissions.decided_at')
                    ->whereNotNull('applications.created_at')
                    ->whereColumn('employer_submissions.decided_at', '>=', 'applications.created_at'),
                'employer_submissions.decided_at',
                'applications.created_at',
                'employer_submissions.decided_at',
                'Average days from application created_at to employer_submissions.decided_at for selected submissions in the selected period. Submissions without decided_at are excluded.',
            );
        }

        if ($this->hasTable('application_interviews')) {
            $firstInterviews = DB::table('application_interviews')
                ->select('application_id', DB::raw('MIN(scheduled_at) as first_scheduled'))
                ->whereNotNull('scheduled_at')
                ->groupBy('application_id');

            $data['time_to_interview'] = $this->averageDays(
                DB::table('applications')
                    ->joinSub($firstInterviews, 'first_interviews', 'first_interviews.application_id', '=', 'applications.id')
                    ->whereNotNull('applications.created_at')
                    ->whereColumn('first_interviews.first_scheduled', '>=', 'applications.created_at'),
                'first_interviews.first_scheduled',
                'applications.created_at',
                'first_interviews.first_scheduled',
                'Average days from application created_at to the first interview scheduled_at. Interviews without scheduled_at are excluded. There is no completed_at column.',
            );
            $has = true;
        }

        if ($canPlacements && $this->hasTable('placements')) {
            $data['time_to_placement'] = $this->averageDays(
                DB::table('placements')
                    ->join('applications', 'applications.id', '=', 'placements.application_id')
                    ->whereNotNull('placements.created_at')
                    ->whereNotNull('applications.created_at')
                    ->whereColumn('placements.created_at', '>=', 'applications.created_at'),
                'placements.created_at',
                'applications.created_at',
                'placements.created_at',
                'Average days from application created_at to placements.created_at for placements created in the selected period.',
            );
            $has = true;
        }

        if ($canEmployees && $this->hasTable('employees')) {
            $data['time_to_join'] = $this->averageDays(
                DB::table('employees')
                    ->join('applications', 'applications.id', '=', 'employees.application_id')
                    ->whereNotNull('employees.joined_at')
                    ->whereNotNull('applications.created_at')
                    ->whereColumn('employees.joined_at', '>=', 'applications.created_at'),
                'employees.joined_at',
                'applications.created_at',
                'employees.joined_at',
                'Application → Join. Average days from application created_at to employees.joined_at. Employees without joined_at are excluded.',
            );
            $has = true;
        }

        return $has ? $data : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function jobs(
        bool $canApplications,
        bool $canSubmissions,
        bool $canPlacements,
        bool $canEmployees,
    ): array {
        $publishing = $this->countsBy('job_listings', 'publishing_status');

        $data = [
            'created' => $this->periodCount('job_listings', 'created_at', 'Jobs created in the selected period.'),
            'snapshot' => [
                'kind' => 'snapshot',
                'definition' => 'Current job_listings rows. Not limited to the selected period.',
                'active' => $this->countTable('job_listings', fn (Builder $query) => $query->where('active', true)),
                'published' => (int) ($publishing[JobPublishingStatus::Published->value] ?? 0),
                'draft' => (int) ($publishing[JobPublishingStatus::Draft->value] ?? 0),
                'paused' => (int) ($publishing[JobPublishingStatus::Paused->value] ?? 0),
                'closed' => (int) ($publishing[JobPublishingStatus::Closed->value] ?? 0),
                'archived' => (int) ($publishing[JobPublishingStatus::Archived->value] ?? 0),
            ],
            'by_industry' => $this->classifiedBreakdown(
                'job_listings',
                'industry_id',
                'industries',
                'Jobs created in the selected period grouped by classified industry.',
            ),
            'by_location' => $this->classifiedBreakdown(
                'job_listings',
                'location_id',
                'locations',
                'Jobs created in the selected period grouped by classified location.',
            ),
            'by_employment_type' => $this->classifiedBreakdown(
                'job_listings',
                'employment_type_id',
                'employment_types',
                'Jobs created in the selected period grouped by classified employment type.',
            ),
        ];

        if ($canApplications) {
            $data['applications_per_job'] = $this->perJobCounts('applications', 'created_at', 'Applications created in the selected period, top jobs.');
        }
        if ($canSubmissions) {
            $data['submissions_per_job'] = $this->perJobCounts('employer_submissions', 'submitted_at', 'Submissions with submitted_at in the selected period, top jobs.');
        }
        if ($canPlacements) {
            $data['placements_per_job'] = $this->perJobCounts('placements', 'created_at', 'Placements created in the selected period, top jobs.');
        }
        if ($canEmployees) {
            $data['joins_per_job'] = $this->perJobCounts('employees', 'joined_at', 'Employees with joined_at in the selected period, top jobs.');
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function employers(
        bool $canJobs,
        bool $canApplications,
        bool $canSubmissions,
        bool $canPlacements,
        bool $canEmployees,
    ): array {
        $data = [
            'created' => $this->periodCount('employer_profiles', 'created_at', 'Employer profiles created in the selected period.'),
            'snapshot' => [
                'kind' => 'snapshot',
                'definition' => 'Current employer_profiles rows. Verified means job_posting_approved_at is set. Restricted means posting_restricted_at is set.',
                'total' => $this->countTable('employer_profiles'),
                'verified' => $this->countTable('employer_profiles', fn (Builder $query) => $query->whereNotNull('job_posting_approved_at')),
                'restricted' => $this->countTable('employer_profiles', fn (Builder $query) => $query->whereNotNull('posting_restricted_at')),
            ],
        ];

        if ($canJobs) {
            $data['jobs_per_employer'] = $this->perEmployerCounts('job_listings', 'created_at', 'employer_id', 'Jobs created in the selected period, top employers.');
        }
        if ($canApplications && $this->hasTable('applications') && $this->hasTable('job_listings')) {
            $data['applications_per_employer'] = $this->namedCounts(
                DB::table('applications')
                    ->join('job_listings', 'job_listings.id', '=', 'applications.job_id')
                    ->join('employer_profiles', 'employer_profiles.id', '=', 'job_listings.employer_id')
                    ->whereNotNull('job_listings.employer_id'),
                'applications.created_at',
                'employer_profiles.id',
                'employer_profiles.company_name',
                'Applications created in the selected period, top employers.',
            );
        }
        if ($canSubmissions) {
            $data['submissions_per_employer'] = $this->perEmployerCounts('employer_submissions', 'submitted_at', 'employer_profile_id', 'Submissions with submitted_at in the selected period, top employers.');
        }
        if ($canPlacements) {
            $data['placements_per_employer'] = $this->perEmployerCounts('placements', 'created_at', 'employer_profile_id', 'Placements created in the selected period, top employers.');
        }
        if ($canEmployees) {
            $data['joins_per_employer'] = $this->perEmployerCounts('employees', 'joined_at', 'employer_profile_id', 'Employees with joined_at in the selected period, top employers.');
            $data['repeat_hiring'] = $this->repeatHiring();
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function candidates(bool $canApplications, bool $canPlacements, bool $canEmployees): array
    {
        $data = [
            'registrations' => $this->periodCount('candidate_profiles', 'created_at', 'Candidate profiles created in the selected period.'),
            'snapshot' => [
                'kind' => 'snapshot',
                'definition' => 'Current candidate users with account_status active. Aggregate only.',
                'active' => $this->hasTable('users')
                    ? $this->countTable('users', function (Builder $query): void {
                        $query->where('role', UserRole::Candidate->value)
                            ->where('account_status', AccountStatus::Active->value);
                    })
                    : 0,
            ],
            'by_location' => $this->classifiedBreakdown(
                'candidate_profiles',
                'location_id',
                'locations',
                'Candidate profiles created in the selected period grouped by classified location.',
            ),
            'by_industry' => $this->classifiedBreakdown(
                'candidate_profiles',
                'industry_id',
                'industries',
                'Candidate profiles created in the selected period grouped by classified industry.',
            ),
            'by_experience' => $this->classifiedBreakdown(
                'candidate_profiles',
                'experience_range_id',
                'experience_ranges',
                'Candidate profiles created in the selected period grouped by classified experience range.',
            ),
        ];

        if ($canApplications) {
            $data['applications'] = $this->periodCount('applications', 'created_at', 'Applications created in the selected period.');
        }
        if ($canPlacements) {
            $data['placements'] = $this->periodCount('placements', 'created_at', 'Placements created in the selected period.');
        }
        if ($canEmployees) {
            $data['joined'] = $this->periodCount('employees', 'joined_at', 'Employees with joined_at in the selected period.');
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function screening(): array
    {
        $byDecision = $this->countsBy('application_screenings', 'decision', function (Builder $query): void {
            $this->inPeriod($query, 'screened_at');
        });
        $pendingSnapshot = $this->countsBy('application_screenings', 'decision');

        return [
            'volume' => $this->periodCount('application_screenings', 'screened_at', 'Screening records with screened_at in the selected period.'),
            'pending' => $this->metric(
                (int) ($pendingSnapshot[ScreeningDecision::Pending->value] ?? 0),
                'snapshot',
                'Current screening rows with decision pending.',
            ),
            'suitable' => $this->metric(
                (int) ($byDecision[ScreeningDecision::Suitable->value] ?? 0),
                'period',
                'Screening records with screened_at in the selected period and decision suitable.',
            ),
            'not_suitable' => $this->metric(
                (int) ($byDecision[ScreeningDecision::NotSuitable->value] ?? 0),
                'period',
                'Screening records with screened_at in the selected period and decision not suitable.',
            ),
            'shortlisted' => $this->metric(
                $this->countTable('application_screenings', function (Builder $query): void {
                    $this->inPeriod($query, 'shortlisted_at');
                }),
                'period',
                'Screening records with shortlisted_at in the selected period.',
            ),
            'turnaround' => $this->hasTable('application_screenings') && $this->hasTable('applications')
                ? $this->averageDays(
                    DB::table('application_screenings')
                        ->join('applications', 'applications.id', '=', 'application_screenings.application_id')
                        ->whereNotNull('application_screenings.screened_at')
                        ->whereNotNull('applications.created_at')
                        ->whereColumn('application_screenings.screened_at', '>=', 'applications.created_at'),
                    'application_screenings.screened_at',
                    'applications.created_at',
                    'application_screenings.screened_at',
                    'Average days from application created_at to screened_at for screenings in the selected period.',
                )
                : $this->emptyTimeMetric('application_screenings or applications table is unavailable.'),
            'screened_by' => $this->staffWorkload(
                'application_screenings',
                'screened_by',
                'screened_at',
                'Screenings with screened_at in the selected period grouped by screened_by. Names are staff only.',
            ),
            'note' => 'Match % is decision support only and is not included. Screening factors are not exposed.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function interviews(): array
    {
        $outcomes = $this->countsBy('application_interviews', 'outcome', function (Builder $query): void {
            $this->inPeriod($query, 'scheduled_at');
        });
        $lifecycle = $this->countsBy('application_interviews', 'lifecycle_status', function (Builder $query): void {
            $this->inPeriod($query, 'scheduled_at');
        });

        $scorecardSubmitted = $this->countTable('interview_scorecards', function (Builder $query): void {
            $this->inPeriod($query, 'submitted_at');
        });
        $pendingScorecards = 0;
        if ($this->hasTable('application_interviews')) {
            $query = DB::table('application_interviews')->whereNotNull('application_interviews.scheduled_at');
            $this->inPeriod($query, 'application_interviews.scheduled_at');
            if ($this->hasTable('interview_scorecards')) {
                $query->leftJoin('interview_scorecards', 'interview_scorecards.application_interview_id', '=', 'application_interviews.id')
                    ->whereNull('interview_scorecards.id');
            }
            $pendingScorecards = (int) $query->count();
        }

        return [
            'scheduled' => $this->periodCount('application_interviews', 'scheduled_at', 'Interviews with scheduled_at in the selected period.'),
            'confirmed' => $this->periodCount('application_interviews', 'confirmed_at', 'Interviews with confirmed_at in the selected period.'),
            'cancelled' => $this->periodCount('application_interviews', 'cancelled_at', 'Interviews with cancelled_at in the selected period.'),
            'lifecycle_in_period' => [
                'kind' => 'period',
                'definition' => 'Interviews whose scheduled_at falls in the selected period, grouped by current lifecycle_status. There is no completed_at column; completed is a status count only.',
                'scheduled' => (int) ($lifecycle[InterviewLifecycleStatus::Scheduled->value] ?? 0),
                'confirmed' => (int) ($lifecycle[InterviewLifecycleStatus::Confirmed->value] ?? 0),
                'rescheduled' => (int) ($lifecycle[InterviewLifecycleStatus::Rescheduled->value] ?? 0),
                'cancelled' => (int) ($lifecycle[InterviewLifecycleStatus::Cancelled->value] ?? 0),
                'completed' => (int) ($lifecycle[InterviewLifecycleStatus::Completed->value] ?? 0),
            ],
            'outcomes' => [
                'kind' => 'period',
                'definition' => 'Interviews whose scheduled_at falls in the selected period, grouped by outcome. Scorecard notes and scores are not included.',
                'pending' => (int) ($outcomes[InterviewOutcome::Pending->value] ?? 0),
                'attended' => (int) ($outcomes[InterviewOutcome::Attended->value] ?? 0),
                'no_show' => (int) ($outcomes[InterviewOutcome::NoShow->value] ?? 0),
                'passed' => (int) ($outcomes[InterviewOutcome::Passed->value] ?? 0),
                'failed' => (int) ($outcomes[InterviewOutcome::Failed->value] ?? 0),
            ],
            'scorecards' => [
                'submitted' => $this->metric($scorecardSubmitted, 'period', 'Scorecards with submitted_at in the selected period. Count only; scorecard notes and scores are omitted.'),
                'pending' => $this->metric($pendingScorecards, 'period', 'Interviews scheduled in the selected period that have no scorecard row.'),
            ],
            'interviewer_workload' => $this->staffWorkload(
                'application_interviews',
                'interviewer_user_id',
                'scheduled_at',
                'Interviews with scheduled_at in the selected period grouped by interviewer_user_id.',
            ),
            'note' => 'Interview → selection conversion is omitted because the relationship cannot be established from a reliable shared timestamp.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function submissions(): array
    {
        $byStatus = $this->countsBy('employer_submissions', 'status', function (Builder $query): void {
            $this->inPeriod($query, 'submitted_at');
        });
        $submitted = $this->countTable('employer_submissions', function (Builder $query): void {
            $this->inPeriod($query, 'submitted_at');
        });
        $decided = $this->countTable('employer_submissions', function (Builder $query): void {
            $this->inPeriod($query, 'submitted_at');
            $query->whereNotNull('decided_at');
        });

        return [
            'submitted' => $this->metric($submitted, 'period', 'Submissions with submitted_at in the selected period.'),
            'under_review' => $this->metric((int) ($byStatus[EmployerSubmissionStatus::UnderReview->value] ?? 0), 'period', 'Submissions submitted in the selected period currently Under Review.'),
            'selected' => $this->metric((int) ($byStatus[EmployerSubmissionStatus::Selected->value] ?? 0), 'period', 'Submissions submitted in the selected period currently Selected.'),
            'rejected' => $this->metric((int) ($byStatus[EmployerSubmissionStatus::Rejected->value] ?? 0), 'period', 'Submissions submitted in the selected period currently Rejected.'),
            'on_hold' => $this->metric((int) ($byStatus[EmployerSubmissionStatus::OnHold->value] ?? 0), 'period', 'Submissions submitted in the selected period currently On Hold.'),
            'decision_rate' => $this->conversion(
                label: 'Submission decision rate',
                formula: 'Decided submissions ÷ Submitted submissions × 100',
                numerator: $decided,
                denominator: $submitted,
                numeratorLabel: 'Decided submissions',
                denominatorLabel: 'Submitted submissions',
                emptyReason: 'No submitted submissions in the selected period.',
            ),
            'note' => 'Submission decision rate is not overall hiring conversion.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function placements(bool $canPlacements, bool $canEmployees): array
    {
        $data = [
            'joining_conversion_note' => 'Placement → Join uses period counts of placements created and employees joined. It is not inferred employment status after join.',
        ];

        if ($canPlacements) {
            $byStatus = $this->countsBy('placements', 'status', function (Builder $query): void {
                $this->inPeriod($query, 'created_at');
            });
            $data['count'] = $this->periodCount('placements', 'created_at', 'Placements created in the selected period.');
            $data['by_status'] = [
                'kind' => 'period',
                'definition' => 'Placements created in the selected period grouped by current status.',
                'open' => (int) ($byStatus[PlacementStatus::Open->value] ?? 0),
                'offered' => (int) ($byStatus[PlacementStatus::Offered->value] ?? 0),
                'accepted' => (int) ($byStatus[PlacementStatus::Accepted->value] ?? 0),
                'declined' => (int) ($byStatus[PlacementStatus::Declined->value] ?? 0),
                'joining_confirmed' => (int) ($byStatus[PlacementStatus::JoiningConfirmed->value] ?? 0),
                'joined' => (int) ($byStatus[PlacementStatus::Joined->value] ?? 0),
                'withdrawn' => (int) ($byStatus[PlacementStatus::Withdrawn->value] ?? 0),
            ];
        }

        if ($canEmployees) {
            $data['joined'] = $this->periodCount('employees', 'joined_at', 'Employees with joined_at in the selected period.');
            $data['joins_by_employer'] = $this->perEmployerCounts('employees', 'joined_at', 'employer_profile_id', 'Employees with joined_at in the selected period, top employers.');
            $data['joins_by_job'] = $this->perJobCounts('employees', 'joined_at', 'Employees with joined_at in the selected period, top jobs.');
        }

        if ($canPlacements && $canEmployees) {
            $placements = $this->countTable('placements', function (Builder $query): void {
                $this->inPeriod($query, 'created_at');
            });
            $joined = $this->countTable('employees', function (Builder $query): void {
                $this->inPeriod($query, 'joined_at');
            });
            $data['joining_conversion'] = $this->conversion(
                label: 'Placement → Join',
                formula: 'Joined employees ÷ Placements × 100',
                numerator: $joined,
                denominator: $placements,
                numeratorLabel: 'Joined employees',
                denominatorLabel: 'Placements',
                emptyReason: 'No placements in the selected period.',
            );
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function replacements(bool $canEmployees): array
    {
        $byStatus = $this->countsBy('replacements', 'status', function (Builder $query): void {
            $this->inPeriod($query, 'requested_at');
        });
        $byReason = $this->countsBy('replacements', 'reason', function (Builder $query): void {
            $this->inPeriod($query, 'requested_at');
        });
        $cases = $this->countTable('replacements', function (Builder $query): void {
            $this->inPeriod($query, 'requested_at');
        });

        $data = [
            'cases' => $this->metric($cases, 'period', 'Replacement cases with requested_at in the selected period.'),
            'requested' => $this->metric((int) ($byStatus[ReplacementStatus::Requested->value] ?? 0), 'period', 'Replacement cases requested in the selected period currently Requested.'),
            'completed' => $this->periodCount('replacements', 'completed_at', 'Replacement cases with completed_at in the selected period.'),
            'cancelled' => $this->periodCount('replacements', 'cancelled_at', 'Replacement cases with cancelled_at in the selected period.'),
            'by_reason' => [
                'kind' => 'period',
                'definition' => 'Replacement cases with requested_at in the selected period grouped by structured ReplacementReason.',
                'rows' => collect($byReason)
                    ->map(fn (int $count, string $reason) => ['reason' => $reason, 'count' => $count])
                    ->values()
                    ->all(),
            ],
            'by_employer' => $this->perEmployerCounts('replacements', 'requested_at', 'employer_profile_id', 'Replacement cases requested in the selected period, top employers.'),
            'by_job' => $this->perJobCounts('replacements', 'requested_at', 'Replacement cases requested in the selected period, top jobs.'),
            'turnaround' => $this->hasTable('replacements')
                ? $this->averageDays(
                    DB::table('replacements')
                        ->whereNotNull('completed_at')
                        ->whereNotNull('requested_at')
                        ->whereColumn('completed_at', '>=', 'requested_at'),
                    'completed_at',
                    'requested_at',
                    'completed_at',
                    'Average days from requested_at to completed_at for replacements completed in the selected period.',
                )
                : $this->emptyTimeMetric('replacements table is unavailable.'),
        ];

        if ($canEmployees) {
            $joined = $this->countTable('employees', function (Builder $query): void {
                $this->inPeriod($query, 'joined_at');
            });
            $data['replacement_rate'] = $this->conversion(
                label: 'Replacement rate',
                formula: 'Replacement cases ÷ Joined employees × 100',
                numerator: $cases,
                denominator: $joined,
                numeratorLabel: 'Replacement cases',
                denominatorLabel: 'Joined employees',
                emptyReason: 'No joined employees in the selected period.',
            );
            $data['replacement_rate']['kind'] = 'period';
            $data['replacement_rate']['definition'] = 'Period metric: replacement cases requested in the period divided by employees who joined in the period.';
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function crm(): array
    {
        $openStatuses = array_map(
            fn (CrmOpportunityStatus $status) => $status->value,
            array_values(array_filter(CrmOpportunityStatus::cases(), fn (CrmOpportunityStatus $status) => $status->isOpen())),
        );

        $assigned = $this->hasTable('crm_leads')
            ? DB::table('crm_leads')
                ->select('assigned_to', DB::raw('COUNT(*) as aggregate'))
                ->whereNotNull('assigned_to')
                ->groupBy('assigned_to')
                ->orderByDesc('aggregate')
                ->limit(10)
                ->get()
            : collect();

        return [
            'leads' => $this->periodCount('crm_leads', 'created_at', 'CRM leads created in the selected period.'),
            'opportunities' => $this->periodCount('crm_opportunities', 'created_at', 'CRM opportunities created in the selected period.'),
            'active_opportunities' => $this->metric(
                $this->countTable('crm_opportunities', fn (Builder $query) => $query->whereIn('status', $openStatuses)),
                'snapshot',
                'Current CRM opportunities that are not converted or active-client.',
            ),
            'converted_opportunities' => $this->periodCount('crm_opportunities', 'converted_at', 'CRM opportunities with converted_at in the selected period.'),
            'assigned_workload' => [
                'kind' => 'snapshot',
                'definition' => 'Current CRM leads grouped by assigned_to. Lead notes and contact details are omitted.',
                'rows' => $this->attachUserNames($assigned, 'assigned_to'),
            ],
            'overdue_tasks' => $this->metric(
                $this->countTable('tasks', function (Builder $query): void {
                    $query->where('status', TaskStatus::Open->value)
                        ->whereNotNull('due_at')
                        ->where('due_at', '<', Carbon::now(config('app.timezone'))->startOfDay())
                        ->where(function (Builder $inner): void {
                            $inner->whereNotNull('crm_lead_id')->orWhereNotNull('crm_opportunity_id');
                        });
                }),
                'snapshot',
                'Open CRM-linked tasks whose due_at is before today. Task body is omitted.',
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function partners(bool $canFinance): array
    {
        $byStatus = $this->countsBy('channel_partners', 'status');
        $agreementStatus = $this->countsBy('channel_partner_agreements', 'status');
        $attributionStatus = $this->countsBy('employer_attributions', 'status', function (Builder $query): void {
            $this->inPeriod($query, 'created_at');
        });

        $data = [
            'partners' => $this->periodCount('channel_partners', 'created_at', 'Channel partners created in the selected period.'),
            'snapshot' => [
                'kind' => 'snapshot',
                'definition' => 'Current channel_partners grouped by status.',
                'pending' => (int) ($byStatus[ChannelPartnerStatus::Pending->value] ?? 0),
                'active' => (int) ($byStatus[ChannelPartnerStatus::Active->value] ?? 0),
                'suspended' => (int) ($byStatus[ChannelPartnerStatus::Suspended->value] ?? 0),
                'total' => $this->countTable('channel_partners'),
            ],
            'agreements' => [
                'created' => $this->periodCount('channel_partner_agreements', 'created_at', 'Partner agreements created in the selected period.'),
                'snapshot_by_status' => $agreementStatus,
            ],
            'attributions' => $this->periodCount('employer_attributions', 'created_at', 'Employer attributions created in the selected period.'),
            'confirmed_attributions' => $this->periodCount('employer_attributions', 'confirmed_at', 'Employer attributions with confirmed_at in the selected period.'),
            'attribution_status_in_period' => [
                'kind' => 'period',
                'pending' => (int) ($attributionStatus[EmployerAttributionStatus::Pending->value] ?? 0),
                'confirmed' => (int) ($attributionStatus[EmployerAttributionStatus::Confirmed->value] ?? 0),
                'disputed' => (int) ($attributionStatus[EmployerAttributionStatus::Disputed->value] ?? 0),
                'void' => (int) ($attributionStatus[EmployerAttributionStatus::Void->value] ?? 0),
            ],
            'partner_generated_employers' => $this->metric(
                $this->hasTable('employer_attributions')
                    ? (int) DB::table('employer_attributions')
                        ->where('status', EmployerAttributionStatus::Confirmed->value)
                        ->whereNotNull('employer_profile_id')
                        ->whereNotNull('confirmed_at')
                        ->where('confirmed_at', '>=', $this->start)
                        ->where('confirmed_at', '<=', $this->end)
                        ->distinct()
                        ->count('employer_profile_id')
                    : 0,
                'period',
                'Distinct employers on confirmed attributions with confirmed_at in the selected period. Partner placement attribution is not first-class and is not inferred.',
            ),
            'note' => 'Partner placement attribution to candidates is not modeled and is not reported.',
        ];

        if ($canFinance) {
            $commissionCounts = $this->countsBy('commission_entries', 'status', function (Builder $query): void {
                $this->inPeriod($query, 'created_at');
            });
            $commissionAmounts = [];
            if ($this->hasTable('commission_entries')) {
                $rows = DB::table('commission_entries')
                    ->select('status', DB::raw('COALESCE(SUM(commission_amount), 0) as total'))
                    ->whereNotNull('created_at')
                    ->where('created_at', '>=', $this->start)
                    ->where('created_at', '<=', $this->end)
                    ->groupBy('status')
                    ->get();
                foreach ($rows as $row) {
                    $commissionAmounts[(string) $row->status] = Money::of($row->total);
                }
            }
            $data['commission'] = [
                'kind' => 'period',
                'definition' => 'Commission entries created in the selected period. Amounts are by entry status, not forecasted revenue.',
                'counts_by_status' => $commissionCounts,
                'amounts_by_status' => [
                    CommissionEntryStatus::Pending->value => $commissionAmounts[CommissionEntryStatus::Pending->value] ?? '0.00',
                    CommissionEntryStatus::Approved->value => $commissionAmounts[CommissionEntryStatus::Approved->value] ?? '0.00',
                    CommissionEntryStatus::Paid->value => $commissionAmounts[CommissionEntryStatus::Paid->value] ?? '0.00',
                    CommissionEntryStatus::Void->value => $commissionAmounts[CommissionEntryStatus::Void->value] ?? '0.00',
                    CommissionEntryStatus::Failed->value => $commissionAmounts[CommissionEntryStatus::Failed->value] ?? '0.00',
                ],
            ];
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function investors(): array
    {
        $byStatus = $this->countsBy('investors', 'status');
        $interestStatus = $this->countsBy('investor_interests', 'status', function (Builder $query): void {
            $this->inPeriod($query, 'created_at');
        });
        $indicated = '0.00';
        if ($this->hasTable('investor_interests')) {
            $indicated = Money::of(DB::table('investor_interests')
                ->where('status', InvestorInterestStatus::Committed->value)
                ->whereNotNull('created_at')
                ->where('created_at', '>=', $this->start)
                ->where('created_at', '<=', $this->end)
                ->sum('indicated_amount'));
        }

        return [
            'investors' => $this->periodCount('investors', 'created_at', 'Investors created in the selected period.'),
            'snapshot' => [
                'kind' => 'snapshot',
                'definition' => 'Current investors grouped by status.',
                'by_status' => $byStatus,
                'total' => $this->countTable('investors'),
            ],
            'opportunities' => $this->periodCount('investment_opportunities', 'created_at', 'Investment opportunities created in the selected period.'),
            'interest_status' => [
                'kind' => 'period',
                'definition' => 'Investor interests created in the selected period grouped by status.',
                'counts' => $interestStatus,
            ],
            'indicated_amount' => [
                'value' => $indicated,
                'kind' => 'period',
                'label' => 'Indicated amount',
                'definition' => 'Sum of indicated_amount on interests created in the selected period with status Committed. This is non-binding indicated intent. It is not invested capital, revenue, or capital raised.',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function campaigns(): array
    {
        $byStatus = $this->countsBy('campaigns', 'status');
        $registrations = $this->countTable('referrals', function (Builder $query): void {
            $this->inPeriod($query, 'created_at');
            $query->where('conversion_type', ReferralConversionType::Registration->value);
        });
        $applications = $this->countTable('referrals', function (Builder $query): void {
            $this->inPeriod($query, 'created_at');
            $query->where('conversion_type', ReferralConversionType::Application->value);
        });

        return [
            'campaigns' => $this->periodCount('campaigns', 'created_at', 'Campaigns created in the selected period.'),
            'active' => $this->metric(
                (int) ($byStatus[CampaignStatus::Active->value] ?? 0),
                'snapshot',
                'Current campaigns with status active.',
            ),
            'share_events' => $this->periodCount('share_events', 'created_at', 'Share events created in the selected period. Channel performance (email/WhatsApp) is not inferred.'),
            'referrals' => $this->periodCount('referrals', 'created_at', 'Referral conversions recorded in the selected period.'),
            'registrations' => $this->metric($registrations, 'period', 'Referral conversions of type registration in the selected period.'),
            'applications' => $this->metric($applications, 'period', 'Referral conversions of type application in the selected period.'),
            'note' => 'Supported conversion types are registration and application only. Placement attribution and multi-touch attribution are not modeled.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function documents(): array
    {
        $lifecycle = $this->countsBy('employer_documents', 'status');
        $verification = $this->countsBy('employer_documents', 'verification_status');
        $today = Carbon::now(config('app.timezone'))->toDateString();
        $soon = Carbon::now(config('app.timezone'))->addDays(30)->toDateString();
        $expired = $this->countTable('employer_documents', function (Builder $query) use ($today): void {
            $query->whereNotNull('expiry_date')->whereDate('expiry_date', '<', $today);
        });
        $expiringSoon = $this->countTable('employer_documents', function (Builder $query) use ($today, $soon): void {
            $query->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '>=', $today)
                ->whereDate('expiry_date', '<=', $soon);
        });

        return [
            'created' => $this->periodCount('employer_documents', 'created_at', 'Employer documents created in the selected period.'),
            'snapshot' => [
                'kind' => 'snapshot',
                'definition' => 'Current employer_documents counts. Filenames, storage paths, and review notes are omitted.',
                'total' => $this->countTable('employer_documents'),
                'active' => (int) ($lifecycle[FileLifecycleStatus::Active->value] ?? 0),
                'archived' => (int) ($lifecycle[FileLifecycleStatus::Archived->value] ?? 0),
                'unverified' => (int) ($verification[DocumentVerificationStatus::Unverified->value] ?? 0),
                'pending' => (int) ($verification[DocumentVerificationStatus::Pending->value] ?? 0),
                'verified' => (int) ($verification[DocumentVerificationStatus::Verified->value] ?? 0),
                'rejected' => (int) ($verification[DocumentVerificationStatus::Rejected->value] ?? 0),
                'expired' => $expired,
                'expiring_soon' => $expiringSoon,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function reviews(): array
    {
        $periodQuery = function (Builder $query): void {
            $this->inPeriod($query, 'created_at');
        };
        $distributionRows = $this->hasTable('relationship_reviews')
            ? DB::table('relationship_reviews')
                ->select('rating', DB::raw('COUNT(*) as aggregate'))
                ->whereNotNull('created_at')
                ->where('created_at', '>=', $this->start)
                ->where('created_at', '<=', $this->end)
                ->groupBy('rating')
                ->pluck('aggregate', 'rating')
            : collect();

        $distribution = [];
        for ($rating = 1; $rating <= 5; $rating++) {
            $distribution[] = [
                'rating' => $rating,
                'count' => (int) ($distributionRows[$rating] ?? 0),
            ];
        }

        $average = null;
        $periodCount = $this->countTable('relationship_reviews', $periodQuery);
        if ($this->hasTable('relationship_reviews') && $periodCount > 0) {
            $average = round((float) DB::table('relationship_reviews')
                ->whereNotNull('created_at')
                ->where('created_at', '>=', $this->start)
                ->where('created_at', '<=', $this->end)
                ->avg('rating'), 2);
        }

        $directions = $this->countsBy('relationship_reviews', 'direction', $periodQuery);

        return [
            'total' => $this->metric($periodCount, 'period', 'Relationship reviews created in the selected period. Review body is omitted.'),
            'average_rating' => [
                'value' => $average,
                'kind' => 'period',
                'definition' => 'Average rating (1–5) of reviews created in the selected period. Null when there are no reviews.',
            ],
            'rating_distribution' => [
                'kind' => 'period',
                'definition' => 'Counts of reviews created in the selected period for ratings 1 through 5.',
                'rows' => $distribution,
            ],
            'employer_to_worker' => $this->metric(
                (int) ($directions[RelationshipReviewDirection::EmployerToWorker->value] ?? 0),
                'period',
                'Employer → Worker reviews created in the selected period.',
            ),
            'worker_to_employer' => $this->metric(
                (int) ($directions[RelationshipReviewDirection::WorkerToEmployer->value] ?? 0),
                'period',
                'Worker → Employer reviews created in the selected period.',
            ),
            'hidden' => $this->metric(
                $this->countTable('relationship_reviews', fn (Builder $query) => $query->where('status', RelationshipReviewStatus::Hidden->value)),
                'snapshot',
                'Current reviews with status hidden.',
            ),
            'note' => 'Reviews remain private. This payload does not include review body, public ratings, or reputation ranking.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function finance(): array
    {
        $billedStatuses = [
            InvoiceStatus::Issued->value,
            InvoiceStatus::PartiallyPaid->value,
            InvoiceStatus::Paid->value,
        ];
        $openStatuses = [
            InvoiceStatus::Issued->value,
            InvoiceStatus::PartiallyPaid->value,
        ];

        $billedAmount = '0.00';
        $billedCount = 0;
        if ($this->hasTable('invoices')) {
            $billedQuery = DB::table('invoices')
                ->whereIn('status', $billedStatuses)
                ->whereNotNull('billed_at')
                ->whereDate('billed_at', '>=', $this->fromDate)
                ->whereDate('billed_at', '<=', $this->toDate);
            $billedAmount = Money::of((clone $billedQuery)->sum('total_amount'));
            $billedCount = (int) (clone $billedQuery)->count();
        }

        $receivedAmount = '0.00';
        $paymentCount = 0;
        if ($this->hasTable('payments')) {
            $paymentQuery = DB::table('payments')
                ->whereNotNull('paid_at')
                ->whereDate('paid_at', '>=', $this->fromDate)
                ->whereDate('paid_at', '<=', $this->toDate);
            $receivedAmount = Money::of((clone $paymentQuery)->sum('amount'));
            $paymentCount = (int) (clone $paymentQuery)->count();
        }

        $openBilled = '0.00';
        $openReceived = '0.00';
        $overdueBilled = '0.00';
        $overdueReceived = '0.00';
        if ($this->hasTable('invoices')) {
            $openBilled = Money::of(DB::table('invoices')->whereIn('status', $openStatuses)->sum('total_amount'));
            $today = Carbon::now(config('app.timezone'))->toDateString();
            $overdueBilled = Money::of(DB::table('invoices')
                ->whereIn('status', $openStatuses)
                ->whereNotNull('due_at')
                ->whereDate('due_at', '<', $today)
                ->sum('total_amount'));
            if ($this->hasTable('payments')) {
                $openReceived = Money::of(DB::table('payments')
                    ->join('invoices', 'invoices.id', '=', 'payments.invoice_id')
                    ->whereIn('invoices.status', $openStatuses)
                    ->sum('payments.amount'));
                $overdueReceived = Money::of(DB::table('payments')
                    ->join('invoices', 'invoices.id', '=', 'payments.invoice_id')
                    ->whereIn('invoices.status', $openStatuses)
                    ->whereNotNull('invoices.due_at')
                    ->whereDate('invoices.due_at', '<', $today)
                    ->sum('payments.amount'));
            }
        }

        $outstanding = Money::sub($openBilled, $openReceived);
        if (Money::cmp($outstanding, '0') < 0) {
            $outstanding = '0.00';
        }
        $overdue = Money::sub($overdueBilled, $overdueReceived);
        if (Money::cmp($overdue, '0') < 0) {
            $overdue = '0.00';
        }

        return [
            'billed_amount' => [
                'value' => $billedAmount,
                'kind' => 'period',
                'definition' => 'SUM(total_amount) of invoices with billed_at in the selected period and status Issued, Partially Paid, or Paid. Draft and Cancelled are excluded.',
            ],
            'received_amount' => [
                'value' => $receivedAmount,
                'kind' => 'period',
                'definition' => 'SUM(amount) of payments with paid_at in the selected period.',
            ],
            'outstanding_amount' => [
                'value' => $outstanding,
                'kind' => 'snapshot',
                'definition' => 'Current open invoices (Issued or Partially Paid) minus payments on those invoices. Not a period metric.',
            ],
            'overdue_amount' => [
                'value' => $overdue,
                'kind' => 'snapshot',
                'definition' => 'Current open invoices with due_at before today, minus payments on those invoices. Not a period metric.',
            ],
            'invoice_count' => $this->metric($billedCount, 'period', 'Billed invoices (Issued, Partially Paid, or Paid) with billed_at in the selected period.'),
            'payment_count' => $this->metric($paymentCount, 'period', 'Payments with paid_at in the selected period.'),
            'note' => 'Gateway refund and failure analytics are not supported. Draft and cancelled invoices are not billed revenue. No forecasting.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function selectedSubmissionsMetric(): array
    {
        return $this->metric(
            $this->countTable('employer_submissions', function (Builder $query): void {
                $this->inPeriod($query, 'submitted_at');
                $query->where('status', EmployerSubmissionStatus::Selected->value);
            }),
            'period',
            'Submissions with submitted_at in the selected period and current status Selected.',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function repeatHiring(): array
    {
        $periodEmployers = 0;
        $snapshotEmployers = 0;
        if ($this->hasTable('employees')) {
            $periodEmployers = (int) DB::table('employees')
                ->select('employer_profile_id')
                ->whereNotNull('joined_at')
                ->whereNotNull('employer_profile_id')
                ->where('joined_at', '>=', $this->start)
                ->where('joined_at', '<=', $this->end)
                ->groupBy('employer_profile_id')
                ->havingRaw('COUNT(*) >= 2')
                ->get()
                ->count();

            $snapshotEmployers = (int) DB::table('employees')
                ->select('employer_profile_id')
                ->whereNotNull('joined_at')
                ->whereNotNull('employer_profile_id')
                ->groupBy('employer_profile_id')
                ->havingRaw('COUNT(*) >= 2')
                ->get()
                ->count();
        }

        return [
            'period' => $this->metric(
                $periodEmployers,
                'period',
                'Employers with two or more employees whose joined_at falls in the selected period. Repeat hiring is based on joined employees, not job postings.',
            ),
            'snapshot' => $this->metric(
                $snapshotEmployers,
                'snapshot',
                'Employers with two or more joined employees in total.',
            ),
        ];
    }

    /**
     * Repeat-hiring uses havingRaw COUNT grouped in SQL. The subsequent get() returns only grouped employer ids, not employee rows.
     *
     * @param  callable(Builder): void|null  $scope
     * @return array<string, int>
     */
    private function countsBy(string $table, string $column, ?callable $scope = null): array
    {
        if (! $this->hasTable($table)) {
            return [];
        }

        $query = DB::table($table);
        if ($scope !== null) {
            $scope($query);
        }

        return $query->select($column, DB::raw('COUNT(*) as aggregate'))
            ->groupBy($column)
            ->pluck('aggregate', $column)
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    /**
     * @param  callable(Builder): void|null  $scope
     */
    private function countTable(string $table, ?callable $scope = null): int
    {
        if (! $this->hasTable($table)) {
            return 0;
        }

        $query = DB::table($table);
        if ($scope !== null) {
            $scope($query);
        }

        return (int) $query->count();
    }

    /**
     * @return array<string, mixed>
     */
    private function periodCount(string $table, string $column, string $definition): array
    {
        return $this->metric(
            $this->countTable($table, function (Builder $query) use ($column): void {
                $this->inPeriod($query, $column);
            }),
            'period',
            $definition,
        );
    }

    private function inPeriod(Builder $query, string $column): void
    {
        $query->whereNotNull($column)
            ->where($column, '>=', $this->start)
            ->where($column, '<=', $this->end);
    }

    /**
     * @return array<string, mixed>
     */
    private function metric(int $value, string $kind, string $definition): array
    {
        return [
            'value' => $value,
            'kind' => $kind,
            'definition' => $definition,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function conversion(
        string $label,
        string $formula,
        int $numerator,
        int $denominator,
        string $numeratorLabel,
        string $denominatorLabel,
        string $emptyReason,
    ): array {
        $rate = $denominator === 0 ? null : round(($numerator / $denominator) * 100, 1);

        return [
            'label' => $label,
            'formula' => $formula,
            'rate' => $rate,
            'numerator' => $numerator,
            'denominator' => $denominator,
            'numerator_label' => $numeratorLabel,
            'denominator_label' => $denominatorLabel,
            'kind' => 'period',
            'empty_reason' => $denominator === 0 ? $emptyReason : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function averageDays(
        Builder $query,
        string $endColumn,
        string $startColumn,
        string $periodColumn,
        string $definition,
    ): array {
        $query->where($periodColumn, '>=', $this->start)
            ->where($periodColumn, '<=', $this->end);

        $eligible = (int) (clone $query)->count();
        if ($eligible === 0) {
            return [
                'average_days' => null,
                'eligible_count' => 0,
                'kind' => 'period',
                'definition' => $definition,
                'empty_reason' => 'No data for selected period',
            ];
        }

        $average = (clone $query)->avg(DB::raw($this->daysDiffSql($endColumn, $startColumn)));

        return [
            'average_days' => $average === null ? null : round((float) $average, 1),
            'eligible_count' => $eligible,
            'kind' => 'period',
            'definition' => $definition,
            'empty_reason' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyTimeMetric(string $reason): array
    {
        return [
            'average_days' => null,
            'eligible_count' => 0,
            'kind' => 'period',
            'definition' => $reason,
            'empty_reason' => 'No data for selected period',
        ];
    }

    private function daysDiffSql(string $endColumn, string $startColumn): string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return '(julianday('.$endColumn.') - julianday('.$startColumn.'))';
        }

        return '(TIMESTAMPDIFF(SECOND, '.$startColumn.', '.$endColumn.') / 86400.0)';
    }

    /**
     * @return array<string, mixed>
     */
    private function classifiedBreakdown(string $table, string $foreignKey, string $lookupTable, string $definition): array
    {
        if (! $this->hasTable($table) || ! $this->hasTable($lookupTable)) {
            return ['kind' => 'period', 'definition' => $definition, 'rows' => []];
        }

        $rows = DB::table($table)
            ->join($lookupTable, $lookupTable.'.id', '=', $table.'.'.$foreignKey)
            ->select($lookupTable.'.id', $lookupTable.'.name', DB::raw('COUNT(*) as aggregate'))
            ->whereNotNull($table.'.'.$foreignKey)
            ->whereNotNull($table.'.created_at')
            ->where($table.'.created_at', '>=', $this->start)
            ->where($table.'.created_at', '<=', $this->end)
            ->groupBy($lookupTable.'.id', $lookupTable.'.name')
            ->orderByDesc('aggregate')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'count' => (int) $row->aggregate,
            ])
            ->all();

        return [
            'kind' => 'period',
            'definition' => $definition,
            'rows' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function perJobCounts(string $table, string $timestamp, string $definition): array
    {
        if (! $this->hasTable($table) || ! $this->hasTable('job_listings')) {
            return ['kind' => 'period', 'definition' => $definition, 'rows' => []];
        }

        $query = DB::table($table)
            ->join('job_listings', 'job_listings.id', '=', $table.'.job_id')
            ->select('job_listings.id', 'job_listings.title', DB::raw('COUNT(*) as aggregate'))
            ->whereNotNull($table.'.job_id');
        $this->inPeriod($query, $table.'.'.$timestamp);

        $rows = $query->groupBy('job_listings.id', 'job_listings.title')
            ->orderByDesc('aggregate')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => (string) $row->title,
                'count' => (int) $row->aggregate,
            ])
            ->all();

        return ['kind' => 'period', 'definition' => $definition, 'rows' => $rows];
    }

    /**
     * @return array<string, mixed>
     */
    private function perEmployerCounts(string $table, string $timestamp, string $employerKey, string $definition): array
    {
        if (! $this->hasTable($table) || ! $this->hasTable('employer_profiles')) {
            return ['kind' => 'period', 'definition' => $definition, 'rows' => []];
        }

        $query = DB::table($table)
            ->join('employer_profiles', 'employer_profiles.id', '=', $table.'.'.$employerKey)
            ->select('employer_profiles.id', 'employer_profiles.company_name', DB::raw('COUNT(*) as aggregate'))
            ->whereNotNull($table.'.'.$employerKey);
        $this->inPeriod($query, $table.'.'.$timestamp);

        return [
            'kind' => 'period',
            'definition' => $definition,
            'rows' => $query->groupBy('employer_profiles.id', 'employer_profiles.company_name')
                ->orderByDesc('aggregate')
                ->limit(10)
                ->get()
                ->map(fn ($row) => [
                    'id' => (int) $row->id,
                    'name' => (string) $row->company_name,
                    'count' => (int) $row->aggregate,
                ])
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function namedCounts(Builder $query, string $timestamp, string $idColumn, string $nameColumn, string $definition): array
    {
        $this->inPeriod($query, $timestamp);

        return [
            'kind' => 'period',
            'definition' => $definition,
            'rows' => $query->select($idColumn.' as id', $nameColumn.' as name', DB::raw('COUNT(*) as aggregate'))
                ->groupBy($idColumn, $nameColumn)
                ->orderByDesc('aggregate')
                ->limit(10)
                ->get()
                ->map(fn ($row) => [
                    'id' => (int) $row->id,
                    'name' => (string) $row->name,
                    'count' => (int) $row->aggregate,
                ])
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function staffWorkload(string $table, string $userColumn, string $timestamp, string $definition): array
    {
        if (! $this->hasTable($table)) {
            return ['kind' => 'period', 'definition' => $definition, 'rows' => []];
        }

        $query = DB::table($table)
            ->select($userColumn, DB::raw('COUNT(*) as aggregate'))
            ->whereNotNull($userColumn);
        $this->inPeriod($query, $timestamp);

        $rows = $query->groupBy($userColumn)
            ->orderByDesc('aggregate')
            ->limit(10)
            ->get();

        return [
            'kind' => 'period',
            'definition' => $definition,
            'rows' => $this->attachUserNames($rows, $userColumn),
        ];
    }

    /**
     * @param  iterable<int, object>  $rows
     * @return list<array{id: int, name: string, count: int}>
     */
    private function attachUserNames(iterable $rows, string $idColumn): array
    {
        $collection = collect($rows);
        $ids = $collection->pluck($idColumn)->filter()->map(fn ($id) => (int) $id)->unique()->values();
        $names = $ids->isEmpty() || ! $this->hasTable('users')
            ? collect()
            : DB::table('users')->whereIn('id', $ids->all())->pluck('name', 'id');

        return $collection->map(fn ($row) => [
            'id' => (int) $row->{$idColumn},
            'name' => (string) ($names[$row->{$idColumn}] ?? 'Staff #'.$row->{$idColumn}),
            'count' => (int) $row->aggregate,
        ])->values()->all();
    }

    private function hasTable(string $table): bool
    {
        return Schema::hasTable($table);
    }

    private function canSee(User $user, PermissionName $permission): bool
    {
        if ($user->hasPermission($permission->value)) {
            return true;
        }

        $manage = match ($permission) {
            PermissionName::JobsView => PermissionName::JobsManage,
            PermissionName::ApplicationsView => PermissionName::ApplicationsManage,
            PermissionName::EmployersView => PermissionName::EmployersManage,
            PermissionName::UsersView => PermissionName::UsersManage,
            PermissionName::CandidatesView => PermissionName::CandidatesManage,
            PermissionName::SubmissionsView => PermissionName::SubmissionsManage,
            PermissionName::PlacementsView => PermissionName::PlacementsManage,
            PermissionName::EmployeesView => PermissionName::EmployeesManage,
            PermissionName::ReplacementsView => PermissionName::ReplacementsManage,
            PermissionName::FinanceView => PermissionName::FinanceManage,
            PermissionName::CampaignsView => PermissionName::CampaignsManage,
            PermissionName::InvestorsView => PermissionName::InvestorsManage,
            default => null,
        };

        return $manage !== null && $user->hasPermission($manage->value);
    }
}
