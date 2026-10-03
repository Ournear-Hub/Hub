import Alert from '@/Components/UI/Alert';
import Button from '@/Components/Public/Button';

export type ApplicationConfirmationPayload = {
    business_id: string | null;
    job_title: string;
    company?: string | null;
    location?: string | null;
    href?: string | null;
};

type Props = {
    confirmation: ApplicationConfirmationPayload;
    className?: string;
};

export default function ApplicationConfirmation({ confirmation, className = '' }: Props) {
    const meta = [confirmation.company, confirmation.location].filter(Boolean).join(' · ');
    const reference = confirmation.business_id ? (
        <>
            {' '}
            <span className="font-medium">{confirmation.business_id}</span>
        </>
    ) : null;

    return (
        <Alert tone="success" title="Application submitted" className={className}>
            <p className="text-ink">
                Your application{reference} for{' '}
                <span className="font-medium">{confirmation.job_title}</span>
                {meta ? <> ({meta})</> : null} is with the hiring team.
            </p>
            <div className="mt-4 flex flex-wrap gap-2">
                {confirmation.href ? (
                    <Button href={confirmation.href} variant="secondary" className="text-sm">
                        View application
                    </Button>
                ) : null}
                <Button href="/jobs" variant="outline" className="text-sm">
                    Browse more jobs
                </Button>
                <Button href="/account/profile" variant="ghost" className="text-sm">
                    View profile
                </Button>
            </div>
        </Alert>
    );
}
