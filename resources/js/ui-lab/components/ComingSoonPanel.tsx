export default function ComingSoonPanel({ label }: { label: string }) {
    return (
        <div className="rounded-xl border border-dashed border-line bg-surface/50 p-8 text-center">
            <p className="text-sm font-semibold uppercase tracking-wide text-muted">{label}</p>
            <p className="mt-2 text-lg font-semibold text-ink">Coming later</p>
            <p className="mt-2 text-sm text-muted">This section will appear when the library item is implemented and approved.</p>
        </div>
    );
}
