import type { ReactNode } from 'react';
import { typography } from '@/design-system/classNames';

type Props = {
    title: string;
    description?: string;
    action?: ReactNode;
    className?: string;
};

export default function EmptyState({ title, description, action, className = '' }: Props) {
    return (
        <div
            className={`rounded-xl border border-dashed border-line bg-white px-6 py-10 text-center ${className}`.trim()}
        >
            <h2 className={typography.sectionTitle}>{title}</h2>
            {description ? <p className={`mx-auto mt-2 max-w-md ${typography.secondary}`}>{description}</p> : null}
            {action ? <div className="mt-4 flex justify-center">{action}</div> : null}
        </div>
    );
}
