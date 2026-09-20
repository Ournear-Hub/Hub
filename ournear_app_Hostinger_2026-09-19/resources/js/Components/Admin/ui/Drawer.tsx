import { useEffect, type ReactNode } from 'react';
import Button from '@/Components/Admin/ui/Button';

type DrawerProps = {
    open: boolean;
    title: string;
    onClose: () => void;
    children: ReactNode;
    footer?: ReactNode;
    loading?: boolean;
    closeLabel?: string;
};

export default function Drawer({
    open,
    title,
    onClose,
    children,
    footer,
    loading = false,
    closeLabel = 'Close',
}: DrawerProps) {
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

    return (
        <div className="fixed inset-0 z-[60] flex justify-end">
            <button
                type="button"
                className="absolute inset-0 bg-ink/40"
                aria-label={closeLabel}
                onClick={onClose}
            />
            <aside
                role="dialog"
                aria-modal="true"
                aria-labelledby="admin-drawer-title"
                className="relative z-10 flex h-full w-full min-w-0 max-w-none flex-col bg-white sm:max-w-lg"
            >
                <div className="flex items-start justify-between gap-3 border-b border-line px-5 py-4">
                    <h2 id="admin-drawer-title" className="text-xl font-semibold text-ink">
                        {title}
                    </h2>
                    <Button variant="ghost" onClick={onClose} aria-label={closeLabel}>
                        Close
                    </Button>
                </div>
                <div className="min-h-0 flex-1 overflow-y-auto overflow-x-hidden px-5 py-4">
                    {loading ? <p className="text-sm text-muted">Loading…</p> : children}
                </div>
                {footer ? <div className="border-t border-line px-5 py-4">{footer}</div> : null}
            </aside>
        </div>
    );
}
