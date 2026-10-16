import type { ComponentPropsWithoutRef, ElementType, PropsWithChildren } from 'react';
import { visuallyHidden } from '@/design-system/classNames';

export type VisuallyHiddenAs = 'span' | 'div' | 'p' | 'label';

type VisuallyHiddenOwnProps = {
    as?: VisuallyHiddenAs;
    className?: string;
};

export type VisuallyHiddenProps<T extends VisuallyHiddenAs = 'span'> = PropsWithChildren<
    VisuallyHiddenOwnProps & Omit<ComponentPropsWithoutRef<T>, keyof VisuallyHiddenOwnProps | 'children'>
>;

export default function VisuallyHidden<T extends VisuallyHiddenAs = 'span'>({
    as,
    className = '',
    children,
    ...rest
}: VisuallyHiddenProps<T>) {
    const Component = (as ?? 'span') as ElementType;
    const rootClass = `${visuallyHidden} ${className}`.trim();

    return (
        <Component className={rootClass} {...rest}>
            {children}
        </Component>
    );
}
