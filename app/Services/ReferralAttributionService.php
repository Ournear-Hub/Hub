<?php

namespace App\Services;

use App\Enums\ReferralConversionType;
use App\Enums\RecruitmentActivityType;
use App\Enums\SecurityActivityType;
use App\Models\Application;
use App\Models\Referral;
use App\Models\ReferralLink;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;

class ReferralAttributionService
{
    public function __construct(
        private RecruitmentActivityService $activities,
        private AdminAlertService $alerts,
        private SecurityAuditService $audit,
    ) {}

    public function capture(Request $request): void
    {
        $incoming = $this->normalizeCode($request->query('ref'));
        if ($incoming === null) {
            return;
        }

        $existingCode = $this->normalizeCode($request->cookie(ReferralLink::COOKIE_NAME));
        if ($existingCode !== null && $this->usableLink($existingCode)) {
            return;
        }

        if (! $this->usableLink($incoming)) {
            return;
        }

        Cookie::queue(cookie(
            ReferralLink::COOKIE_NAME,
            $incoming,
            ReferralLink::COOKIE_MINUTES,
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            'lax',
        ));
    }

    public function resolveLink(Request $request): ?ReferralLink
    {
        $cookieCode = $this->normalizeCode($request->cookie(ReferralLink::COOKIE_NAME));
        if ($cookieCode !== null) {
            $cookieLink = $this->usableLink($cookieCode);
            if ($cookieLink) {
                return $cookieLink;
            }
        }

        $queryCode = $this->normalizeCode($request->query('ref'));

        return $queryCode !== null ? $this->usableLink($queryCode) : null;
    }

    public function attributeRegistration(User $user, Request $request, ?User $actor = null): ?Referral
    {
        $link = $this->resolveLink($request);
        if (! $link) {
            return null;
        }

        if ($link->referrer_user_id && (int) $link->referrer_user_id === (int) $user->id) {
            return null;
        }

        $already = Referral::query()
            ->where('referred_user_id', $user->id)
            ->where('conversion_type', ReferralConversionType::Registration->value)
            ->exists();

        if ($already) {
            return null;
        }

        return $this->store(
            $link,
            ReferralConversionType::Registration,
            $user,
            $actor ?? $user,
            referredUser: $user,
        );
    }

    public function attributeApplication(Application $application, Request $request, ?User $actor = null): ?Referral
    {
        $link = $this->resolveLink($request);
        if (! $link) {
            return null;
        }

        if ($application->user_id && $link->referrer_user_id && (int) $link->referrer_user_id === (int) $application->user_id) {
            return null;
        }

        if (Referral::query()->where('application_id', $application->id)->exists()) {
            return null;
        }

        $referred = $application->user_id
            ? User::query()->find($application->user_id)
            : null;

        return $this->store(
            $link,
            ReferralConversionType::Application,
            $referred,
            $actor ?? $referred,
            application: $application,
        );
    }

    private function store(
        ReferralLink $link,
        ReferralConversionType $type,
        ?User $referred,
        ?User $actor,
        ?User $referredUser = null,
        ?Application $application = null,
    ): Referral {
        return DB::transaction(function () use ($link, $type, $referred, $actor, $referredUser, $application) {
            $referral = Referral::query()->create([
                'conversion_type' => $type,
                'is_primary' => true,
                'referral_link_id' => $link->id,
                'campaign_id' => $link->campaign_id,
                'referrer_user_id' => $link->referrer_user_id,
                'referred_user_id' => $referredUser?->id ?? $referred?->id ?? $application?->user_id,
                'application_id' => $application?->id,
                'job_id' => $application?->job_id ?? $link->job_id,
                'created_by' => $actor?->id,
            ]);

            if ($type === ReferralConversionType::Registration && $referredUser) {
                $referredUser->forceFill([
                    'registration_source' => $referral->registrationSource(),
                ])->save();
            }

            if ($actor) {
                $this->activities->record(
                    actor: $actor,
                    type: RecruitmentActivityType::ReferralAttributed,
                    application: $application,
                    campaign: $link->campaign,
                    referralLink: $link,
                    referral: $referral,
                    body: $type === ReferralConversionType::Registration
                        ? 'Registration attributed to referral '.$link->code.'.'
                        : 'Application attributed to referral '.$link->code.'.',
                    toStatus: $type->value,
                );
            }

            $this->audit->record(
                SecurityActivityType::ReferralAttributed,
                'Public referral '.$link->code.' attributed for '.$type->value.'.',
                $actor,
                $referral,
            );

            $this->alerts->referralAttributed($referral);

            return $referral->fresh(['referralLink', 'campaign', 'referredUser']) ?? $referral;
        });
    }

    private function usableLink(string $code): ?ReferralLink
    {
        $link = ReferralLink::query()
            ->with('campaign')
            ->whereRaw('UPPER(code) = ?', [strtoupper($code)])
            ->first();

        if (! $link instanceof ReferralLink || ! $link->isCapturable()) {
            return null;
        }

        return $link;
    }

    private function normalizeCode(mixed $value): ?string
    {
        $code = strtoupper(trim((string) $value));
        if ($code === '' || ! preg_match('/^[A-Z0-9]{4,16}$/', $code)) {
            return null;
        }

        return $code;
    }
}
