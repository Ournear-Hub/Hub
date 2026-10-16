import { Link } from '@inertiajs/react';
import Alert from '@/Components/UI/Alert';
import Button from '@/Components/atoms/Button';
import type { AccountCompletionItem } from '@/types/account';
import { incompleteResumeItems, isHireResumeReady, profileHrefForCompletionKey } from '@/Components/Resume/resumeReadiness';

type Props = {
    items: AccountCompletionItem[];
    completion: number;
    className?: string;
};

export default function ResumeReadinessPanel({ items, completion, className = '' }: Props) {
    const missing = incompleteResumeItems(items);
    const ready = isHireResumeReady(items);

    if (ready) {
        return (
            <Alert tone="success" title="Hire Resume ready" className={className}>
                Your profile includes enough information to generate a readable OurNear resume. Use preview below, then print or
                save as PDF from your browser.
            </Alert>
        );
    }

    return (
        <section className={`rounded-xl border border-line bg-white p-4 sm:p-6 ${className}`.trim()} aria-labelledby="resume-readiness">
            <h2 id="resume-readiness" className="text-lg font-semibold text-ink">
                Your resume is not ready yet
            </h2>
            <p className="mt-1 text-sm text-muted">
                Hire Resume is built from your profile — complete these sections (profile {completion}% overall).
            </p>
            <ul className="mt-3 space-y-2 text-sm">
                {missing.map((item) => (
                    <li key={item.key}>
                        <Link href={profileHrefForCompletionKey(item.key)} className="font-medium text-brand hover:underline">
                            {item.label}
                        </Link>
                    </li>
                ))}
            </ul>
            <Button surface="public" href="/account/profile" variant="secondary" className="mt-4 min-h-11">
                Complete profile
            </Button>
        </section>
    );
}
