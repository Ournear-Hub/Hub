import {
    inputGroupActionSlot,
    inputGroupAddon,
    inputGroupAddonLeading,
    inputGroupAddonTrailing,
    inputGroupControlAfterLeading,
    inputGroupControlBeforeAction,
    inputGroupControlBeforeTrailing,
    inputGroupControlSlot,
    inputGroupRoot,
} from '@/design-system/classNames';
import { Children, isValidElement, type ReactElement, type ReactNode } from 'react';

export type InputGroupProps = {
    /** Non-interactive prefix (e.g. https://, $). Decorative by default — not the control accessible name. */
    leading?: ReactNode;
    /** Non-interactive suffix (e.g. kg, %). Decorative by default. */
    trailing?: ReactNode;
    /** Adjacent interactive control (e.g. Button). Remains independently accessible. */
    action?: ReactNode;
    className?: string;
    children: ReactNode;
};

function Addon({ position, children }: { position: 'leading' | 'trailing'; children: ReactNode }) {
    const edge = position === 'leading' ? inputGroupAddonLeading : inputGroupAddonTrailing;
    return (
        <span className={`${inputGroupAddon} ${edge}`.trim()} aria-hidden="true">
            {children}
        </span>
    );
}

export default function InputGroup({ leading, trailing, action, className = '', children }: InputGroupProps) {
    const control = Children.only(children);

    const controlJoin = [
        inputGroupControlSlot,
        leading ? inputGroupControlAfterLeading : '',
        trailing ? inputGroupControlBeforeTrailing : '',
        action ? inputGroupControlBeforeAction : '',
    ]
        .filter(Boolean)
        .join(' ');

    const rootClass = `${inputGroupRoot} ${className}`.trim();

    return (
        <div className={rootClass}>
            {leading != null && leading !== false ? <Addon position="leading">{leading}</Addon> : null}
            <div className={controlJoin}>{control}</div>
            {trailing != null && trailing !== false ? <Addon position="trailing">{trailing}</Addon> : null}
            {action != null && action !== false ? (
                <div className={inputGroupActionSlot}>{action}</div>
            ) : null}
        </div>
    );
}

/** Used by FormField to forward id/ARIA to the nested control without InputGroup cloning children. */
export function isInputGroupElement(node: ReactNode): node is ReactElement<InputGroupProps> {
    return isValidElement(node) && node.type === InputGroup;
}
