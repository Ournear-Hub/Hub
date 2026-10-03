<?php

namespace App\Services;

use App\Enums\AutomationTrigger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecruitmentAutomationHooks
{
    /**
     * @param  array<string, mixed>  $context
     */
    public static function fire(AutomationTrigger $trigger, array $context): void
    {
        DB::afterCommit(function () use ($trigger, $context) {
            try {
                app(RecruitmentAutomationDispatcher::class)->dispatch($trigger, $context);
            } catch (\Throwable $exception) {
                Log::warning('Recruitment automation dispatch failed.', [
                    'trigger' => $trigger->value,
                    'message' => $exception->getMessage(),
                ]);
            }
        });
    }
}
