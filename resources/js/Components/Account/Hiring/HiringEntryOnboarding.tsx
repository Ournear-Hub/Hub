import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import Button from '@/Components/atoms/Button';
import Card from '@/Components/atoms/Card';
import type { AccountLookupOption } from '@/types/account';

type Gate = {
    email_verified: boolean;
    mobile_verified: boolean;
    whatsapp_required: boolean;
    is_employer: boolean;
    has_company?: boolean;
    company_name?: string | null;
    company_phone?: string | null;
    industry_id?: number | null;
    hiring_email?: string;
    hiring_email_verified?: boolean;
    uses_account_email?: boolean;
    hiring_active: boolean;
    workspace_ready?: boolean;
    unlimited_publishing?: boolean;
    trial_available?: boolean;
    message: string;
};

type Eligibility = {
    eligible: boolean;
    reason: string | null;
    next_action: string | null;
} | null;

type HiringActivateForm = {
    company_name: string;
    company_phone: string;
    company_email: string;
    industry_id: string;
};

type HiringActivateErrors = Partial<Record<keyof HiringActivateForm | 'email' | 'mobile', string>>;

type Props = {
    gate: Gate;
    eligibility: Eligibility;
    industries: AccountLookupOption[];
    successMessage?: string | null;
    canAdminHiring: boolean;
};

export default function HiringEntryOnboarding({ gate, eligibility, industries, successMessage, canAdminHiring }: Props) {
    const form = useForm<HiringActivateForm>({
        company_name: gate.company_name ?? '',
        company_phone: gate.company_phone ?? '',
        company_email: gate.hiring_email ?? '',
        industry_id: gate.industry_id ? String(gate.industry_id) : '',
    });
    const errors = form.errors as HiringActivateErrors;

    const activate = (event: FormEvent) => {
        event.preventDefault();
        form.post('/account/hiring');
    };

    return (
        <div className="mx-auto flex max-w-2xl flex-col gap-4">
            <Card as="section" padding="standard">
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
            </Card>

            {gate.hiring_active ? (
                <Card as="section" padding="compact">
                    <h2 className="text-base font-semibold text-ink">Optional verification</h2>
                    <p className="mt-2 text-sm text-muted">
                        Email and mobile verification are not required to open the workspace or publish your one-hour trial job.
                        Verify both to publish unlimited jobs.
                    </p>
                    <ul className="mt-3 space-y-2 text-sm text-ink">
                        <li>Email — {gate.email_verified ? 'Verified' : 'Not verified yet'}</li>
                        <li>Mobile — {gate.mobile_verified ? 'Verified' : 'Not verified yet'}</li>
                        <li>WhatsApp — optional; verification is not required.</li>
                    </ul>
                    <div className="mt-4 flex flex-wrap gap-2">
                        <Button surface="public" href="/account/settings/verification" variant="primary" className="min-h-11">
                            Verification settings
                        </Button>
                        <Button surface="public" href="/account/jobs/create" variant="secondary" className="min-h-11">
                            Create Job
                        </Button>
                        {canAdminHiring ? (
                            <Button surface="public" href="/admin/hiring" variant="outline" className="min-h-11">
                                Staff command center
                            </Button>
                        ) : null}
                    </div>
                </Card>
            ) : null}

            {eligibility && !eligibility.eligible && eligibility.reason && gate.hiring_active ? (
                <Card as="section" padding="compact">
                    <p className="text-sm text-ink">{eligibility.reason}</p>
                    {eligibility.next_action ? <p className="mt-2 text-sm text-muted">{eligibility.next_action}</p> : null}
                </Card>
            ) : null}

            {!gate.hiring_active ? (
                <form onSubmit={activate} className="space-y-4 rounded-xl border border-line bg-white p-4 sm:p-6">
                    <h2 className="text-lg font-semibold text-ink">Activate employer workspace</h2>
                    <p className="text-sm text-muted">
                        Enter your company details to access the employer dashboard. This does not verify your email or mobile
                        number.
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
                    <label className="block text-sm font-medium text-ink" htmlFor="company_phone">
                        Company phone number
                    </label>
                    <input
                        id="company_phone"
                        type="tel"
                        className="w-full rounded-md border border-line px-3 py-2 text-sm"
                        value={form.data.company_phone}
                        onChange={(event) => form.setData('company_phone', event.target.value)}
                    />
                    {errors.company_phone ? <p className="text-sm text-danger">{errors.company_phone}</p> : null}
                    <label className="block text-sm font-medium text-ink" htmlFor="company_email">
                        Company email address
                    </label>
                    <input
                        id="company_email"
                        type="email"
                        className="w-full rounded-md border border-line px-3 py-2 text-sm"
                        value={form.data.company_email}
                        onChange={(event) => form.setData('company_email', event.target.value)}
                    />
                    {errors.company_email ? <p className="text-sm text-danger">{errors.company_email}</p> : null}
                    <label className="block text-sm font-medium text-ink" htmlFor="industry_id">
                        Industry
                    </label>
                    <select
                        id="industry_id"
                        className="w-full rounded-md border border-line px-3 py-2 text-sm"
                        value={form.data.industry_id}
                        onChange={(event) => form.setData('industry_id', event.target.value)}
                    >
                        <option value="">Select industry</option>
                        {industries
                            .filter(
                                (industry) =>
                                    industry.selectable !== false || String(industry.id) === form.data.industry_id,
                            )
                            .map((industry) => (
                                <option key={industry.id} value={industry.id}>
                                    {industry.name}
                                </option>
                            ))}
                    </select>
                    {errors.industry_id ? <p className="text-sm text-danger">{errors.industry_id}</p> : null}
                    <div className="flex flex-wrap gap-3">
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="min-h-11 rounded-md bg-accent px-4 py-2 text-sm font-semibold text-white disabled:bg-disabled"
                        >
                            Activate workspace
                        </button>
                        <Link href="/account/settings/verification" className="inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline">
                            Verify contact details (optional)
                        </Link>
                    </div>
                </form>
            ) : null}
        </div>
    );
}
