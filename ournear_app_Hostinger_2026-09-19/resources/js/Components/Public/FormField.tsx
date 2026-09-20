import type { InputHTMLAttributes, TextareaHTMLAttributes } from 'react';

type FieldProps = {
    label: string;
    id: string;
    as?: 'input' | 'textarea';
} & InputHTMLAttributes<HTMLInputElement> &
    TextareaHTMLAttributes<HTMLTextAreaElement>;

export default function FormField({ label, id, as = 'input', ...props }: FieldProps) {
    const classes =
        'w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-brand';

    return (
        <div>
            <label htmlFor={id} className="mb-1 block text-sm font-medium text-ink">
                {label}
            </label>
            {as === 'textarea' ? (
                <textarea id={id} className={`${classes} min-h-28`} {...props} />
            ) : (
                <input id={id} className={classes} {...props} />
            )}
        </div>
    );
}
