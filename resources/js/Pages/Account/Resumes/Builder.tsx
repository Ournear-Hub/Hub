import { Link } from '@inertiajs/react';
import Button from '@/Components/Public/Button';
import OurNearResume from '@/Components/Resume/OurNearResume';
import ResumeReadinessPanel from '@/Components/Resume/ResumeReadinessPanel';
import SectionHeader from '@/Components/UI/SectionHeader';
import AccountLayout from '@/Layouts/AccountLayout';
import type { AccountCompletionItem, ResumeBuilderPreview } from '@/types/account';

type BuilderProps = {
    preview: ResumeBuilderPreview;
    completion_items?: AccountCompletionItem[];
    profile_completion?: number;
};

export default function ResumeBuilder({ preview, completion_items = [], profile_completion = 0 }: BuilderProps) {
    return (
        <AccountLayout
            title="Hire Resume"
            description="Preview your OurNear-generated resume. Profile data is the source of truth."
            breadcrumbs={[
                { label: 'Account', href: '/account' },
                { label: 'Resume', href: '/account/resumes' },
                { label: 'Hire Resume' },
            ]}
        >
            <div className="resume-print-hide mx-auto max-w-3xl space-y-6">
                <SectionHeader
                    title="Hire Resume"
                    description="Readiness → preview → print or save as PDF. Editing happens in your profile, not here."
                />

                <ResumeReadinessPanel items={completion_items} completion={profile_completion} />

                <div className="flex flex-wrap gap-2">
                    <Link href="/account/resumes" className="inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline">
                        ← Resume hub
                    </Link>
                    <Button href="/account/profile" variant="outline" className="min-h-11">
                        Complete profile
                    </Button>
                    <Button type="button" variant="primary" className="min-h-11" onClick={() => window.print()}>
                        Print / save PDF
                    </Button>
                </div>

                <p className="text-xs text-muted">{preview.note}</p>
            </div>

            <div className="resume-print-hide mt-6">
                <h2 className="mb-3 text-sm font-semibold uppercase tracking-wide text-muted">Preview</h2>
            </div>
            <div className="ourneer-resume-print-root mx-auto max-w-3xl print:max-w-none">
                <OurNearResume preview={preview} />
            </div>
        </AccountLayout>
    );
}
