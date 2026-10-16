import {
    emptyStateActions,
    emptyStateActionsCenter,
    emptyStateActionsStart,
    emptyStateAlignCenter,
    emptyStateAlignStart,
    emptyStateDescription,
    emptyStateRoot,
    emptyStateSizeLg,
    emptyStateSizeMd,
    emptyStateSizeSm,
    emptyStateTitleLg,
    emptyStateTitleMd,
    emptyStateTitleSm,
} from '@/design-system/classNames';
import OurNearIcon from '@/icons/OurNearIcon';
import { isValidElement, type ReactNode } from 'react';

export type EmptyStateAlign = 'center' | 'start';
export type EmptyStateSize = 'sm' | 'md' | 'lg';

export type EmptyStateProps = {
    /** Registry icon name or custom node. */
    icon?: string | ReactNode;
    title?: string;
    description?: string;
    action?: ReactNode;
    secondaryAction?: ReactNode;
    align?: EmptyStateAlign;
    size?: EmptyStateSize;
    className?: string;
    children?: ReactNode;
};

const sizeClass: Record<EmptyStateSize, string> = {
    sm: emptyStateSizeSm,
    md: emptyStateSizeMd,
    lg: emptyStateSizeLg,
};

const titleClass: Record<EmptyStateSize, string> = {
    sm: emptyStateTitleSm,
    md: emptyStateTitleMd,
    lg: emptyStateTitleLg,
};

function renderIcon(icon: string | ReactNode, size: EmptyStateSize) {
    if (typeof icon === 'string') {
        const iconSize = size === 'lg' ? 'md' : 'sm';
        return <OurNearIcon name={icon} size={iconSize} decorative className="shrink-0 text-muted" />;
    }
    if (isValidElement(icon) || icon) {
        return <span className="shrink-0 text-muted">{icon}</span>;
    }
    return null;
}

export default function EmptyState({
    icon,
    title,
    description,
    action,
    secondaryAction,
    align = 'center',
    size = 'md',
    className = '',
    children,
}: EmptyStateProps) {
    const hasActions = Boolean(action || secondaryAction);
    const alignClass = align === 'start' ? emptyStateAlignStart : emptyStateAlignCenter;
    const actionsAlign = align === 'start' ? emptyStateActionsStart : emptyStateActionsCenter;
    const rootClass =
        `${emptyStateRoot} ${alignClass} ${sizeClass[size]} ${className}`.trim();
    const descriptionClass = `${emptyStateDescription} ${align === 'center' ? 'mx-auto' : ''}`.trim();

    return (
        <div className={rootClass}>
            {icon ? renderIcon(icon, size) : null}
            {title ? <p className={titleClass[size]}>{title}</p> : null}
            {description ? <p className={descriptionClass}>{description}</p> : null}
            {children ? <div className="w-full text-sm text-ink">{children}</div> : null}
            {hasActions ? (
                <div className={`${emptyStateActions} ${actionsAlign}`.trim()}>
                    {action}
                    {secondaryAction}
                </div>
            ) : null}
        </div>
    );
}
