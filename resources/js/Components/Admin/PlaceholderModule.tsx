type PlaceholderModuleProps = {
    title: string;
    description: string;
};

export default function PlaceholderModule({ title, description }: PlaceholderModuleProps) {
    return (
        <section className="rounded-xl border border-line bg-white p-6 sm:p-8">
            <h1 className="text-2xl font-semibold tracking-tight text-ink">{title}</h1>
            <p className="mt-3 max-w-2xl text-sm text-muted">{description}</p>
            <p className="mt-6 rounded-md bg-surface px-3 py-2 text-sm text-muted">
                This screen is ready. Records and actions will appear after the related database
                work is implemented. No sample data is shown.
            </p>
        </section>
    );
}
