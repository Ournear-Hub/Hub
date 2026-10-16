import Input, { type InputProps, type InputSurface } from '@/Components/atoms/Input';
import OurNearIcon from '@/icons/OurNearIcon';
import { forwardRef } from 'react';

export type SearchFieldProps = Omit<InputProps, 'type' | 'className'> & {
    /** Wrapper */
    className?: string;
    /** Applied to the canonical Input (e.g. padding overrides). */
    inputClassName?: string;
};

/**
 * Search-oriented Input composition — icon + `type="search"`.
 * Does not own query state, debounce, or results.
 */
const SearchField = forwardRef<HTMLInputElement, SearchFieldProps>(function SearchField(
    { surface = 'public', error = false, className = '', inputClassName = '', ...inputProps },
    ref,
) {
    return (
        <div className={`relative ${className}`.trim()}>
            <span
                className="pointer-events-none absolute left-3 top-1/2 z-[1] -translate-y-1/2 text-muted"
                aria-hidden="true"
            >
                <OurNearIcon name="search" size="sm" decorative />
            </span>
            <Input
                ref={ref}
                type="search"
                surface={surface}
                error={error}
                className={`pl-9 ${inputClassName}`.trim()}
                {...inputProps}
            />
        </div>
    );
});

export type { InputSurface };

export default SearchField;
