import { useForm, usePage } from '@inertiajs/react';
import AccountLayout from '@/Layouts/AccountLayout';
import Button from '@/Components/Public/Button';
import PageContainer from '@/Components/Public/PageContainer';
import type { SharedPageProps } from '@/types/page';

type Programme = { value: string; label: string };

type Application = {
    programme: string;
    programme_label: string;
    status_label: string;
    motivation: string | null;
    can_edit: boolean;
};

type PageProps = {
    programmes: Programme[];
    selected_programme: string;
    application: Application | null;
};

export default function Apply({ programmes, selected_programme, application }: PageProps) {
    const { flash } = usePage<SharedPageProps>().props;
    const form = useForm({
        programme: selected_programme || programmes[0]?.value || '',
        motivation: application?.motivation ?? '',
    });
    const locked = application !== null && !application.can_edit;

    return (
        <AccountLayout title="Development Programme">
            <PageContainer className="max-w-3xl py-8">
                <h1 className="text-2xl font-bold text-brand">Development Programme application</h1>
                <p className="mt-2 text-sm text-muted">
                    Submitting this form records your interest. It does not activate the programme or collect a fee. Payment details are shared privately after review.
                </p>
                {flash?.success ? <p className="mt-4 rounded-lg bg-brand/5 px-3 py-2 text-sm text-brand">{flash.success}</p> : null}
                {flash?.error ? <p className="mt-4 rounded-lg bg-accent/10 px-3 py-2 text-sm text-accent">{flash.error}</p> : null}
                {application ? (
                    <p className="mt-4 text-sm text-ink">
                        Current status: {application.programme_label} — {application.status_label}
                    </p>
                ) : null}
                <form
                    className="mt-6 space-y-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        if (!locked) {
                            form.post('/account/development-programme');
                        }
                    }}
                >
                    <label className="block text-sm font-semibold text-ink" htmlFor="programme">
                        Programme
                        <select
                            id="programme"
                            className="mt-1 w-full rounded-lg border border-line bg-white px-3 py-2 text-sm"
                            value={form.data.programme}
                            disabled={locked}
                            onChange={(event) => form.setData('programme', event.target.value)}
                        >
                            {programmes.map((programme) => (
                                <option key={programme.value} value={programme.value}>
                                    {programme.label}
                                </option>
                            ))}
                        </select>
                    </label>
                    {form.errors.programme ? <p className="text-sm text-accent">{form.errors.programme}</p> : null}
                    <label className="block text-sm font-semibold text-ink" htmlFor="motivation">
                        Why this programme
                        <textarea
                            id="motivation"
                            className="mt-1 min-h-32 w-full rounded-lg border border-line px-3 py-2 text-sm"
                            value={form.data.motivation}
                            disabled={locked}
                            onChange={(event) => form.setData('motivation', event.target.value)}
                        />
                    </label>
                    {form.errors.motivation ? <p className="text-sm text-accent">{form.errors.motivation}</p> : null}
                    <Button type="submit" disabled={locked || form.processing}>
                        {application ? 'Update application' : 'Submit application'}
                    </Button>
                </form>
            </PageContainer>
        </AccountLayout>
    );
}
