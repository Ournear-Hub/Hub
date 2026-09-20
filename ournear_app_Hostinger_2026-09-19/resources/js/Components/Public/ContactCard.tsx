type ContactCardProps = {
    label: string;
    value: string;
};

export default function ContactCard({ label, value }: ContactCardProps) {
    return (
        <article className="rounded-xl border border-line bg-white p-5">
            <h3 className="text-sm font-semibold text-ink">{label}</h3>
            <p className="mt-2 text-sm text-muted">{value}</p>
        </article>
    );
}
