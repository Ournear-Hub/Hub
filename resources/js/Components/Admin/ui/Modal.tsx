import { useEffect, type ReactNode } from 'react';
import Button from '@/Components/Admin/ui/Button';

type ModalProps = {
    open: boolean;
    title: string;
    onClose: () => void;
    children: ReactNode;
    footer?: ReactNode;
};

export default function Modal({ open, title, onClose, children, footer }: ModalProps) {
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
        <div className="fixed inset-0 z-[70] flex items-end justify-center p-4 sm:items-center">
            <button type="button" className="absolute inset-0 bg-ink/40" aria-label="Close dialog" onClick={onClose} />
            <div
                role="dialog"
                aria-modal="true"
                aria-labelledby="admin-modal-title"
                className="relative z-10 w-full max-w-md rounded-xl border border-line bg-white p-5"
            >
                <div className="flex items-start justify-between gap-3">
                    <h2 id="admin-modal-title" className="text-lg font-semibold text-ink">
                        {title}
                    </h2>
                    <Button variant="ghost" onClick={onClose} aria-label="Close dialog">
                        Close
                    </Button>
                </div>
                <div className="mt-4 text-sm text-muted">{children}</div>
                {footer ? <div className="mt-5 flex flex-wrap justify-end gap-2">{footer}</div> : null}
            </div>
        </div>
    );
}
