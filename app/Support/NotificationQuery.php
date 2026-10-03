<?php

namespace App\Support;

use App\Enums\AdminNotificationEvent;
use App\Enums\PermissionName;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\Employee;
use App\Models\EmployerSubmission;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Placement;
use App\Models\Replacement;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationQuery
{
    /**
     * @return LengthAwarePaginator<int, DatabaseNotification>
     */
    public function adminIndex(Request $request, User $user): LengthAwarePaginator
    {
        $query = $this->baseQuery($request, $user)->latest();

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search) {
                $inner->where('data->title', 'like', '%'.$search.'%')
                    ->orWhere('data->body', 'like', '%'.$search.'%')
                    ->orWhere('data->event', 'like', '%'.$search.'%');
            });
        }

        $event = trim((string) $request->query('event', ''));
        if ($event !== '') {
            $query->where('data->event', $event);
        }

        $category = trim((string) $request->query('category', ''));
        if ($category !== '') {
            $query->whereIn('data->event', AdminNotificationEvent::valuesForCategory($category));
        }

        $readState = $this->readState($request);
        if ($readState === 'unread' || $request->boolean('unread')) {
            $query->whereNull('read_at');
        } elseif ($readState === 'read') {
            $query->whereNotNull('read_at');
        }

        $from = trim((string) $request->query('date_from', ''));
        if ($from !== '') {
            $query->whereDate('created_at', '>=', $from);
        }

        $to = trim((string) $request->query('date_to', ''));
        if ($to !== '') {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query->paginate(20)->withQueryString();
    }

    public function selected(Request $request, User $user): ?DatabaseNotification
    {
        $id = trim((string) $request->query('notification', ''));
        if ($id === '') {
            return null;
        }

        return $this->baseQuery($request, $user)->whereKey($id)->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(Request $request): array
    {
        $selectedId = trim((string) $request->query('notification', ''));
        $readState = $this->readState($request);
        if ($request->boolean('unread') && $readState === 'all') {
            $readState = 'unread';
        }

        return [
            'search' => $request->string('search')->toString(),
            'event' => $request->string('event')->toString(),
            'category' => $request->string('category')->toString(),
            'read_state' => $readState,
            'unread' => $readState === 'unread' ? '1' : '',
            'recipient_id' => $request->string('recipient_id')->toString(),
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
            'notification' => $selectedId,
        ];
    }

    /**
     * @return list<string>
     */
    public function events(): array
    {
        return array_map(fn (AdminNotificationEvent $event) => $event->value, AdminNotificationEvent::cases());
    }

    /**
     * @return list<string>
     */
    public function categories(): array
    {
        return AdminNotificationEvent::categories();
    }

    /**
     * @return list<array{id: int, name: string, email: string, label: string}>
     */
    public function recipients(User $user): array
    {
        if ($user->isSuperAdmin()) {
            return User::query()
                ->where('role', UserRole::Admin->value)
                ->where('is_active', true)
                ->whereHas('roles.permissions', fn ($query) => $query->where('name', PermissionName::NotificationsView->value))
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->map(fn (User $admin) => [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'label' => $admin->name.' · '.$admin->email,
                ])
                ->all();
        }

        return [[
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'label' => $user->name.' · '.$user->email,
        ]];
    }

    /**
     * @return array<string, mixed>
     */
    public static function toAdminArray(DatabaseNotification $notification): array
    {
        $data = is_array($notification->data) ? $notification->data : [];
        $eventValue = (string) ($data['event'] ?? '');
        $event = AdminNotificationEvent::tryFrom($eventValue);
        $notifiable = $notification->notifiable;
        $related = self::relatedFromContext(is_array($data['context'] ?? null) ? $data['context'] : []);

        return [
            'id' => $notification->id,
            'event' => $eventValue,
            'event_label' => $event?->label() ?? $eventValue,
            'category' => $event?->category() ?? null,
            'title' => $data['title'] ?? '',
            'body' => $data['body'] ?? '',
            'url' => $data['url'] ?? null,
            'context' => $data['context'] ?? [],
            'related' => $related,
            'chain' => self::chainFromRelated($related),
            'recipient' => $notifiable instanceof User ? [
                'id' => $notifiable->id,
                'name' => $notifiable->name,
                'email' => $notifiable->email,
            ] : null,
            'read_at' => $notification->read_at?->toDateTimeString(),
            'created_at' => $notification->created_at?->toDateTimeString(),
            'is_unread' => $notification->read_at === null,
            'can_toggle_read' => $notifiable instanceof User,
        ];
    }

    /**
     * @return Builder<DatabaseNotification>
     */
    private function baseQuery(Request $request, User $user): Builder
    {
        $recipientId = $request->integer('recipient_id');

        if ($user->isSuperAdmin() && $recipientId > 0 && $recipientId !== (int) $user->id) {
            return DatabaseNotification::query()
                ->with('notifiable')
                ->where('notifiable_type', $user->getMorphClass())
                ->where('notifiable_id', $recipientId);
        }

        return $user->notifications()->getQuery()->with('notifiable');
    }

    private function readState(Request $request): string
    {
        $state = trim((string) $request->query('read_state', ''));

        return in_array($state, ['all', 'unread', 'read'], true) ? $state : 'all';
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private static function relatedFromContext(array $context): array
    {
        $related = [];

        if (! empty($context['application_id'])) {
            $application = Application::query()
                ->with(['job.employer.user', 'user.candidateProfile'])
                ->find($context['application_id']);
            if ($application) {
                $related = array_merge($related, self::relatedFromApplication($application));
            }
        }

        if (! empty($context['submission_id'])) {
            $submission = EmployerSubmission::query()
                ->with(['application.user.candidateProfile', 'employer.user', 'job'])
                ->find($context['submission_id']);
            if ($submission) {
                $related['submission'] = [
                    'id' => $submission->id,
                    'business_id' => $submission->business_id,
                    'url' => RecruitmentWorkspace::submissionUrl($submission),
                ];
                $related['employer'] = $related['employer'] ?? ($submission->employer ? [
                    'id' => $submission->employer->id,
                    'business_id' => $submission->employer->business_id,
                    'company_name' => $submission->employer->displayCompany(),
                    'url' => RecruitmentWorkspace::employerUrl($submission->employer),
                ] : null);
            }
        }

        if (! empty($context['placement_id'])) {
            $placement = Placement::query()
                ->with(['application.user.candidateProfile', 'employer.user', 'job'])
                ->find($context['placement_id']);
            if ($placement) {
                $related['placement'] = [
                    'id' => $placement->id,
                    'business_id' => $placement->business_id,
                    'url' => RecruitmentWorkspace::placementUrl($placement),
                ];
                $related = array_merge(self::relatedFromApplication($placement->application), $related);
                if ($placement->employer && empty($related['employer'])) {
                    $related['employer'] = [
                        'id' => $placement->employer->id,
                        'business_id' => $placement->employer->business_id,
                        'company_name' => $placement->employer->displayCompany(),
                        'url' => RecruitmentWorkspace::employerUrl($placement->employer),
                    ];
                }
                if ($placement->job && empty($related['job'])) {
                    $related['job'] = [
                        'id' => $placement->job->id,
                        'business_id' => $placement->job->business_id,
                        'title' => $placement->job->title,
                        'url' => RecruitmentWorkspace::jobUrl($placement->job),
                    ];
                }
            }
        }

        if (! empty($context['employee_id'])) {
            $employee = Employee::query()->find($context['employee_id']);
            if ($employee) {
                $related['employee'] = [
                    'id' => $employee->id,
                    'business_id' => $employee->business_id,
                    'url' => RecruitmentWorkspace::employeeUrl($employee),
                ];
            }
        }

        if (! empty($context['replacement_id'])) {
            $replacement = Replacement::query()->find($context['replacement_id']);
            if ($replacement) {
                $related['replacement'] = [
                    'id' => $replacement->id,
                    'business_id' => $replacement->business_id,
                    'url' => RecruitmentWorkspace::replacementUrl($replacement),
                ];
            }
        }

        if (! empty($context['invoice_id'])) {
            $invoice = Invoice::query()
                ->with(['application.user.candidateProfile', 'employer.user', 'job', 'placement'])
                ->find($context['invoice_id']);
            if ($invoice) {
                $related = array_merge(self::relatedFromApplication($invoice->application), $related);
                $related['invoice'] = [
                    'id' => $invoice->id,
                    'business_id' => $invoice->business_id,
                    'url' => RecruitmentWorkspace::invoiceUrl($invoice),
                ];
            }
        }

        if (! empty($context['payment_id'])) {
            $payment = Payment::query()->with('invoice')->find($context['payment_id']);
            if ($payment) {
                $related['payment'] = [
                    'id' => $payment->id,
                    'business_id' => $payment->business_id,
                    'url' => RecruitmentWorkspace::paymentUrl($payment),
                ];
            }
        }

        return array_filter($related);
    }

    /**
     * @return array<string, mixed>
     */
    private static function relatedFromApplication(?Application $application): array
    {
        if ($application === null) {
            return [];
        }

        $application->loadMissing(['job.employer.user', 'user.candidateProfile']);
        $profile = $application->user?->candidateProfile;

        return array_filter([
            'application' => [
                'id' => $application->id,
                'business_id' => $application->business_id,
                'url' => RecruitmentWorkspace::applicationUrl($application),
            ],
            'job' => $application->job ? [
                'id' => $application->job->id,
                'business_id' => $application->job->business_id,
                'title' => $application->job->title,
                'url' => RecruitmentWorkspace::jobUrl($application->job),
            ] : null,
            'candidate' => [
                'id' => $profile?->id,
                'business_id' => $profile?->business_id,
                'name' => $application->full_name,
                'url' => RecruitmentWorkspace::candidateUrl($profile),
            ],
            'employer' => $application->job?->employer ? [
                'id' => $application->job->employer->id,
                'business_id' => $application->job->employer->business_id,
                'company_name' => $application->job->employer->displayCompany(),
                'url' => RecruitmentWorkspace::employerUrl($application->job->employer),
            ] : null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $related
     * @return list<array<string, mixed>>
     */
    private static function chainFromRelated(array $related): array
    {
        $order = ['candidate', 'application', 'job', 'employer', 'submission', 'placement', 'employee', 'replacement', 'invoice', 'payment'];
        $steps = [];

        foreach ($order as $key) {
            if (empty($related[$key]) || ! is_array($related[$key])) {
                continue;
            }

            $item = $related[$key];
            $steps[] = RecruitmentWorkspace::step(
                $key,
                match ($key) {
                    'candidate' => 'Candidate',
                    'application' => 'Application',
                    'job' => 'Job',
                    'employer' => 'Employer',
                    'submission' => 'Employer Submission',
                    'placement' => 'Placement',
                    'employee' => 'Employee',
                    'replacement' => 'Replacement',
                    'invoice' => 'Invoice',
                    'payment' => 'Payment',
                    default => ucfirst($key),
                },
                $item['business_id'] ?? $item['name'] ?? $item['title'] ?? $item['company_name'] ?? null,
                $item['url'] ?? null,
            );
        }

        return $steps;
    }
}
