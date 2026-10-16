<?php

namespace App\Support;

use App\Enums\EmployerStaffingAccessStatus;
use App\Models\EmployerEnquiry;
use App\Models\EmployerProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class StaffingRequirementAllocationQuery
{
    /**
     * Staffing requirements (employer enquiries) eligible for admin candidate allocation.
     * These are not companies — each row is a submitted manpower requirement.
     *
     * @return list<array<string, mixed>>
     */
    public static function optionsForAdminAllocation(): array
    {
        return self::baseQuery()
            ->limit(500)
            ->get()
            ->map(fn (EmployerEnquiry $enquiry) => self::toOption($enquiry))
            ->values()
            ->all();
    }

    /**
     * @return Builder<EmployerEnquiry>
     */
    public static function baseQuery(): Builder
    {
        return EmployerEnquiry::query()
            ->with(['user.employerProfile'])
            ->latest('id')
            ->where(function (Builder $builder) {
                $builder
                    ->whereHas('user.employerProfile', function (Builder $profile) {
                        $profile->where('staffing_access_status', EmployerStaffingAccessStatus::Approved->value);
                    })
                    ->orWhere(function (Builder $legacy) {
                        // Legacy enquiries without user_id remain selectable for admin follow-up.
                        $legacy->whereNull('user_id');
                    });
            });
    }

    public static function findEligible(int $id): ?EmployerEnquiry
    {
        return self::baseQuery()->whereKey($id)->first();
    }

    /**
     * @return array<string, mixed>
     */
    private static function toOption(EmployerEnquiry $enquiry): array
    {
        /** @var EmployerProfile|null $profile */
        $profile = $enquiry->user?->employerProfile;
        $staffingStatus = $profile?->staffing_access_status?->value
            ?? ($enquiry->user_id === null ? 'legacy_unlinked' : EmployerStaffingAccessStatus::NotRequested->value);

        return [
            'id' => $enquiry->id,
            'label' => trim(($enquiry->company_name ?? 'Staffing').' · '.$enquiry->project_location),
            'company_name' => $enquiry->company_name,
            'employer_profile_id' => $profile?->id,
            'employer_profile_name' => $profile?->displayCompany(),
            'owner_user_id' => $enquiry->user_id,
            'staffing_access_status' => $staffingStatus,
            'staffing_access_label' => $profile?->staffing_access_status?->label() ?? 'Unlinked requirement',
        ];
    }

    /**
     * @return list<array{id: int, name: string, staffing_access_status: string, staffing_access_label: string, has_requirements: bool}>
     */
    public static function companyDirectoryForAdmin(): array
    {
        $requirementCounts = EmployerEnquiry::query()
            ->selectRaw('user_id, count(*) as aggregate')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->pluck('aggregate', 'user_id');

        return EmployerProfile::query()
            ->orderBy('company_name')
            ->get(['id', 'company_name', 'user_id', 'staffing_access_status'])
            ->map(function (EmployerProfile $profile) use ($requirementCounts) {
                $count = (int) ($requirementCounts->get($profile->user_id) ?? 0);
                $status = $profile->staffing_access_status ?? EmployerStaffingAccessStatus::NotRequested;

                return [
                    'id' => $profile->id,
                    'name' => $profile->displayCompany(),
                    'staffing_access_status' => $status->value,
                    'staffing_access_label' => $status->label(),
                    'has_requirements' => $count > 0,
                    'requirements_count' => $count,
                ];
            })
            ->values()
            ->all();
    }
}
