import Button from '@/Components/atoms/Button';
import {
    dialogBody,
    dialogFooter,
    dialogHeader,
    dialogOverlay,
    dialogPanel,
    dialogTitle,
} from '@/design-system/classNames';
import { useEffect, useId, type ReactNode } from 'react';

export type DialogProps = {
    open: boolean;
    onClose: () => void;
    title: ReactNode;
    /** Optional supporting copy; wired to `aria-describedby` when present. */
    description?: ReactNode;
    children: ReactNode;
    /** Action row (buttons). Caller-owned labels and handlers. */
    footer?: ReactNode;
    /** Use when `title` is not a plain string and no visible title is rendered. */
    'aria-label'?: string;
    className?: string;
};

export default function Dialog({
    open,
    onClose,
    title,
    description,
    children,
    footer,
    'aria-label': ariaLabel,
    className = '',
}: DialogProps) {
    const titleId = useId();
    const descriptionId = useId();

    useEffect(() => {
        if (!open) {
            return;
        }

        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                onClose();
            }
        };

        window.addEventListener('keydown', onKeyDown);
        return () => window.removeEventListener('keydown', onKeyDown);
    }, [open, onClose]);

    if (!open) {
        return null;
    }

    const panelClass = `${dialogPanel} ${className}`.trim();
    const describedBy = description ? descriptionId : undefined;

    return (
        <div className={dialogOverlay}>
            <button type="button" className="absolute inset-0 bg-ink/40" aria-label="Close dialog" onClick={onClose} />
            <div
                role="dialog"
                aria-modal="true"
                aria-label={ariaLabel}
                aria-labelledby={ariaLabel ? undefined : titleId}
                aria-describedby={describedBy}
                className={panelClass}
            >
                <div className={dialogHeader}>
                    <h2 id={titleId} className={dialogTitle}>
                        {title}
                    </h2>
                    <Button type="button" surface="admin" variant="ghost" onClick={onClose} aria-label="Close dialog">
                        Close
                    </Button>
                </div>
                {description ? (
                    <p id={descriptionId} className="mt-4 text-sm text-muted">
                        {description}
                    </p>
                ) : null}
                {children ? <div className={dialogBody}>{children}</div> : null}
                {footer ? <div className={dialogFooter}>{footer}</div> : null}
            </div>
        </div>
    );
}
