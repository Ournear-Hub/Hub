import type { FormEvent, ReactNode } from 'react';

type FilterBarProps = {
    onSubmit: (event: FormEvent<HTMLFormElement>) => void;
    children: ReactNode;
    className?: string;
};

export default function FilterBar({ onSubmit, children, className = '' }: FilterBarProps) {
    return (
        <form
            onSubmit={onSubmit}
            className={`mb-6 grid gap-3 rounded-xl border border-line bg-white p-4 md:grid-cols-4 ${className}`}
        >
            {children}
        </form>
    );
}
