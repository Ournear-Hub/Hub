import type { ReactNode } from 'react';
import { typography } from '@/design-system/classNames';

type SectionHeaderProps = {
    title: string;
    description?: string;
    eyebrow?: string;
    action?: ReactNode;
    className?: string;
    headingLevel?: 'h2' | 'h3';
};

export default function SectionHeader({
    title,
    description,
    eyebrow,
    action,
    className = '',
    headingLevel = 'h2',
}: SectionHeaderProps) {
    const Heading = headingLevel;

    return (
        <div className={`flex flex-wrap items-start justify-between gap-3 ${className}`.trim()}>
            <div className="min-w-0">
                {eyebrow ? <p className={typography.eyebrow}>{eyebrow}</p> : null}
                <Heading className={eyebrow ? `mt-1 ${typography.sectionTitle}` : typography.sectionTitle}>{title}</Heading>
                {description ? <p className={`mt-1 max-w-2xl ${typography.secondary}`}>{description}</p> : null}
            </div>
            {action ? <div className="shrink-0">{action}</div> : null}
        </div>
    );
}
