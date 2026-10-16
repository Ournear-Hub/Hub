import { filterBarRoot } from '@/design-system/classNames';
import type { FormEvent, ReactNode } from 'react';

export type FilterBarProps = {
    onSubmit: (event: FormEvent<HTMLFormElement>) => void;
    children: ReactNode;
    className?: string;
};

export default function FilterBar({ onSubmit, children, className = '' }: FilterBarProps) {
    return (
        <form onSubmit={onSubmit} className={`${filterBarRoot} ${className}`.trim()}>
            {children}
        </form>
    );
}
