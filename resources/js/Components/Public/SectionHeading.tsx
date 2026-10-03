type SectionHeadingProps = {
    eyebrow?: string;
    title: string;
    description?: string;
    light?: boolean;
};

export default function SectionHeading({ eyebrow, title, description, light = false }: SectionHeadingProps) {
    return (
        <div className="mx-auto max-w-3xl text-center">
            {eyebrow && (
                <p className={`text-sm font-medium ${light ? 'text-accent' : 'text-accent'}`}>{eyebrow}</p>
            )}
            <h2 className={`mt-2 text-2xl font-semibold tracking-tight sm:text-3xl ${light ? 'text-white' : 'text-ink'}`}>
                {title}
            </h2>
            {description && (
                <p className={`mt-3 text-base ${light ? 'text-white/80' : 'text-muted'}`}>{description}</p>
            )}
        </div>
    );
}
