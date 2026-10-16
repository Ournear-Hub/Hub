import IconButton from '@/Components/atoms/IconButton';
import {
    alertBorderError,
    alertBorderInfo,
    alertBorderSuccess,
    alertBorderWarning,
    alertContent,
    alertMessage,
    alertRoot,
    alertTitle,
    statusTone,
} from '@/design-system/classNames';
import OurNearIcon from '@/icons/OurNearIcon';
import type { ReactNode } from 'react';

export type AlertVariant = 'info' | 'success' | 'warning' | 'error';

export type AlertProps = {
    variant: AlertVariant;
    title?: string;
    /** Show default variant icon (default true). */
    icon?: boolean;
    dismissible?: boolean;
    onDismiss?: () => void;
    className?: string;
    children: ReactNode;
};

const variantTone: Record<AlertVariant, keyof typeof statusTone> = {
    info: 'info',
    success: 'success',
    warning: 'warning',
    error: 'error',
};

const variantBorder: Record<AlertVariant, string> = {
    info: alertBorderInfo,
    success: alertBorderSuccess,
    warning: alertBorderWarning,
    error: alertBorderError,
};

const variantIcon: Record<AlertVariant, string> = {
    info: 'info',
    success: 'check',
    warning: 'alert',
    error: 'alert',
};

function alertRole(variant: AlertVariant): 'alert' | 'status' {
    return variant === 'error' ? 'alert' : 'status';
}

export default function Alert({
    variant,
    title,
    icon = true,
    dismissible = false,
    onDismiss,
    className = '',
    children,
}: AlertProps) {
    if (dismissible && !onDismiss && import.meta.env.DEV) {
        console.warn('[Alert] dismissible requires onDismiss — caller controls visibility.');
    }

    const toneClass = statusTone[variantTone[variant]];
    const rootClass = `${alertRoot} ${variantBorder[variant]} ${toneClass} ${className}`.trim();
    const role = alertRole(variant);

    return (
        <div role={role} className={rootClass}>
            {icon ? (
                <OurNearIcon name={variantIcon[variant]} size="sm" decorative className="mt-0.5 shrink-0" />
            ) : null}
            <div className={alertContent}>
                {title ? <p className={alertTitle}>{title}</p> : null}
                <div className={`${alertMessage} ${title ? 'mt-1' : ''}`.trim()}>{children}</div>
            </div>
            {dismissible ? (
                <IconButton
                    icon="close"
                    label="Dismiss"
                    variant="ghost"
                    size="sm"
                    className="-mr-1 -mt-1 shrink-0"
                    onClick={onDismiss}
                />
            ) : null}
        </div>
    );
}
