<?php

namespace App\Enums;

use App\Models\ApplicationStatusOption;
use App\Models\EmploymentType;
use App\Models\ExperienceRange;
use App\Models\Industry;
use App\Models\JobCategory;
use App\Models\JobStatusOption;
use App\Models\JobTitle;
use App\Models\Location;
use App\Models\Qualification;
use App\Models\RejectionReason;
use Illuminate\Database\Eloquent\Model;

enum MasterEntityType: string
{
    case Industries = 'industries';
    case Categories = 'categories';
    case JobTitles = 'job-titles';
    case Locations = 'locations';
    case EmploymentTypes = 'employment-types';
    case ExperienceRanges = 'experience-ranges';
    case Qualifications = 'qualifications';
    case JobStatuses = 'job-statuses';
    case ApplicationStatuses = 'application-statuses';
    case RejectionReasons = 'rejection-reasons';

    /**
     * @return class-string<Model>
     */
    public function modelClass(): string
    {
        return match ($this) {
            self::Industries => Industry::class,
            self::Categories => JobCategory::class,
            self::JobTitles => JobTitle::class,
            self::Locations => Location::class,
            self::EmploymentTypes => EmploymentType::class,
            self::ExperienceRanges => ExperienceRange::class,
            self::Qualifications => Qualification::class,
            self::JobStatuses => JobStatusOption::class,
            self::ApplicationStatuses => ApplicationStatusOption::class,
            self::RejectionReasons => RejectionReason::class,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Industries => 'Industries',
            self::Categories => 'Categories',
            self::JobTitles => 'Job Titles',
            self::Locations => 'Locations',
            self::EmploymentTypes => 'Employment Types',
            self::ExperienceRanges => 'Experience Ranges',
            self::Qualifications => 'Qualifications',
            self::JobStatuses => 'Job Statuses',
            self::ApplicationStatuses => 'Application Statuses',
            self::RejectionReasons => 'Rejection Reasons',
        };
    }

    public function singular(): string
    {
        return match ($this) {
            self::Industries => 'Industry',
            self::Categories => 'Category',
            self::JobTitles => 'Job Title',
            self::Locations => 'Location',
            self::EmploymentTypes => 'Employment Type',
            self::ExperienceRanges => 'Experience Range',
            self::Qualifications => 'Qualification',
            self::JobStatuses => 'Job Status',
            self::ApplicationStatuses => 'Application Status',
            self::RejectionReasons => 'Rejection Reason',
        };
    }

    public function hasDescription(): bool
    {
        return in_array($this, [
            self::Industries,
            self::Categories,
            self::JobTitles,
            self::RejectionReasons,
        ], true);
    }

    public function hasIndustryParent(): bool
    {
        return in_array($this, [self::Categories, self::JobTitles], true);
    }

    public function hasCategoryParent(): bool
    {
        return $this === self::JobTitles;
    }

    public function hasCode(): bool
    {
        return in_array($this, [self::JobStatuses, self::ApplicationStatuses], true);
    }

    public function allowsCreate(): bool
    {
        return ! $this->hasCode();
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    public static function navigation(): array
    {
        return array_map(
            fn (self $type): array => [
                'id' => $type->value,
                'label' => $type->label(),
            ],
            self::cases(),
        );
    }
}
