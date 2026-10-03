import type { LabelHTMLAttributes, ReactNode } from 'react';

type LabelProps = LabelHTMLAttributes<HTMLLabelElement> & {
    children: ReactNode;
};

export default function Label({ className = '', children, ...props }: LabelProps) {
    return (
        <label className={`mb-1 block text-sm font-medium text-ink ${className}`} {...props}>
            {children}
        </label>
    );
}
