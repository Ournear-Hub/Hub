import OurNearIcon from '@/icons/OurNearIcon';

/** Decorative hero — not live data. Shared by Talent Pool and Staffing landings. */
export function RecruitmentShortlistHeroIllustration({
    ariaLabel = 'Illustration: OurNear recruitment team shortlists candidate profiles for an employer requirement',
}: {
    ariaLabel?: string;
}) {
    return (
        <div
            className="relative flex aspect-[4/3] min-h-[14rem] w-full flex-col justify-between overflow-hidden rounded-xl border border-line bg-gradient-to-br from-brand/5 via-white to-accent/5 p-5 shadow-sm"
            role="img"
            aria-label={ariaLabel}
        >
            <p className="text-[10px] font-medium uppercase tracking-wide text-muted">Illustrative preview</p>
            <div className="grid gap-3 sm:grid-cols-[1fr_1.1fr]">
                <div className="rounded-lg border border-line bg-white p-3 shadow-sm">
                    <p className="text-[10px] font-semibold uppercase text-brand">Requirement</p>
                    <p className="mt-1 text-xs font-semibold text-ink">Site supervisors</p>
                    <p className="mt-1 text-[11px] text-muted">3 roles · Abu Dhabi</p>
                    <div className="mt-2 h-1.5 overflow-hidden rounded-full bg-line">
                        <div className="h-full w-2/3 rounded-full bg-accent" aria-hidden="true" />
                    </div>
                    <p className="mt-1 text-[10px] text-muted">Screening in progress</p>
                </div>
                <div className="rounded-lg border border-accent/30 bg-white p-3 shadow-sm">
                    <div className="flex items-center justify-between gap-2">
                        <p className="text-xs font-semibold text-ink">Candidate profile</p>
                        <span className="rounded-full bg-accent/15 px-2 py-0.5 text-[10px] font-semibold text-accent">
                            Shortlisted
                        </span>
                    </div>
                    <p className="mt-2 text-[11px] text-muted">Matched skills · Experience summary</p>
                    <p className="mt-2 text-[10px] text-muted">For employer review — not a live record</p>
                </div>
            </div>
            <div className="mt-2 flex items-center justify-center gap-2 text-[10px] text-muted">
                <OurNearIcon name="clipboard" size="sm" decorative className="text-brand" />
                <span aria-hidden="true">→</span>
                <OurNearIcon name="user" size="sm" decorative className="text-accent" />
            </div>
        </div>
    );
}

export function RecruitmentDashboardIllustration() {
    return (
        <div
            className="flex min-h-[12rem] w-full overflow-hidden rounded-xl border border-line bg-white shadow-sm"
            role="img"
            aria-label="Illustration: recruitment dashboard with requirement summary and candidate cards"
        >
            <div className="hidden w-14 shrink-0 bg-brand sm:block" aria-hidden="true" />
            <div className="min-w-0 flex-1 p-4">
                <p className="text-[10px] font-semibold uppercase text-muted">Illustrative dashboard</p>
                <p className="mt-1 text-sm font-semibold text-ink">Requirement summary</p>
                <ul className="mt-3 space-y-2">
                    <li className="rounded-md border border-line p-2 text-xs">
                        <span className="font-medium text-ink">Profile review</span>
                        <span className="ml-2 text-muted">Pending</span>
                    </li>
                    <li className="rounded-md border border-line p-2 text-xs">
                        <span className="font-medium text-ink">Interview schedule</span>
                        <span className="ml-2 text-muted">Coordinated</span>
                    </li>
                </ul>
            </div>
        </div>
    );
}
