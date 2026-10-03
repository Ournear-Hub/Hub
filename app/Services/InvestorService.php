<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\InvestorStatus;
use App\Enums\RecruitmentActivityType;
use App\Enums\SecurityActivityType;
use App\Models\Investor;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InvestorService
{
    public function __construct(
        private UserManagementService $users,
        private RecruitmentActivityService $activities,
        private AdminAlertService $alerts,
        private SecurityAuditService $audit,
    ) {}

    /**
     * @param  array{
     *     name?: string,
     *     email: string,
     *     mobile?: string|null,
     *     display_name: string,
     *     contact_person?: string|null,
     *     notes?: string|null
     * }  $data
     */
    public function create(array $data, User $actor): Investor
    {
        return DB::transaction(function () use ($data, $actor) {
            $displayName = trim((string) $data['display_name']);
            $user = $this->users->createInvestorIdentity($actor, [
                'name' => $data['name'] ?? ($data['contact_person'] ?? $displayName),
                'email' => $data['email'],
                'mobile' => $data['mobile'] ?? null,
            ]);

            $investor = Investor::query()->create([
                'user_id' => $user->id,
                'status' => InvestorStatus::PendingVerification,
                'display_name' => $displayName,
                'contact_person' => $data['contact_person'] ?? $user->name,
                'email' => strtolower(trim($data['email'])),
                'mobile' => $data['mobile'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::InvestorCreated,
                investor: $investor,
                body: 'Investor created: '.$investor->display_name,
                toStatus: InvestorStatus::PendingVerification->value,
            );

            return $investor->fresh(['user']) ?? $investor;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Investor $investor, array $data, User $actor): Investor
    {
        $investor->fill([
            'display_name' => $data['display_name'] ?? $investor->display_name,
            'contact_person' => array_key_exists('contact_person', $data) ? $data['contact_person'] : $investor->contact_person,
            'email' => array_key_exists('email', $data) ? $data['email'] : $investor->email,
            'mobile' => array_key_exists('mobile', $data) ? $data['mobile'] : $investor->mobile,
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : $investor->notes,
        ])->save();

        if ($investor->user && (isset($data['contact_person']) || isset($data['display_name']) || isset($data['mobile']))) {
            $investor->user->fill([
                'name' => $data['contact_person'] ?? $data['display_name'] ?? $investor->user->name,
                'mobile' => array_key_exists('mobile', $data) ? $data['mobile'] : $investor->user->mobile,
            ])->save();
        }

        return $investor->fresh(['user']) ?? $investor;
    }

    public function verify(Investor $investor, User $actor): Investor
    {
        if ($investor->status === InvestorStatus::Active && $investor->verified_at) {
            return $investor;
        }

        return DB::transaction(function () use ($investor, $actor) {
            $from = $investor->status->value;
            $investor->forceFill([
                'status' => InvestorStatus::Active,
                'verified_at' => $investor->verified_at ?? now(),
                'verified_by' => $actor->id,
                'suspended_at' => null,
                'suspended_by' => null,
            ])->save();

            if ($investor->user) {
                $investor->user->forceFill([
                    'account_status' => AccountStatus::Active,
                    'is_active' => true,
                ])->save();
            }

            $fresh = $investor->fresh(['user']) ?? $investor;

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::InvestorVerified,
                investor: $fresh,
                body: 'Investor verified: '.$fresh->display_name,
                fromStatus: $from,
                toStatus: InvestorStatus::Active->value,
            );

            $this->audit->record(
                SecurityActivityType::InvestorVerified,
                'Investor '.$fresh->display_name.' was verified.',
                $actor,
                $fresh,
            );

            $this->alerts->investorVerified($fresh);

            return $fresh;
        });
    }

    public function suspend(Investor $investor, User $actor): Investor
    {
        if ($investor->status === InvestorStatus::Suspended) {
            return $investor;
        }

        return DB::transaction(function () use ($investor, $actor) {
            $from = $investor->status->value;
            $investor->forceFill([
                'status' => InvestorStatus::Suspended,
                'suspended_at' => now(),
                'suspended_by' => $actor->id,
            ])->save();

            if ($investor->user) {
                $investor->user->forceFill([
                    'account_status' => AccountStatus::Suspended,
                    'is_active' => false,
                ])->save();
            }

            $fresh = $investor->fresh(['user']) ?? $investor;

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::InvestorSuspended,
                investor: $fresh,
                body: 'Investor suspended: '.$fresh->display_name,
                fromStatus: $from,
                toStatus: InvestorStatus::Suspended->value,
            );

            $this->audit->record(
                SecurityActivityType::InvestorSuspended,
                'Investor '.$fresh->display_name.' was suspended.',
                $actor,
                $fresh,
            );

            return $fresh;
        });
    }
}
