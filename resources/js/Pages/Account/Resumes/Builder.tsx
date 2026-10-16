import { Link, router } from '@inertiajs/react';
import Button from '@/Components/atoms/Button';
import OurNearResume from '@/Components/Resume/OurNearResume';
import ResumeReadinessPanel from '@/Components/Resume/ResumeReadinessPanel';
import AccountLayout from '@/Layouts/AccountLayout';
import { accountLayout } from '@/design-system/classNames';
import type { AccountCompletionItem, ResumeBuilderPreview } from '@/types/account';

type BuilderProps = {
    preview: ResumeBuilderPreview;
    completion_items?: AccountCompletionItem[];
    profile_completion?: number;
};

function formatGeneratedAt(iso: string | null | undefined): string | null {
    if (!iso) {
        return null;
    }
    const date = new Date(iso);
    if (Number.isNaN(date.getTime())) {
        return null;
    }
    return date.toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

function printResume() {
    window.print();
}

export default function ResumeBuilder({ preview, completion_items = [], profile_completion = 0 }: BuilderProps) {
    const generatedLabel = formatGeneratedAt(preview.generated_at);

    return (
        <AccountLayout
            title="Hire Resume"
            description="Live preview from your saved profile. Print or save as PDF from your browser."
            breadcrumbs={[
                { label: 'Personal Dashboard', href: '/account' },
                { label: 'Hire Resume', href: '/account/resumes' },
                { label: 'Preview' },
            ]}
        >
            <div className={`resume-print-hide ${accountLayout.profileResumeWorkspace}`}>
                <Link
                    href="/account/resumes"
                    className="inline-flex min-h-10 items-center text-sm font-semibold text-brand hover:underline"
                >
                    ← Back to Hire Resume
                </Link>

                {completion_items.length > 0 ? (
                    <div className="max-w-3xl">
                        <ResumeReadinessPanel items={completion_items} completion={profile_completion} />
                    </div>
                ) : null}

                <div className="max-w-3xl space-y-3">
                    <header>
                        <h1 className="text-xl font-semibold text-ink sm:text-2xl">Hire Resume preview</h1>
                        <p className="mt-1 text-sm text-muted">
                            This preview uses your latest saved profile data
                            {generatedLabel ? (
                                <>
                                    {' '}
                                    (profile last updated <span className="font-medium text-ink">{generatedLabel}</span>)
                                </>
                            ) : (
                                '.'
                            )}{' '}
                            Edit your profile to change what appears here.
                        </p>
                        {preview.note ? <p className="mt-2 text-xs text-muted">{preview.note}</p> : null}
                    </header>
                    <div className="flex flex-wrap gap-2">
                        <Button surface="public" type="button" variant="outline" className="min-h-11" onClick={() => router.reload()}>
                            Refresh preview
                        </Button>
                        <Button surface="public" href="/account/profile#professional" variant="outline" className="min-h-11">
                            Update profile
                        </Button>
                        <Button
                            surface="public"
                            href="/account/resumes/builder/download"
                            nativeAnchor
                            variant="primary"
                            className="min-h-11"
                        >
                            Download Hire Resume
                        </Button>
                        <Button surface="public" type="button" variant="secondary" className="min-h-11" onClick={printResume}>
                            Print / Save as PDF
                        </Button>
                    </div>
                    <p className="text-xs text-muted">
                        Download saves a private HTML file from your profile. For PDF, use Print / Save as PDF in your browser.
                    </p>
                </div>
            </div>

            <div className="ourneer-resume-print-root mt-6 min-w-0 max-w-[52rem] print:mt-0 print:max-w-none">
                <OurNearResume preview={preview} />
            </div>
        </AccountLayout>
    );
}
