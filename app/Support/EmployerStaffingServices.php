<?php

namespace App\Support;

use App\Enums\EmployerEnquiryStatus;
use App\Models\EmployerEnquiry;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class EmployerStaffingServices
{
    public const REQUEST_HREF = '/for-employers';

    public const SUPPORT_CONTACT_HREF = '/contact';

    /**
     * @return array<string, int>
     */
    public static function workspaceSummary(User $user): array
    {
        $base = $user->employerEnquiries();

        $total = (clone $base)->count();

        $active = (clone $base)->whereIn('status', [
            EmployerEnquiryStatus::New,
            EmployerEnquiryStatus::Contacted,
            EmployerEnquiryStatus::InProgress,
        ])->count();

        $completed = (clone $base)->where('status', EmployerEnquiryStatus::Completed)->count();

        $closed = (clone $base)->where('status', EmployerEnquiryStatus::Closed)->count();

        return [
            'total_requests' => $total,
            'active_requests' => $active,
            'completed_requests' => $completed,
            'closed_requests' => $closed,
        ];
    }

    /**
     * @return Builder<EmployerEnquiry>
     */
    public static function ownedEnquiriesQuery(User $user): Builder
    {
        return EmployerEnquiry::query()
            ->where('user_id', $user->id)
            ->latest();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function statusFilterOptions(): array
    {
        return collect(EmployerEnquiryStatus::cases())
            ->map(fn (EmployerEnquiryStatus $status) => [
                'value' => $status->value,
                'label' => $status->toEmployerLabel(),
            ])
            ->values()
            ->all();
    }
}
