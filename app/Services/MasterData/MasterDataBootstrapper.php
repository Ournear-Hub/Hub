<?php

namespace App\Services\MasterData;

use App\Enums\ApplicationStatus;
use App\Enums\BusinessIdPrefix;
use App\Enums\JobPublishingStatus;
use App\Models\Application;
use App\Models\ApplicationStatusOption;
use App\Models\CandidateProfile;
use App\Models\EmployerEnquiry;
use App\Models\EmployerProfile;
use App\Models\EmploymentType;
use App\Models\ExperienceRange;
use App\Models\Industry;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\JobStatusOption;
use App\Models\JobTitle;
use App\Models\Location;
use App\Models\Qualification;
use App\Models\RejectionReason;
use App\Support\WebsiteContentDefaults;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class MasterDataBootstrapper
{
    public function run(): void
    {
        $this->seedLookups();
        $this->seedClassificationDefaults();
        $this->importUnambiguousMasterValues();
        $this->mapExistingForeignKeys();
        $this->assignMissingBusinessIds();
    }

    public function mapExistingForeignKeys(): void
    {
        $this->mapJobs();
        $this->mapApplications();
        $this->mapCandidateProfiles();
        $this->mapEmployerProfiles();
        $this->mapEmployerEnquiries();
    }

    private function seedLookups(): void
    {
        foreach ([
            ['Full-time', 1],
            ['Part-time', 2],
            ['Contract', 3],
            ['Temporary', 4],
        ] as [$name, $order]) {
            $this->firstOrCreateByName(EmploymentType::class, $name, ['sort_order' => $order]);
        }

        foreach ([
            ['0-1 years', 1, 0, 1],
            ['1-3 years', 2, 1, 3],
            ['3-5 years', 3, 3, 5],
            ['5+ years', 4, 5, null],
        ] as [$name, $order, $min, $max]) {
            $this->firstOrCreateByName(ExperienceRange::class, $name, [
                'sort_order' => $order,
                'min_years' => $min,
                'max_years' => $max,
            ]);
        }

        foreach (['GCSEs', 'A-Levels', 'NVQ', 'Degree'] as $index => $name) {
            $this->firstOrCreateByName(Qualification::class, $name, ['sort_order' => $index + 1]);
        }

        foreach ([
            [JobPublishingStatus::Draft->value, 'Draft', 1],
            [JobPublishingStatus::Published->value, 'Published', 2],
        ] as [$code, $name, $order]) {
            JobStatusOption::query()->firstOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'is_active' => true,
                    'is_system' => true,
                    'sort_order' => $order,
                ],
            );
        }

        foreach (ApplicationStatus::cases() as $index => $status) {
            ApplicationStatusOption::query()->firstOrCreate(
                ['code' => $status->value],
                [
                    'name' => $status->value,
                    'is_active' => true,
                    'is_system' => true,
                    'sort_order' => $index + 1,
                ],
            );
        }

        foreach ([
            ['Not suitable for the role', 1],
            ['Insufficient experience', 2],
            ['Incomplete application', 3],
            ['Role filled', 4],
            ['Candidate withdrew', 5],
        ] as [$name, $order]) {
            $this->firstOrCreateByName(RejectionReason::class, $name, ['sort_order' => $order]);
        }
    }

    private function seedClassificationDefaults(): void
    {
        $order = 1;

        foreach (WebsiteContentDefaults::groups()['industries'] as $industry) {
            $this->firstOrCreateByName(Industry::class, $industry['title'], [
                'description' => $industry['description'] ?? null,
                'sort_order' => $order,
            ]);
            $order++;
        }

        foreach ([
            'Facilities',
            'Hospitality',
            'Logistics',
            'Manufacturing',
            'Office and professional',
        ] as $name) {
            $this->firstOrCreateByName(Industry::class, $name, ['sort_order' => $order]);
            $order++;
        }

        $construction = Industry::findByName('Construction');

        if (! $construction) {
            return;
        }

        $hierarchy = [
            'Civil' => ['Site Engineer', 'Quantity Surveyor', 'Civil Foreman'],
            'Electrical' => ['Electrical Engineer', 'Electrician'],
            'Mechanical' => ['Mechanical Engineer', 'Fitter'],
        ];

        $categoryOrder = 1;

        foreach ($hierarchy as $categoryName => $titles) {
            $category = JobCategory::query()->firstOrCreate(
                [
                    'industry_id' => $construction->id,
                    'name' => $categoryName,
                ],
                [
                    'is_active' => true,
                    'sort_order' => $categoryOrder,
                ],
            );

            foreach ($titles as $titleOrder => $title) {
                JobTitle::query()->firstOrCreate(
                    [
                        'job_category_id' => $category->id,
                        'name' => $title,
                    ],
                    [
                        'is_active' => true,
                        'sort_order' => $titleOrder + 1,
                    ],
                );
            }

            $categoryOrder++;
        }
    }

    private function importUnambiguousMasterValues(): void
    {
        foreach ($this->distinctStrings([
            [Job::class, 'industry'],
            [EmployerProfile::class, 'industry'],
            [EmployerEnquiry::class, 'industry'],
        ]) as $name) {
            $this->firstOrCreateByName(Industry::class, $name);
        }

        foreach ($this->distinctStrings([[Job::class, 'employment_type']]) as $name) {
            $this->firstOrCreateByName(EmploymentType::class, $name);
        }

        foreach ($this->distinctStrings([
            [Job::class, 'experience'],
            [Application::class, 'experience'],
            [CandidateProfile::class, 'experience'],
        ]) as $name) {
            $this->firstOrCreateByName(ExperienceRange::class, $name);
        }

        foreach ($this->distinctStrings([
            [Application::class, 'qualification'],
            [CandidateProfile::class, 'qualification'],
        ]) as $name) {
            $this->firstOrCreateByName(Qualification::class, $name);
        }

        foreach ($this->distinctStrings([
            [Job::class, 'location'],
            [Application::class, 'current_location'],
            [CandidateProfile::class, 'location'],
            [EmployerEnquiry::class, 'project_location'],
        ]) as $name) {
            $this->firstOrCreateByName(Location::class, $name);
        }
    }

    private function mapJobs(): void
    {
        Job::query()->orderBy('id')->chunkById(100, function ($jobs): void {
            foreach ($jobs as $job) {
                $dirty = false;

                if ($job->industry_id === null && ($industry = Industry::findByName((string) $job->industry))) {
                    $job->industry_id = $industry->id;
                    $dirty = true;
                }

                if ($job->location_id === null && ($location = Location::findByName((string) $job->location))) {
                    $job->location_id = $location->id;
                    $dirty = true;
                }

                if ($job->employment_type_id === null && ($type = EmploymentType::findByName((string) $job->employment_type))) {
                    $job->employment_type_id = $type->id;
                    $dirty = true;
                }

                if ($job->experience_range_id === null && ($range = ExperienceRange::findByName((string) $job->experience))) {
                    $job->experience_range_id = $range->id;
                    $dirty = true;
                }

                if ($dirty) {
                    $job->save();
                }
            }
        });
    }

    private function mapApplications(): void
    {
        Application::query()->orderBy('id')->chunkById(100, function ($applications): void {
            foreach ($applications as $application) {
                $dirty = false;

                if ($application->location_id === null && ($location = Location::findByName((string) $application->current_location))) {
                    $application->location_id = $location->id;
                    $dirty = true;
                }

                if ($application->experience_range_id === null && ($range = ExperienceRange::findByName((string) $application->experience))) {
                    $application->experience_range_id = $range->id;
                    $dirty = true;
                }

                if ($application->qualification_id === null && ($qualification = Qualification::findByName((string) $application->qualification))) {
                    $application->qualification_id = $qualification->id;
                    $dirty = true;
                }

                if ($dirty) {
                    $application->save();
                }
            }
        });
    }

    private function mapCandidateProfiles(): void
    {
        CandidateProfile::query()->orderBy('id')->chunkById(100, function ($profiles): void {
            foreach ($profiles as $profile) {
                $dirty = false;

                if ($profile->location_id === null && filled($profile->location) && ($location = Location::findByName((string) $profile->location))) {
                    $profile->location_id = $location->id;
                    $dirty = true;
                }

                if ($profile->experience_range_id === null && filled($profile->experience) && ($range = ExperienceRange::findByName((string) $profile->experience))) {
                    $profile->experience_range_id = $range->id;
                    $dirty = true;
                }

                if ($profile->qualification_id === null && filled($profile->qualification) && ($qualification = Qualification::findByName((string) $profile->qualification))) {
                    $profile->qualification_id = $qualification->id;
                    $dirty = true;
                }

                if ($dirty) {
                    $profile->save();
                }
            }
        });
    }

    private function mapEmployerProfiles(): void
    {
        EmployerProfile::query()->orderBy('id')->chunkById(100, function ($profiles): void {
            foreach ($profiles as $profile) {
                if ($profile->industry_id !== null || blank($profile->industry)) {
                    continue;
                }

                $industry = Industry::findByName((string) $profile->industry);

                if ($industry) {
                    $profile->industry_id = $industry->id;
                    $profile->save();
                }
            }
        });
    }

    private function mapEmployerEnquiries(): void
    {
        EmployerEnquiry::query()->orderBy('id')->chunkById(100, function ($enquiries): void {
            foreach ($enquiries as $enquiry) {
                $dirty = false;

                if ($enquiry->industry_id === null && filled($enquiry->industry) && ($industry = Industry::findByName((string) $enquiry->industry))) {
                    $enquiry->industry_id = $industry->id;
                    $dirty = true;
                }

                if ($enquiry->location_id === null && filled($enquiry->project_location) && ($location = Location::findByName((string) $enquiry->project_location))) {
                    $enquiry->location_id = $location->id;
                    $dirty = true;
                }

                if ($dirty) {
                    $enquiry->save();
                }
            }
        });
    }

    private function assignMissingBusinessIds(): void
    {
        $generator = app(BusinessIdGenerator::class);

        $this->backfillBusinessIds(Job::class, BusinessIdPrefix::Job, $generator);
        $this->backfillBusinessIds(Application::class, BusinessIdPrefix::Application, $generator);
        $this->backfillBusinessIds(CandidateProfile::class, BusinessIdPrefix::Candidate, $generator);
        $this->backfillBusinessIds(EmployerProfile::class, BusinessIdPrefix::Employer, $generator);
    }

    /**
     * @param  class-string<Model>  $model
     */
    private function backfillBusinessIds(string $model, BusinessIdPrefix $prefix, BusinessIdGenerator $generator): void
    {
        $model::query()
            ->whereNull('business_id')
            ->orderBy('id')
            ->chunkById(100, function ($records) use ($generator, $prefix): void {
                foreach ($records as $record) {
                    $record->business_id = $generator->next($prefix);
                    $record->save();
                }
            });
    }

    /**
     * @param  class-string<Model>  $model
     * @param  array<string, mixed>  $attributes
     */
    private function firstOrCreateByName(string $model, string $name, array $attributes = []): Model
    {
        $existing = $model::findByName($name);

        if ($existing) {
            return $existing;
        }

        return $model::query()->create([
            'name' => trim($name),
            'is_active' => true,
            ...$attributes,
        ]);
    }

    /**
     * @param  list<array{0: class-string<Model>, 1: string}>  $sources
     * @return list<string>
     */
    private function distinctStrings(array $sources): array
    {
        $values = [];

        foreach ($sources as [$model, $column]) {
            $table = (new $model)->getTable();

            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            $model::query()
                ->whereNotNull($column)
                ->where($column, '!=', '')
                ->distinct()
                ->orderBy($column)
                ->pluck($column)
                ->each(function (mixed $value) use (&$values): void {
                    $name = trim((string) $value);

                    if ($name !== '') {
                        $values[mb_strtolower($name)] = $name;
                    }
                });
        }

        return array_values($values);
    }
}
