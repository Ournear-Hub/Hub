import Button from '@/Components/atoms/Button';
import type { AccountCandidateNextAction } from '@/types/account';

type Props = {
    action: AccountCandidateNextAction;
};

export default function CandidateProfileCompletionPrompt({ action }: Props) {
    if (action.title !== 'Complete Your Personal & Professional Information') {
        return null;
    }

    return (
        <section
            className="rounded-xl border border-brand/25 bg-brand/[0.06] p-4 sm:p-5"
            aria-labelledby="profile-completion-prompt-heading"
        >
            <h2 id="profile-completion-prompt-heading" className="text-base font-semibold text-ink sm:text-lg">
                {action.title}
            </h2>
            <p className="mt-2 text-sm text-muted">{action.description}</p>
            <Button surface="public" href={action.href} variant="primary" className="mt-4 min-h-10">
                {action.cta}
            </Button>
        </section>
    );
}
