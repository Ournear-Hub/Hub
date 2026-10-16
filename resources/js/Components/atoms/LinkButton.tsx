import { forwardRef, type AnchorHTMLAttributes, type MouseEvent, type PropsWithChildren, type ReactNode } from 'react';
import type { ButtonSize, ButtonSurface, ButtonVariant } from '@/Components/atoms/Button';
import { linkButtonClassName } from '@/design-system/buttonVisuals';

export type LinkButtonProps = PropsWithChildren<
    {
        href: string;
        surface?: ButtonSurface;
        variant?: ButtonVariant;
        size?: ButtonSize;
        fullWidth?: boolean;
        leadingIcon?: ReactNode;
        trailingIcon?: ReactNode;
        disabled?: boolean;
        className?: string;
    } & Omit<AnchorHTMLAttributes<HTMLAnchorElement>, 'href' | 'className' | 'children'>
>;

const LinkButton = forwardRef<HTMLAnchorElement, LinkButtonProps>(function LinkButton(
    {
        href,
        surface = 'public',
        variant = 'primary',
        size = 'md',
        fullWidth = false,
        leadingIcon,
        trailingIcon,
        disabled = false,
        className = '',
        onClick,
        tabIndex,
        children,
        ...rest
    },
    ref,
) {
    const classes = linkButtonClassName({
        surface,
        variant,
        size,
        fullWidth,
        disabled,
        className,
    });

    const handleClick = (event: MouseEvent<HTMLAnchorElement>) => {
        if (disabled) {
            event.preventDefault();
            event.stopPropagation();
            return;
        }
        onClick?.(event);
    };

    return (
        <a
            ref={ref}
            href={href}
            className={classes}
            aria-disabled={disabled || undefined}
            tabIndex={disabled ? -1 : tabIndex}
            onClick={handleClick}
            {...rest}
        >
            {leadingIcon ? <span className="inline-flex shrink-0">{leadingIcon}</span> : null}
            {children}
            {trailingIcon ? <span className="inline-flex shrink-0">{trailingIcon}</span> : null}
        </a>
    );
});

export default LinkButton;
