<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class RecoveredApplicationReconciler
{
    public function __construct(private RecoveredIdentityMap $identityMap) {}

    /**
     * @return array{
     *     recoverable: int,
     *     unrecoverable: int,
     *     skipped_valid: int,
     *     skipped_duplicate: int,
     *     details: list<array<string, mixed>>
     * }
     */
    public function analyze(bool $limitDetails = true, int $detailLimit = 100): array
    {
        if (! Schema::hasTable('applications')) {
            return [
                'recoverable' => 0,
                'unrecoverable' => 0,
                'skipped_valid' => 0,
                'skipped_duplicate' => 0,
                'details' => [],
            ];
        }

        $recoverable = 0;
        $unrecoverable = 0;
        $skippedValid = 0;
        $skippedDuplicate = 0;
        $details = [];

        $applications = DB::table('applications')
            ->select(['id', 'user_id', 'job_id', 'email', 'full_name'])
            ->orderBy('id')
            ->get();

        foreach ($applications as $application) {
            $applicationId = (int) $application->id;
            $userId = (int) $application->user_id;
            $jobId = (int) $application->job_id;
            $userValid = $this->userExists($userId);
            $jobValid = $this->jobExists($jobId);

            if ($userValid && $jobValid) {
                $skippedValid++;

                continue;
            }

            [$proposedUserId, $userMethod] = $userValid
                ? [$userId, 'existing']
                : $this->resolveUserId($userId, (string) ($application->email ?? ''));
            [$proposedJobId, $jobMethod] = $jobValid
                ? [$jobId, 'existing']
                : $this->resolveJobId($jobId);

            if ($proposedUserId === null || $proposedJobId === null) {
                $unrecoverable++;
                if (! $limitDetails || count($details) < $detailLimit) {
                    $details[] = [
                        'application_id' => $applicationId,
                        'status' => 'unrecoverable',
                        'user_id' => $userId,
                        'job_id' => $jobId,
                        'proposed_user_id' => $proposedUserId,
                        'proposed_job_id' => $proposedJobId,
                        'user_match' => $userMethod,
                        'job_match' => $jobMethod,
                    ];
                }

                continue;
            }

            if ($this->wouldDuplicate($applicationId, $proposedUserId, $proposedJobId)) {
                $skippedDuplicate++;
                if (! $limitDetails || count($details) < $detailLimit) {
                    $details[] = [
                        'application_id' => $applicationId,
                        'status' => 'duplicate_would_conflict',
                        'user_id' => $userId,
                        'job_id' => $jobId,
                        'proposed_user_id' => $proposedUserId,
                        'proposed_job_id' => $proposedJobId,
                        'user_match' => $userMethod,
                        'job_match' => $jobMethod,
                    ];
                }

                continue;
            }

            $recoverable++;
            if (! $limitDetails || count($details) < $detailLimit) {
                $details[] = [
                    'application_id' => $applicationId,
                    'status' => 'recoverable',
                    'user_id' => $userId,
                    'job_id' => $jobId,
                    'proposed_user_id' => $proposedUserId,
                    'proposed_job_id' => $proposedJobId,
                    'user_match' => $userMethod,
                    'job_match' => $jobMethod,
                ];
            }
        }

        return [
            'recoverable' => $recoverable,
            'unrecoverable' => $unrecoverable,
            'skipped_valid' => $skippedValid,
            'skipped_duplicate' => $skippedDuplicate,
            'details' => $details,
        ];
    }

    /**
     * @return array{updated: int, skipped: int}
     */
    public function apply(): array
    {
        $analysis = $this->analyze(limitDetails: false);
        $updated = 0;
        $skipped = 0;

        foreach ($analysis['details'] as $row) {
            if (($row['status'] ?? '') !== 'recoverable') {
                $skipped++;

                continue;
            }

            DB::table('applications')
                ->where('id', $row['application_id'])
                ->update([
                    'user_id' => $row['proposed_user_id'],
                    'job_id' => $row['proposed_job_id'],
                    'updated_at' => now(),
                ]);
            $updated++;
        }

        return ['updated' => $updated, 'skipped' => $skipped];
    }

    private function userExists(int $userId): bool
    {
        return Schema::hasTable('users')
            && DB::table('users')->where('id', $userId)->exists();
    }

    private function jobExists(int $jobId): bool
    {
        return Schema::hasTable('job_listings')
            && DB::table('job_listings')->where('id', $jobId)->exists();
    }

    /**
     * @return array{0: ?int, 1: string}
     */
    private function resolveUserId(int $legacyUserId, string $applicationEmail): array
    {
        $mapped = $this->identityMap->mappedUserId($legacyUserId);
        if ($mapped !== null && $this->userExists($mapped)) {
            return [$mapped, 'user_id_map.json'];
        }

        $email = strtolower(trim($applicationEmail));
        if ($email !== '' && Schema::hasTable('users')) {
            $matches = DB::table('users')
                ->whereRaw('LOWER(email) = ?', [$email])
                ->pluck('id');

            if ($matches->count() === 1) {
                return [(int) $matches->first(), 'application.email → users.email'];
            }
        }

        return [null, 'none'];
    }

    /**
     * @return array{0: ?int, 1: string}
     */
    private function resolveJobId(int $legacyJobId): array
    {
        $mapped = $this->identityMap->mappedJobId($legacyJobId);
        if ($mapped !== null && $this->jobExists($mapped)) {
            return [$mapped, 'job_id_map.json'];
        }

        return [null, 'none'];
    }

    private function wouldDuplicate(int $applicationId, int $userId, int $jobId): bool
    {
        return DB::table('applications')
            ->where('id', '!=', $applicationId)
            ->where('user_id', $userId)
            ->where('job_id', $jobId)
            ->exists();
    }
}
