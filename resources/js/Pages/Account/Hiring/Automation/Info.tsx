import { Link } from '@inertiajs/react';
import AccountLayout from '@/Layouts/AccountLayout';
import Button from '@/Components/atoms/Button';
import Card from '@/Components/atoms/Card';

export default function HiringAutomationInfo() {
    return (
        <AccountLayout
            title="Automation"
            description="How recruitment automation applies to your hiring pipeline."
            breadcrumbs={[
                { label: 'My Workspace', href: '/account' },
                { label: 'Automation' },
            ]}
        >
            <Card padding="compact" className="max-w-2xl">
                <h2 className="text-base font-semibold text-ink">Recruitment automation</h2>
                <p className="mt-2 text-sm text-muted">
                    OurNear runs recruitment automation rules from the admin workspace (screening recommendations, tasks,
                    and notifications). Rules do not auto-shortlist, reject, or hire candidates on your jobs.
                </p>
                <p className="mt-2 text-sm text-muted">
                    Review automation guidance on individual applications during screening, and manage applicants from
                    your hiring pipeline views.
                </p>
                <div className="mt-4 flex flex-wrap gap-2">
                    <Button surface="public" href="/account/hiring/applications?stage=screening" variant="primary">
                        Open screening queue
                    </Button>
                    <Button surface="public" href="/account/jobs" variant="secondary">
                        View jobs
                    </Button>
                    <Link href="/account/hiring/applications" className="inline-flex min-h-10 items-center text-sm font-semibold text-brand hover:underline">
                        All applications
                    </Link>
                </div>
            </Card>
        </AccountLayout>
    );
}
