import type { SelectHTMLAttributes } from 'react';
import { adminControlClass } from '@/Components/Admin/ui/Input';

type SelectProps = SelectHTMLAttributes<HTMLSelectElement>;

export default function Select({ className = '', children, ...props }: SelectProps) {
    return (
        <select className={`${adminControlClass} ${className}`} {...props}>
            {children}
        </select>
    );
}
