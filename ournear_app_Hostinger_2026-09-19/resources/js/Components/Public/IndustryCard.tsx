type IndustryCardProps = {
    name: string;
};

export default function IndustryCard({ name }: IndustryCardProps) {
    return (
        <article className="rounded-xl border border-line bg-white px-4 py-6 text-center">
            <h3 className="text-sm font-semibold text-ink">{name}</h3>
        </article>
    );
}
