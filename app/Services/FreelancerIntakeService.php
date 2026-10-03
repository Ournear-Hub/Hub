<?php

namespace App\Services;

use App\Enums\FreelancerGender;
use App\Enums\FreelancerStatus;
use App\Enums\NoticePeriod;
use App\Enums\PreferredWorkType;
use App\Enums\UserRole;
use App\Models\EmploymentType;
use App\Models\ExperienceRange;
use App\Models\Freelancer;
use App\Models\Industry;
use App\Models\JobCategory;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FreelancerIntakeService
{
    public function __construct(
        private FreelancerFileService $files,
        private AdminAlertService $alerts,
    ) {}

    public function token(Request $request): string
    {
        $existing = $request->session()->get('freelancer_submission_token');
        if (is_string($existing) && strlen($existing) === 40) {
            return $existing;
        }

        $token = Str::random(40);
        $request->session()->put('freelancer_submission_token', $token);

        return $token;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function submit(array $validated, ?UploadedFile $resume, ?UploadedFile $certificate, Request $request): Freelancer
    {
        $token = (string) $validated['submission_token'];
        $existing = $this->existingSubmission($token, $validated);
        if ($existing instanceof Freelancer) {
            return $existing;
        }

        $resumeFile = $resume
            ? $this->files->store($resume, 'resumes', $this->files->resumeExtensions(), 'resume')
            : null;
        $certificateFile = $certificate
            ? $this->files->store($certificate, 'certificates', $this->files->documentExtensions(), 'certificate')
            : null;

        $freelancer = DB::transaction(function () use ($validated, $token, $resumeFile, $certificateFile) {
            $freelancer = Freelancer::query()->create([
                'submission_token' => $token,
                'name' => $validated['name'],
                'mobile' => $validated['mobile'],
                'email' => $validated['email'],
                'gender' => $validated['gender'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'current_location_id' => $validated['current_location_id'],
                'primary_skill' => $validated['primary_skill'],
                'current_designation' => $validated['current_designation'] ?? null,
                'current_company' => $validated['current_company'] ?? null,
                'industry_id' => $validated['industry_id'],
                'job_category_id' => $validated['job_category_id'] ?? null,
                'experience_range_id' => $validated['experience_range_id'],
                'employment_type_id' => $validated['employment_type_id'] ?? null,
                'preferred_work_type' => $validated['preferred_work_type'] ?? null,
                'expected_rate' => $validated['expected_rate'] ?? null,
                'notice_period' => $validated['notice_period'] ?? null,
                'availability' => $validated['availability'] ?? null,
                'skills' => $this->skillList($validated['skills'] ?? null),
                'summary' => $validated['summary'] ?? null,
                'resume_disk' => $resumeFile['disk'] ?? null,
                'resume_path' => $resumeFile['path'] ?? null,
                'resume_original_name' => $resumeFile['original_name'] ?? null,
                'resume_mime' => $resumeFile['mime'] ?? null,
                'resume_size' => $resumeFile['size'] ?? null,
                'certificate_disk' => $certificateFile['disk'] ?? null,
                'certificate_path' => $certificateFile['path'] ?? null,
                'certificate_original_name' => $certificateFile['original_name'] ?? null,
                'certificate_mime' => $certificateFile['mime'] ?? null,
                'certificate_size' => $certificateFile['size'] ?? null,
                'consent' => true,
                'status' => FreelancerStatus::New,
                'status_history' => [],
                'submitted_at' => now(),
            ]);

            $freelancer->preferredLocations()->sync($validated['preferred_location_ids'] ?? []);

            return $freelancer;
        });

        $this->alerts->freelancerReceived($freelancer);
        $request->session()->forget('freelancer_submission_token');

        return $freelancer->load('preferredLocations');
    }

    public function updateStatus(Freelancer $freelancer, FreelancerStatus $status, ?User $actor): Freelancer
    {
        $from = $freelancer->status;
        if ($from === $status) {
            return $freelancer;
        }

        $history = $freelancer->history();
        $history[] = [
            'from' => $from?->value,
            'to' => $status->value,
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->name,
            'created_at' => now()->toDateTimeString(),
        ];

        $freelancer->forceFill([
            'status' => $status,
            'status_history' => $history,
        ])->save();

        return $freelancer->fresh() ?? $freelancer;
    }

    /**
     * @return Collection<int, User>
     */
    public function possibleMatches(Freelancer $freelancer): Collection
    {
        return User::query()
            ->whereIn('role', [UserRole::Candidate, UserRole::Employer])
            ->where(function ($query) use ($freelancer) {
                $query->whereRaw('LOWER(email) = ?', [Str::lower($freelancer->email)]);
                if (filled($freelancer->mobile)) {
                    $query->orWhere('mobile', $freelancer->mobile);
                }
            })
            ->orderBy('id')
            ->limit(5)
            ->get(['id', 'business_id', 'name', 'role']);
    }

    /**
     * @return array<string, mixed>
     */
    public function formOptions(): array
    {
        return [
            'locations' => Location::query()->active()->ordered()->get(['id', 'name'])->map(fn (Location $item) => [
                'value' => $item->id,
                'label' => $item->name,
            ])->all(),
            'industries' => Industry::query()->active()->ordered()->get(['id', 'name'])->map(fn (Industry $item) => [
                'value' => $item->id,
                'label' => $item->name,
            ])->all(),
            'categories' => JobCategory::query()->active()->ordered()->get(['id', 'name'])->map(fn (JobCategory $item) => [
                'value' => $item->id,
                'label' => $item->name,
            ])->all(),
            'experiences' => ExperienceRange::query()->active()->ordered()->get(['id', 'name'])->map(fn (ExperienceRange $item) => [
                'value' => $item->id,
                'label' => $item->name,
            ])->all(),
            'employment_types' => EmploymentType::query()->active()->ordered()->get(['id', 'name'])->map(fn (EmploymentType $item) => [
                'value' => $item->id,
                'label' => $item->name,
            ])->all(),
            'genders' => FreelancerGender::options(),
            'work_types' => PreferredWorkType::options(),
            'notice_periods' => NoticePeriod::options(),
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function existingSubmission(string $token, array $validated): ?Freelancer
    {
        $byToken = Freelancer::query()->where('submission_token', $token)->first();
        if ($byToken instanceof Freelancer) {
            return $byToken->load('preferredLocations');
        }

        return Freelancer::query()
            ->whereRaw('LOWER(email) = ?', [Str::lower((string) $validated['email'])])
            ->where('mobile', $validated['mobile'])
            ->where('name', $validated['name'])
            ->where('created_at', '>=', now()->subMinutes(2))
            ->latest('id')
            ->first()?->load('preferredLocations');
    }

    /**
     * @return list<string>
     */
    private function skillList(?string $skills): array
    {
        if (! is_string($skills) || trim($skills) === '') {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            fn (string $skill) => trim($skill),
            preg_split('/[,;\n]+/', $skills) ?: [],
        ))));
    }
}
