import PageContainer from '@/Components/Public/PageContainer';

export type HomeGlanceStat = {
    key: string;
    label: string;
    value: number;
    formatted: string;
};

type Props = {
    stats: HomeGlanceStat[];
};

export default function HomeAtAGlance({ stats }: Props) {
    if (stats.length === 0) {
        return null;
    }

    return (
        <section className="border-b border-line bg-white py-10 md:py-12" aria-labelledby="home-at-a-glance-heading">
            <PageContainer className="min-w-0">
                <h2 id="home-at-a-glance-heading" className="text-center text-xl font-semibold text-brand md:text-2xl">
                    OurNear Hire at a Glance
                </h2>
                <div className="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {stats.map((stat) => (
                        <div
                            key={stat.key}
                            className="rounded-xl border border-line bg-white px-4 py-6 text-center shadow-sm"
                        >
                            <p className="text-3xl font-bold tabular-nums text-[#F15A24] md:text-4xl">{stat.formatted}</p>
                            <p className="mt-2 text-sm font-semibold text-[#214449]">{stat.label}</p>
                        </div>
                    ))}
                </div>
            </PageContainer>
        </section>
    );
}
