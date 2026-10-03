<?php

namespace App\Support;

use App\Models\Conversation;
use App\Models\Invoice;
use App\Models\LoginLog;
use App\Models\SecurityActivity;
use App\Models\User;
use App\Services\CommunicationPreferenceService;

class User360Assembler
{
    public function __construct(
        private CommunicationPreferenceService $preferences,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function payload(User $user): array
    {
        $user->loadMissing([
            'roles.permissions',
            'brand',
            'adminProfile',
            'candidateProfile.classifiedLocation',
            'employerProfile.classifiedIndustry',
            'employerProfile.locations',
            'communicationPreferences',
            'tags',
        ]);

        $this->preferences->ensureDefaults($user);
        $user->load('communicationPreferences');

        $applications = $user->applications()
            ->with(['job', 'screening'])
            ->latest('id')
            ->limit(50)
            ->get();

        $jobs = $applications
            ->map(fn ($application) => $application->job)
            ->filter()
            ->unique('id')
            ->values()
            ->map(fn ($job) => [
                'id' => $job->id,
                'business_id' => $job->business_id,
                'title' => $job->title,
                'active' => (bool) $job->active,
                'href' => '/admin/jobs?job='.$job->id,
            ])
            ->all();

        $documents = $applications
            ->filter(fn ($application) => filled($application->getRawOriginal('resume_path')))
            ->map(fn ($application) => [
                'id' => $application->id,
                'type' => 'Resume',
                'label' => ($application->job?->title ?: 'Application').' resume',
                'href' => route('admin.applications.resume', $application, false),
                'view_href' => route('admin.applications.resume', [
                    'application' => $application,
                    'disposition' => 'inline',
                ], false),
                'download_href' => route('admin.applications.resume', $application, false),
            ])
            ->values()
            ->all();

        $notifications = $user->notifications()
            ->latest()
            ->limit(30)
            ->get()
            ->map(fn ($notification) => [
                'id' => $notification->id,
                'title' => data_get($notification->data, 'title', 'Notification'),
                'body' => data_get($notification->data, 'body'),
                'read_at' => optional($notification->read_at)?->toDateTimeString(),
                'created_at' => optional($notification->created_at)?->toDateTimeString(),
            ])
            ->all();

        $conversations = Conversation::query()
            ->whereHas('participants', fn ($query) => $query->where('users.id', $user->id))
            ->latest('updated_at')
            ->limit(20)
            ->get()
            ->map(fn (Conversation $conversation) => [
                'id' => $conversation->id,
                'subject' => $conversation->subject,
                'href' => '/admin/messages?conversation='.$conversation->id,
                'updated_at' => optional($conversation->updated_at)?->toDateTimeString(),
            ])
            ->all();

        $payments = Invoice::query()
            ->where(function ($query) use ($user) {
                $query->where('created_by', $user->id)
                    ->orWhereHas('employer', fn ($employer) => $employer->where('user_id', $user->id))
                    ->orWhereHas('employee.application', fn ($application) => $application->where('user_id', $user->id));
            })
            ->with(['employer', 'payments'])
            ->latest('id')
            ->limit(30)
            ->get()
            ->map(fn (Invoice $invoice) => $invoice->toAdminListArray())
            ->all();

        return [
            'user' => $user->toAdminArray(),
            'candidate' => $user->candidateProfile?->toAdminArray(),
            'employer' => $user->employerProfile?->toAdminArray(),
            'applications' => $applications->map(fn ($application) => $application->toAdminListArray())->all(),
            'jobs' => $jobs,
            'documents' => $documents,
            'notifications' => $notifications,
            'messages' => $conversations,
            'payments' => $payments,
            'activities' => $user->activities()->with(['actor', 'user'])->latest('id')->limit(50)->get()->map->toAdminArray()->all(),
            'login_logs' => $user->loginLogs()->latest('id')->limit(30)->get()->map->toAdminArray()->all(),
            'security_activities' => SecurityActivity::query()
                ->with('actor')
                ->where(function ($query) use ($user) {
                    $query->where('actor_id', $user->id)
                        ->orWhere(function ($inner) use ($user) {
                            $inner->where('subject_type', User::class)->where('subject_id', $user->id);
                        });
                })
                ->latest('id')
                ->limit(30)
                ->get()
                ->map(fn (SecurityActivity $activity) => $activity->toAdminArray())
                ->all(),
            'communication_preferences' => $user->communicationPreferences
                ->map(fn ($preference) => $preference->toAdminArray())
                ->values()
                ->all(),
        ];
    }
}
