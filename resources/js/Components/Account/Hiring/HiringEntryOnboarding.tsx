import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import Button from '@/Components/Public/Button';
import Card from '@/Components/UI/Card';

type Gate = {
    email_verified: boolean;
    mobile_verified: boolean;
    whatsapp_required: boolean;
    is_employer: boolean;
    has_company?: boolean;
    company_name?: string | null;
    hiring_email?: string;
    hiring_email_verified?: boolean;
    uses_account_email?: boolean;
    hiring_active: boolean;
    message: string;
};

type Eligibility = {
    eligible: boolean;
    reason: string | null;
    next_action: string | null;
} | null;

type HiringActivateForm = {
    company_name: string;
    hiring_email: string;
};

type HiringActivateErrors = Partial<Record<keyof HiringActivateForm | 'email' | 'mobile', string>>;

type Props = {
    gate: Gate;
    eligibility: Eligibility;
    successMessage?: string | null;
    canAdminHiring: boolean;
};

export default function HiringEntryOnboarding({ gate, eligibility, successMessage, canAdminHiring }: Props) {
    const form = useForm<HiringActivateForm>({
        company_name: gate.company_name ?? '',
        hiring_email: gate.hiring_email ?? '',
    });
    const errors = form.errors as HiringActivateErrors;

    const activate = (event: FormEvent) => {
        event.preventDefault();
        form.post('/account/hiring');
    };

    const sendEmail = () => {
        form.post('/account/hiring/email/send');
    };

    const setupRequired = !gate.email_verified || !gate.mobile_verified || !gate.hiring_email_verified || !gate.has_company;

    return (
        <div className="mx-auto flex max-w-2xl flex-col gap-4">
            <Card as="section" density="standard">
                <p className="text-sm font-semibold text-brand">Hire with OurNear</p>
                <h1 className="mt-2 text-2xl font-semibold text-ink">Hiring workspace</h1>
                <p className="mt-3 text-sm text-muted">
                    Access your hiring workspace to post jobs, manage candidates, track applications, and work with OurNear
                    recruitment services—all on your existing account.
                </p>
                {successMessage ? (
                    <p className="mt-3 rounded-md border border-success/20 bg-success/10 px-3 py-2 text-sm text-success">
                        {successMessage}
                    </p>
                ) : null}
                <p className="mt-4 text-sm text-ink">{gate.message}</p>
                {setupRequired ? (
                    <p className="mt-2 text-sm font-medium text-ink">Set up your hiring profile to enter the workspace.</p>
                ) : null}
            </Card>

            <Card as="section" density="compact">
                <h2 className="text-base font-semibold text-ink">Verification checklist</h2>
                <ul className="mt-3 space-y-2 text-sm text-ink">
                    <li>Email — {gate.email_verified ? 'Verified' : 'Required'}</li>
                    <li>Mobile — {gate.mobile_verified ? 'Verified' : 'Required to activate hiring'}</li>
                    <li>
                        Recruiter email — {gate.hiring_email_verified ? 'Verified' : 'Verify if different from account email'}
                        {gate.uses_account_email ? ' · using your account email' : ''}
                    </li>
                    <li>WhatsApp — optional; verification is not required.</li>
                </ul>
                <div className="mt-4 flex flex-wrap gap-2">
                    {!gate.email_verified ? (
                        <Button href="/account/settings/security" variant="primary" className="min-h-11">
                            Verify email
                        </Button>
                    ) : null}
                    {!gate.mobile_verified ? (
                        <Button href="/account/settings/security" variant="primary" className="min-h-11">
                            Verify mobile
                        </Button>
                    ) : null}
                    <Button href="/contact" variant="outline" className="min-h-11">
                        Send hiring request
                    </Button>
                    {canAdminHiring ? (
                        <Button href="/admin/hiring" variant="outline" className="min-h-11">
                            Staff command center
                        </Button>
                    ) : null}
                </div>
            </Card>

            {eligibility && !eligibility.eligible && eligibility.reason ? (
                <Card as="section" density="compact">
                    <p className="text-sm text-ink">{eligibility.reason}</p>
                    {eligibility.next_action ? <p className="mt-2 text-sm text-muted">{eligibility.next_action}</p> : null}
                    <Link href="/account/profile" className="mt-3 inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline">
                        Continue company setup
                    </Link>
                </Card>
            ) : null}

            {!gate.hiring_active ? (
                <form onSubmit={activate} className="space-y-4 rounded-xl border border-line bg-white p-4 sm:p-6">
                    <h2 className="text-lg font-semibold text-ink">Activate hiring on this account</h2>
                    <p className="text-sm text-muted">
                        This uses your existing OurNear account. Recruiter email can match your login email or be verified separately.
                    </p>
                    <label className="block text-sm font-medium text-ink" htmlFor="company_name">
                        Company name
                    </label>
                    <input
                        id="company_name"
                        className="w-full rounded-md border border-line px-3 py-2 text-sm"
                        value={form.data.company_name}
                        onChange={(event) => form.setData('company_name', event.target.value)}
                    />
                    {errors.company_name ? <p className="text-sm text-danger">{errors.company_name}</p> : null}
                    <label className="block text-sm font-medium text-ink" htmlFor="hiring_email">
                        Recruiter email
                    </label>
                    <input
                        id="hiring_email"
                        type="email"
                        className="w-full rounded-md border border-line px-3 py-2 text-sm"
                        value={form.data.hiring_email}
                        onChange={(event) => form.setData('hiring_email', event.target.value)}
                    />
                    {errors.hiring_email ? <p className="text-sm text-danger">{errors.hiring_email}</p> : null}
                    {errors.email ? <p className="text-sm text-danger">{errors.email}</p> : null}
                    {errors.mobile ? <p className="text-sm text-danger">{errors.mobile}</p> : null}
                    <div className="flex flex-wrap gap-3">
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="min-h-11 rounded-md bg-accent px-4 py-2 text-sm font-semibold text-white disabled:bg-disabled"
                        >
                            Enter hiring workspace
                        </button>
                        <button
                            type="button"
                            className="min-h-11 rounded-md border border-line px-4 py-2 text-sm font-semibold text-ink"
                            onClick={sendEmail}
                        >
                            Verify recruiter email
                        </button>
                    </div>
                </form>
            ) : null}
        </div>
    );
}
