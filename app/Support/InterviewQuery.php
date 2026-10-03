<?php

namespace App\Support;

use App\Enums\InterviewLifecycleStatus;
use App\Models\ApplicationInterview;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class InterviewQuery
{
    /**
     * @return LengthAwarePaginator<int, ApplicationInterview>
     */
    public function adminIndex(Request $request): LengthAwarePaginator
    {
        $query = ApplicationInterview::query()
            ->with(['application.job', 'interviewer:id,name', 'creator:id,name', 'scorecard'])
            ->orderByDesc('scheduled_at')
            ->orderByDesc('id');

        if ($request->filled('lifecycle_status')) {
            $query->where('lifecycle_status', $request->string('lifecycle_status')->toString());
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->toString());
        }

        if ($request->filled('application_id')) {
            $query->where('application_id', $request->integer('application_id'));
        }

        if ($request->filled('interviewer_user_id')) {
            $query->where('interviewer_user_id', $request->integer('interviewer_user_id'));
        }

        $scope = $request->string('scope')->toString();
        if ($scope === 'upcoming') {
            $query->whereIn('lifecycle_status', [
                InterviewLifecycleStatus::Scheduled->value,
                InterviewLifecycleStatus::Confirmed->value,
            ])->where('scheduled_at', '>=', now());
        } elseif ($scope === 'today') {
            $query->whereDate('scheduled_at', now()->toDateString());
        } elseif ($scope === 'mine') {
            $userId = $request->user()?->id;
            if ($userId) {
                $query->where(function ($inner) use ($userId) {
                    $inner->where('created_by', $userId)
                        ->orWhere('interviewer_user_id', $userId);
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->whereHas('application', function ($application) use ($search) {
                $application->where('full_name', 'like', '%'.$search.'%')
                    ->orWhere('business_id', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        return $query->paginate(20)->withQueryString();
    }

    /**
     * @return array<string, string>
     */
    public function filters(Request $request): array
    {
        return [
            'search' => $request->string('search')->toString(),
            'lifecycle_status' => $request->string('lifecycle_status')->toString(),
            'type' => $request->string('type')->toString(),
            'application_id' => $request->string('application_id')->toString(),
            'interviewer_user_id' => $request->string('interviewer_user_id')->toString(),
            'scope' => $request->string('scope')->toString(),
            'interview' => $request->string('interview')->toString(),
        ];
    }
}
