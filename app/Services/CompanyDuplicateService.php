<?php

namespace App\Services;

use App\Models\CrmLead;
use App\Models\EmployerProfile;
use Illuminate\Validation\ValidationException;

class CompanyDuplicateService
{
    /**
     * @return list<array{type: string, id: int, company_name: string|null, email: string|null, reasons: list<string>}>
     */
    public function matches(
        ?string $companyName,
        ?string $email = null,
        ?string $website = null,
        ?string $mobile = null,
        ?int $ignoreLeadId = null,
        ?int $ignoreEmployerId = null,
    ): array {
        $nameKey = $this->normalizeName($companyName);
        $emailKey = $this->normalizeEmail($email);
        $hostKey = $this->normalizeHost($website);
        $mobileKey = $this->normalizeMobile($mobile);

        $matches = [];

        foreach (EmployerProfile::query()->with('user:id,email,mobile')->get() as $employer) {
            if ($ignoreEmployerId && (int) $employer->id === $ignoreEmployerId) {
                continue;
            }

            $reasons = $this->reasons(
                $nameKey,
                $emailKey,
                $hostKey,
                $mobileKey,
                $this->normalizeName($employer->company_name),
                $this->normalizeEmail($employer->user?->email ?: null),
                $this->normalizeHost($employer->company_website),
                $this->normalizeMobile($employer->mobile_number ?: $employer->user?->mobile),
            );

            if ($reasons === []) {
                continue;
            }

            $matches[] = [
                'type' => 'employer',
                'id' => $employer->id,
                'company_name' => $employer->company_name,
                'email' => $employer->user?->email,
                'workspace_url' => route('admin.employers.index', ['employer' => $employer->id], false),
                'reasons' => $reasons,
            ];
        }

        $leads = CrmLead::query();
        if ($ignoreLeadId) {
            $leads->whereKeyNot($ignoreLeadId);
        }

        foreach ($leads->get() as $lead) {
            $reasons = $this->reasons(
                $nameKey,
                $emailKey,
                $hostKey,
                $mobileKey,
                $this->normalizeName($lead->company_name),
                $this->normalizeEmail($lead->email),
                $this->normalizeHost($lead->website),
                $this->normalizeMobile($lead->mobile),
            );

            if ($reasons === []) {
                continue;
            }

            $matches[] = [
                'type' => 'lead',
                'id' => $lead->id,
                'company_name' => $lead->company_name,
                'email' => $lead->email,
                'workspace_url' => route('admin.crm.index', ['lead' => $lead->id], false),
                'reasons' => $reasons,
            ];
        }

        return $matches;
    }

    /**
     * @return list<array{type: string, id: int, company_name: string|null, email: string|null, reasons: list<string>}>
     */
    public function assertClear(
        ?string $companyName,
        ?string $email,
        ?string $website,
        ?string $mobile,
        bool $confirmDuplicates,
        ?int $ignoreLeadId = null,
        ?int $ignoreEmployerId = null,
    ): array {
        $matches = $this->matches($companyName, $email, $website, $mobile, $ignoreLeadId, $ignoreEmployerId);

        if ($matches !== [] && ! $confirmDuplicates) {
            session()->flash('duplicate_matches', $matches);

            throw ValidationException::withMessages([
                'duplicates' => 'Possible duplicate company records were found. Confirm to continue without merging.',
            ]);
        }

        return $matches;
    }

    public function normalizeName(?string $value): string
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;
        $value = preg_replace('/\b(ltd|limited|inc|incorporated|llc|llp|plc|pvt|private|company|co)\b\.?/i', '', $value) ?? $value;

        return trim((string) preg_replace('/[^a-z0-9 ]/', '', $value));
    }

    public function normalizeEmail(?string $value): string
    {
        return strtolower(trim((string) $value));
    }

    public function normalizeHost(?string $value): string
    {
        $value = strtolower(trim((string) $value));
        if ($value === '') {
            return '';
        }

        $host = parse_url(str_contains($value, '://') ? $value : 'https://'.$value, PHP_URL_HOST);

        return is_string($host) ? preg_replace('/^www\./', '', $host) ?? $host : '';
    }

    public function normalizeMobile(?string $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    /**
     * @return list<string>
     */
    private function reasons(
        string $nameKey,
        string $emailKey,
        string $hostKey,
        string $mobileKey,
        string $otherName,
        string $otherEmail,
        string $otherHost,
        string $otherMobile,
    ): array {
        $reasons = [];

        if ($nameKey !== '' && $otherName !== '' && $nameKey === $otherName) {
            $reasons[] = 'company_name';
        }
        if ($emailKey !== '' && $otherEmail !== '' && $emailKey === $otherEmail) {
            $reasons[] = 'email';
        }
        if ($hostKey !== '' && $otherHost !== '' && $hostKey === $otherHost) {
            $reasons[] = 'website';
        }
        if ($mobileKey !== '' && $otherMobile !== '' && $mobileKey === $otherMobile) {
            $reasons[] = 'mobile';
        }

        return $reasons;
    }
}
