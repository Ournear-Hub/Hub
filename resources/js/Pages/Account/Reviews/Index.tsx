import { useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AccountLayout from '@/Layouts/AccountLayout';
import type { SharedPageProps } from '@/types/page';

type Review = {
    id: number;
    business_id: string | null;
    direction: string;
    direction_label: string;
    rating: number;
    body: string;
    visibility: string;
    status: string;
    status_label: string;
    created_at: string | null;
};

type Relationship = {
    id: number;
    business_id: string | null;
    worker_name: string | null;
    employer_name: string | null;
    job_title: string | null;
    joined_at: string | null;
    own_review: Review | null;
    counterpart_review: Review | null;
    can_submit: boolean;
};

type Props = {
    relationships: Relationship[];
};

function stars(rating: number): string {
    return `${'★'.repeat(rating)}${'☆'.repeat(Math.max(0, 5 - rating))}`;
}

function ReviewCard({ title, review }: { title: string; review: Review }) {
    return (
        <div className="rounded-md bg-surface px-3 py-2 text-sm">
            <p className="font-medium text-ink">{title}</p>
            <p className="mt-1 text-brand" aria-label={`Rating ${review.rating} of 5`}>
                {stars(review.rating)}
            </p>
            <p className="mt-2 whitespace-pre-wrap text-ink">{review.body}</p>
            <p className="mt-2 text-xs text-muted">
                {review.created_at ?? '—'} · {review.status_label}
            </p>
        </div>
    );
}

export default function WorkerReviewsIndex({ relationships }: Props) {
    const flash = usePage<SharedPageProps>().props.flash;

    return (
        <AccountLayout
            title="Reviews"
            description="Private reviews for employers you joined. These are not shown on public profiles."
            breadcrumbs={[
                { label: 'Account', href: '/account' },
                { label: 'Reviews' },
            ]}
        >
            {flash.success ? (
                <p className="mb-4 rounded-md border border-success/20 bg-success/10 px-3 py-2 text-sm text-success">
                    {flash.success}
                </p>
            ) : null}
            {relationships.length === 0 ? (
                <p className="rounded-xl border border-dashed border-line bg-white p-8 text-center text-sm text-muted">
                    You can review an employer after you have joined as an employee.
                </p>
            ) : (
                <div className="space-y-4">
                    {relationships.map((relationship) => (
                        <WorkerRelationshipCard key={relationship.id} relationship={relationship} />
                    ))}
                </div>
            )}
        </AccountLayout>
    );
}

function WorkerRelationshipCard({ relationship }: { relationship: Relationship }) {
    const form = useForm({
        rating: 5,
        body: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(`/account/reviews/${relationship.id}`);
    };

    return (
        <article className="rounded-xl border border-line bg-white p-5">
            <h2 className="text-lg font-semibold text-ink">{relationship.employer_name ?? 'Employer'}</h2>
            <p className="text-sm text-muted">
                {relationship.job_title ?? 'Job'}
                {relationship.joined_at ? ` · Joined ${relationship.joined_at}` : ''}
            </p>
            {relationship.own_review ? <div className="mt-3"><ReviewCard title="Your review" review={relationship.own_review} /></div> : null}
            {relationship.can_submit ? (
                <form onSubmit={submit} className="mt-3 space-y-3">
                    <fieldset>
                        <legend className="text-sm font-medium text-ink">Rating</legend>
                        <div className="mt-1 flex gap-3">
                            {[1, 2, 3, 4, 5].map((value) => (
                                <label key={value} className="flex items-center gap-1 text-sm text-ink">
                                    <input
                                        type="radio"
                                        name={`worker-rating-${relationship.id}`}
                                        value={value}
                                        checked={form.data.rating === value}
                                        onChange={() => form.setData('rating', value)}
                                    />
                                    {value}
                                </label>
                            ))}
                        </div>
                        {form.errors.rating ? <p className="text-sm text-danger">{form.errors.rating}</p> : null}
                    </fieldset>
                    <label className="block text-sm font-medium text-ink" htmlFor={`worker-review-body-${relationship.id}`}>
                        Feedback
                    </label>
                    <textarea
                        id={`worker-review-body-${relationship.id}`}
                        className="w-full rounded-md border border-line px-3 py-2 text-sm"
                        rows={4}
                        value={form.data.body}
                        onChange={(event) => form.setData('body', event.target.value)}
                    />
                    {form.errors.body ? <p className="text-sm text-danger">{form.errors.body}</p> : null}
                    {(form.errors as Record<string, string | undefined>).employee ? (
                        <p className="text-sm text-danger">{(form.errors as Record<string, string | undefined>).employee}</p>
                    ) : null}
                    <button
                        type="submit"
                        className="rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
                        disabled={form.processing}
                    >
                        Submit review
                    </button>
                </form>
            ) : null}
            {relationship.counterpart_review ? (
                <div className="mt-3">
                    <ReviewCard title="Employer review" review={relationship.counterpart_review} />
                </div>
            ) : null}
        </article>
    );
}
