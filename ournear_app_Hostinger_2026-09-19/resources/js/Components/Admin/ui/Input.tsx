import type { InputHTMLAttributes } from 'react';

export const adminControlClass =
    'w-full min-h-10 rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-brand focus-visible:ring-2 focus-visible:ring-brand/20 disabled:bg-surface disabled:text-disabled';

type InputProps = InputHTMLAttributes<HTMLInputElement>;

export default function Input({ className = '', ...props }: InputProps) {
    return <input className={`${adminControlClass} ${className}`} {...props} />;
}
