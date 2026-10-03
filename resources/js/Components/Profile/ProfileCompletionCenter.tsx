import { Link } from '@inertiajs/react';
import Button from '@/Components/Public/Button';
import StatusBadge from '@/Components/UI/StatusBadge';
import type { AccountProfile } from '@/types/account';
import {
    buildProfileModules,
    nextIncompleteModule,
    type ProfileModuleCard,
    type ProfileModuleId,
} from '@/Components/Profile/profileCompletionModules';

type Props = {
    profile: AccountProfile;
    onOpenModule?: (moduleId: ProfileModuleId) => void;
    compact?: boolean;
};

function statusLabel(status: ProfileModuleCard['status']): string {
    if (status === 'complete') {
        return 'Complete';
    }
    if (status === 'in_progress') {
        return 'In progress';
    }
    return 'Not started';
}

function statusTone(status: ProfileModuleCard['status']): 'success' | 'warning' | 'neutral' {
    if (status === 'complete') {
        return 'success';
    }
    if (status === 'in_progress') {
        return 'warning';
    }
    return 'neutral';
}

function actionLabel(module: ProfileModuleCard): string {
    if (module.id === 'resume') {
        return 'Manage';
    }
    if (module.status === 'complete') {
        return 'Edit';
    }
    if (module.status === 'in_progress') {
        return 'Continue';
    }
    return 'Add';
}

export default function ProfileCompletionCenter({ profile, onOpenModule, compact = false }: Props) {
    const modules = buildProfileModules(profile);
    const next = nextIncompleteModule(modules);
    const completion = profile.profile_completion ?? 0;

    if (compact) {
        return (
            <section className="rounded-xl border border-line bg-white p-4 sm:p-5" aria-labelledby="profile-completion-summary">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <h2 id="profile-completion-summary" className="text-base font-semibold text-ink">
                        Profile
                    </h2>
                    <p className="text-sm font-semibold text-brand">{completion}% complete</p>
                </div>
                <div className="mt-2 h-2 overflow-hidden rounded-full bg-surface" aria-hidden="true">
                    <div className="h-full rounded-full bg-brand" style={{ width: `${Math.max(0, Math.min(100, completion))}%` }} />
                </div>
                <p className="mt-2 text-sm text-muted">Complete your profile to improve application readiness.</p>
                {next ? (
                    <p className="mt-1 text-sm text-ink">
                        Next: <span className="font-medium">{next.title}</span>
                    </p>
                ) : null}
                <Link href="/account/profile" className="mt-3 inline-block text-sm font-semibold text-brand hover:underline">
                    Continue
                </Link>
            </section>
        );
    }

    return (
        <section className="rounded-xl border border-line bg-white p-4 sm:p-6" aria-labelledby="profile-completion-center">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 id="profile-completion-center" className="text-lg font-semibold text-ink">
                        Complete your profile
                    </h2>
                    <p className="mt-1 text-sm text-muted">
                        {completion >= 100
                            ? 'Your profile meets the current completion criteria.'
                            : completion >= 70
                              ? 'Your profile is almost ready.'
                              : 'Finish one section at a time — no long forms.'}
                    </p>
                </div>
                <p className="text-lg font-semibold text-brand">{completion}%</p>
            </div>
            <div className="mt-3 h-2 overflow-hidden rounded-full bg-surface" role="progressbar" aria-valuenow={completion} aria-valuemin={0} aria-valuemax={100}>
                <div className="h-full rounded-full bg-brand transition-[width]" style={{ width: `${Math.max(0, Math.min(100, completion))}%` }} />
            </div>

            {next ? (
                <div className="mt-5 rounded-lg border border-brand/20 bg-brand/5 p-4">
                    <p className="text-xs font-semibold uppercase tracking-wide text-muted">Next step</p>
                    <p className="mt-1 font-semibold text-ink">{next.title}</p>
                    <p className="mt-1 text-sm text-muted">{next.description}</p>
                    {onOpenModule ? (
                        <Button type="button" variant="primary" className="mt-3 min-h-11" onClick={() => onOpenModule(next.id)}>
                            Continue
                        </Button>
                    ) : (
                        <Link href={`/account/profile#${next.id}`} className="mt-3 inline-flex min-h-11 items-center rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white">
                            Continue
                        </Link>
                    )}
                </div>
            ) : (
                <p className="mt-4 text-sm text-success">Required profile information is complete.</p>
            )}

            <div className="mt-6">
                <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Profile modules</h3>
                <ul className="mt-3 space-y-2">
                    {modules.map((module) => (
                        <li key={module.id}>
                            <article className="flex flex-col gap-3 rounded-lg border border-line bg-surface/40 p-3 sm:flex-row sm:items-center sm:justify-between">
                                <div className="min-w-0 flex-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h4 className="font-semibold text-ink">{module.title}</h4>
                                        <StatusBadge tone={statusTone(module.status)}>{statusLabel(module.status)}</StatusBadge>
                                    </div>
                                    <p className="mt-0.5 line-clamp-2 text-sm text-muted">{module.summary}</p>
                                </div>
                                {module.id === 'resume' ? (
                                    <Link
                                        href="/account/resumes"
                                        className="inline-flex min-h-11 shrink-0 items-center justify-center rounded-md border border-line bg-white px-4 py-2 text-sm font-semibold text-brand"
                                    >
                                        Manage
                                    </Link>
                                ) : onOpenModule ? (
                                    <button
                                        type="button"
                                        className="inline-flex min-h-11 shrink-0 items-center justify-center rounded-md border border-line bg-white px-4 py-2 text-sm font-semibold text-brand hover:bg-white"
                                        onClick={() => onOpenModule(module.id)}
                                    >
                                        {actionLabel(module)}
                                    </button>
                                ) : (
                                    <Link
                                        href={`/account/profile#${module.id}`}
                                        className="inline-flex min-h-11 shrink-0 items-center justify-center rounded-md border border-line bg-white px-4 py-2 text-sm font-semibold text-brand"
                                    >
                                        {actionLabel(module)}
                                    </Link>
                                )}
                            </article>
                        </li>
                    ))}
                </ul>
            </div>
        </section>
    );
}
