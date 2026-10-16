import { forwardRef, type InputHTMLAttributes } from 'react';
import type { InputSurface } from '@/Components/atoms/Input';
import { switchInput, switchTrackAdmin, switchTrackBase } from '@/design-system/classNames';

export type SwitchProps = Omit<InputHTMLAttributes<HTMLInputElement>, 'type' | 'className'> & {
    surface?: InputSurface;
    className?: string;
};

function isAdminFamily(surface: InputSurface): boolean {
    return surface === 'admin' || surface === 'corporate';
}

function trackClasses(surface: InputSurface): string {
    return isAdminFamily(surface) ? switchTrackAdmin : switchTrackBase;
}

/**
 * Boolean ON/OFF control — native checkbox + role="switch" for assistive tech;
 * checked = ON, unchecked = OFF. No internal state.
 */
const Switch = forwardRef<HTMLInputElement, SwitchProps>(function Switch(
    { surface = 'public', className = '', ...props },
    ref,
) {
    return (
        <span className={`inline-flex align-middle ${className}`.trim()}>
            <input ref={ref} type="checkbox" role="switch" className={switchInput} {...props} />
            <span className={trackClasses(surface)} aria-hidden="true" />
        </span>
    );
});

export default Switch;
