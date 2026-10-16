import type { SelectHTMLAttributes } from 'react';
import CanonicalSelect from '@/Components/atoms/Select';

type SelectProps = SelectHTMLAttributes<HTMLSelectElement>;

/** Admin UI adapter — delegates to canonical Select with admin surface. */
export default function Select({ children, ...props }: SelectProps) {
    return (
        <CanonicalSelect surface="admin" {...props}>
            {children}
        </CanonicalSelect>
    );
}
