import type { PropsWithChildren, ReactNode } from 'react';
import Button from '@/Components/Public/Button';

type Props = PropsWithChildren<{
    title: string;
    description?: string;
    onBack: () => void;
    footer?: ReactNode;
}>;

export default function ProfileModuleEditorShell({ title, description, onBack, footer, children }: Props) {
    return (
        <div className="mt-6 space-y-4 pb-[calc(1rem+env(safe-area-inset-bottom,0px))]">
            <div className="flex flex-wrap items-center gap-3">
                <Button type="button" variant="ghost" className="min-h-11 px-2 text-sm" onClick={onBack}>
                    ← Back
                </Button>
                <div className="min-w-0">
                    <h2 className="text-lg font-semibold text-ink">{title}</h2>
                    {description ? <p className="mt-0.5 text-sm text-muted">{description}</p> : null}
                </div>
            </div>
            <div className="rounded-xl border border-line bg-white p-4 sm:p-6">{children}</div>
            {footer ? (
                <div className="sticky bottom-0 z-10 -mx-1 border-t border-line bg-canvas/95 px-1 py-3 backdrop-blur-[2px] sm:static sm:border-0 sm:bg-transparent sm:p-0">
                    {footer}
                </div>
            ) : null}
        </div>
    );
}
