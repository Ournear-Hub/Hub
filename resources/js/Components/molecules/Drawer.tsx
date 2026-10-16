import Button from '@/Components/atoms/Button';
import {
    drawerBody,
    drawerFooter,
    drawerHeader,
    drawerOverlay,
    drawerPanel,
    drawerPanelSizeXl,
    drawerTitle,
} from '@/design-system/classNames';
import { useEffect, useId, type ReactNode } from 'react';

export type DrawerSize = 'md' | 'xl';

export type DrawerProps = {
    open: boolean;
    onClose: () => void;
    title: ReactNode;
    description?: ReactNode;
    children: ReactNode;
    footer?: ReactNode;
    /** Panel max-width on `sm+` (V2 default `md` → `sm:max-w-lg`; `xl` → `sm:max-w-3xl`). */
    size?: DrawerSize;
    'aria-label'?: string;
    className?: string;
};

export default function Drawer({
    open,
    onClose,
    title,
    description,
    children,
    footer,
    size = 'md',
    'aria-label': ariaLabel,
    className = '',
}: DrawerProps) {
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

    const panelClass = `${drawerPanel} ${size === 'xl' ? drawerPanelSizeXl : ''} ${className}`.trim();
    const describedBy = description ? descriptionId : undefined;

    return (
        <div className={drawerOverlay}>
            <button type="button" className="absolute inset-0 bg-ink/40" aria-label="Close drawer" onClick={onClose} />
            <aside
                role="dialog"
                aria-modal="true"
                aria-label={ariaLabel}
                aria-labelledby={ariaLabel ? undefined : titleId}
                aria-describedby={describedBy}
                className={panelClass}
            >
                <div className={drawerHeader}>
                    <h2 id={titleId} className={drawerTitle}>
                        {title}
                    </h2>
                    <Button type="button" surface="admin" variant="ghost" onClick={onClose} aria-label="Close drawer">
                        Close
                    </Button>
                </div>
                {description ? (
                    <p id={descriptionId} className="border-b border-line px-5 py-3 text-sm text-muted">
                        {description}
                    </p>
                ) : null}
                {children ? <div className={drawerBody}>{children}</div> : null}
                {footer ? <div className={drawerFooter}>{footer}</div> : null}
            </aside>
        </div>
    );
}
