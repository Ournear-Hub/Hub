import type { InputHTMLAttributes, MouseEvent } from 'react';

export const adminControlClass =
    'w-full min-h-10 rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-brand focus-visible:ring-2 focus-visible:ring-brand/20 disabled:bg-surface disabled:text-disabled';

type InputProps = InputHTMLAttributes<HTMLInputElement>;

function isPickerType(type: InputProps['type']): boolean {
    return type === 'date' || type === 'datetime-local' || type === 'time' || type === 'month' || type === 'week';
}

function openPicker(event: MouseEvent<HTMLInputElement>) {
    const input = event.currentTarget;
    if (typeof input.showPicker !== 'function' || input.disabled || input.readOnly) {
        return;
    }

    try {
        input.showPicker();
    } catch {
        // Native date input still works if the picker cannot open from this click.
    }
}

export default function Input({ className = '', type, onClick, ...props }: InputProps) {
    const picker = isPickerType(type);

    return (
        <input
            type={type}
            className={`${adminControlClass} ${picker ? 'cursor-pointer' : ''} ${className}`}
            onClick={(event) => {
                if (picker) {
                    openPicker(event);
                }
                onClick?.(event);
            }}
            {...props}
        />
    );
}
