type EmptyStateProps = {
    title: string;
    description?: string;
};

export default function EmptyState({ title, description }: EmptyStateProps) {
    return (
        <div className="px-4 py-10 text-center">
            <p className="text-sm font-medium text-ink">{title}</p>
            {description ? <p className="mt-1 text-sm text-muted">{description}</p> : null}
        </div>
    );
}
