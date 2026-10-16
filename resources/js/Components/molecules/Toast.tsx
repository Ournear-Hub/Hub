import IconButton from '@/Components/atoms/IconButton';
import {
    alertBorderError,
    alertBorderInfo,
    alertBorderSuccess,
    alertBorderWarning,
    alertContent,
    alertMessage,
    alertTitle,
    statusTone,
    toastPlacementBottomEnd,
    toastPlacementBottomStart,
    toastPlacementTopEnd,
    toastPlacementTopStart,
    toastRoot,
} from '@/design-system/classNames';
import OurNearIcon from '@/icons/OurNearIcon';
import { useCallback, useEffect, useRef, type ReactNode } from 'react';
import { createPortal } from 'react-dom';

export type ToastVariant = 'info' | 'success' | 'warning' | 'error';
export type ToastPlacement = 'bottom-end' | 'bottom-start' | 'top-end' | 'top-start';

const DEFAULT_DURATION_MS = 5000;

export type ToastProps = {
    variant: ToastVariant;
    title?: string;
    /** Show default variant icon (default true). */
    icon?: boolean;
    /** Auto-dismiss after ms; `0` disables (default 5000). */
    duration?: number;
    dismissible?: boolean;
    onDismiss?: () => void;
    placement?: ToastPlacement;
    className?: string;
    children: ReactNode;
};

const variantTone: Record<ToastVariant, keyof typeof statusTone> = {
    info: 'info',
    success: 'success',
    warning: 'warning',
    error: 'error',
};

const variantBorder: Record<ToastVariant, string> = {
    info: alertBorderInfo,
    success: alertBorderSuccess,
    warning: alertBorderWarning,
    error: alertBorderError,
};

const variantIcon: Record<ToastVariant, string> = {
    info: 'info',
    success: 'check',
    warning: 'alert',
    error: 'alert',
};

const placementClass: Record<ToastPlacement, string> = {
    'bottom-end': toastPlacementBottomEnd,
    'bottom-start': toastPlacementBottomStart,
    'top-end': toastPlacementTopEnd,
    'top-start': toastPlacementTopStart,
};

function toastRole(variant: ToastVariant): 'alert' | 'status' {
    return variant === 'error' ? 'alert' : 'status';
}

export default function Toast({
    variant,
    title,
    icon = true,
    duration = DEFAULT_DURATION_MS,
    dismissible = true,
    onDismiss,
    placement = 'bottom-end',
    className = '',
    children,
}: ToastProps) {
    const timerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    const needsDismissHandler = dismissible || duration > 0;
    if (needsDismissHandler && !onDismiss && import.meta.env.DEV) {
        console.warn('[Toast] onDismiss is required when dismissible or duration is set — caller controls visibility.');
    }

    const dismiss = useCallback(() => {
        if (timerRef.current !== null) {
            clearTimeout(timerRef.current);
            timerRef.current = null;
        }
        onDismiss?.();
    }, [onDismiss]);

    useEffect(() => {
        if (duration <= 0) {
            return;
        }
        timerRef.current = setTimeout(() => {
            dismiss();
        }, duration);
        return () => {
            if (timerRef.current !== null) {
                clearTimeout(timerRef.current);
                timerRef.current = null;
            }
        };
    }, [dismiss, duration]);

    const toneClass = statusTone[variantTone[variant]];
    const surfaceClass = `${toastRoot} ${variantBorder[variant]} ${toneClass} ${className}`.trim();
    const role = toastRole(variant);
    const anchorClass = placementClass[placement];

    const toast = (
        <div className={anchorClass}>
            <div role={role} className={surfaceClass}>
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
                        onClick={dismiss}
                    />
                ) : null}
            </div>
        </div>
    );

    return createPortal(toast, document.body);
}
