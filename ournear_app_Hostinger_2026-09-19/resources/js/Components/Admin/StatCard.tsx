type StatCardProps = {
    label: string;
    value: number | null;
};

export default function StatCard({ label, value }: StatCardProps) {
    return (
        <article className="rounded-xl border border-line bg-white p-4 sm:p-5">
            <p className="text-sm text-muted">{label}</p>
            <p className="mt-2 text-2xl font-semibold text-ink">{value === null ? '—' : value}</p>
            {value === null && (
                <p className="mt-1 text-xs text-disabled">Available when domain data is added</p>
            )}
        </article>
    );
}
