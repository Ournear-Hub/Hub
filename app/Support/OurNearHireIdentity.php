<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OurNearHireIdentity
{
    public function assign(User $user): void
    {
        if (! Schema::hasColumn('users', 'person_number') || filled($user->person_number)) {
            return;
        }

        $user->person_number = $this->nextNumber();
    }

    public function display(User $user, ?string $code = null): string
    {
        $number = (int) ($user->person_number ?: 0);
        if ($number < 1 && Schema::hasColumn('users', 'person_number')) {
            $this->assign($user);
            if ($user->isDirty('person_number') && $user->exists) {
                $user->saveQuietly();
            }
            $number = (int) $user->person_number;
        }

        return self::format(max(1, $number), $code ?? $this->codeFor($user));
    }

    public function codeFor(User $user): string
    {
        if ($user->isCandidate()) {
            return 'CAN';
        }

        if ($user->isEmployer() || ($user->isAdmin() && $user->canViewHiringCommandCenter())) {
            return 'REC';
        }

        return 'USER';
    }

    public static function format(int $number, string $code): string
    {
        $width = max(3, strlen((string) $number));

        return sprintf('ON-HIR-%s-%0'.$width.'d', strtoupper($code), $number);
    }

    private function nextNumber(): int
    {
        return ((int) DB::table('users')->max('person_number')) + 1;
    }
}
