import type { InputSurface } from '@/Components/atoms/Input';
import { controlError, fileInputAdminBase, fileInputBase } from '@/design-system/classNames';
import { forwardRef, type InputHTMLAttributes } from 'react';

export type FileFieldProps = Omit<InputHTMLAttributes<HTMLInputElement>, 'type' | 'className'> & {
    surface?: InputSurface;
    error?: boolean;
    /** Wrapper element */
    className?: string;
    inputClassName?: string;
};

function isAdminFamily(surface: InputSurface): boolean {
    return surface === 'admin' || surface === 'corporate';
}

function fileInputClasses(surface: InputSurface, error: boolean, inputClassName: string): string {
    const base = isAdminFamily(surface) ? fileInputAdminBase : fileInputBase;
    const errorClass = error ? controlError : '';
    return `${base} ${errorClass} ${inputClassName}`.trim();
}

/**
 * Native file selection control — no upload, preview, or validation logic.
 */
const FileField = forwardRef<HTMLInputElement, FileFieldProps>(function FileField(
    {
        surface = 'public',
        error = false,
        className = '',
        inputClassName = '',
        'aria-invalid': ariaInvalid,
        ...props
    },
    ref,
) {
    const invalid = error || ariaInvalid === true || ariaInvalid === 'true';

    return (
        <div className={className || undefined}>
            <input
                ref={ref}
                type="file"
                className={fileInputClasses(surface, error, inputClassName)}
                aria-invalid={invalid ? true : ariaInvalid}
                {...props}
            />
        </div>
    );
});

export type { InputSurface };

export default FileField;
