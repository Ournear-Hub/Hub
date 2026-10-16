import PageContainer from '@/Components/Public/PageContainer';
import Button from '@/Components/atoms/Button';

type CtaSectionProps = {
    title: string;
    description: string;
    actionLabel: string;
    actionHref: string;
};

export default function CtaSection({ title, description, actionLabel, actionHref }: CtaSectionProps) {
    return (
        <section className="bg-brand py-14">
            <PageContainer className="flex flex-col items-start justify-between gap-6 sm:flex-row sm:items-center">
                <div className="max-w-2xl">
                    <h2 className="text-2xl font-semibold text-white sm:text-3xl">{title}</h2>
                    <p className="mt-2 text-white/80">{description}</p>
                </div>
                <Button surface="public" href={actionHref} variant="accent">
                    {actionLabel}
                </Button>
            </PageContainer>
        </section>
    );
}
