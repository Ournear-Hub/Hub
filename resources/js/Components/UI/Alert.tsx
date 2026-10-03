import type { PropsWithChildren } from 'react';
import { statusTone, type StatusTone } from '@/design-system/classNames';

type AlertProps = PropsWithChildren<{
    tone?: StatusTone;
    title?: string;
    className?: string;
}>;

const borderTone: Record<StatusTone, string> = {
    success: 'border-success/30',
    warning: 'border-warning/40',
    error: 'border-danger/30',
    neutral: 'border-line',
    info: 'border-brand/25',
    brand: 'border-brand/25',
};

export default function Alert({ tone = 'info', title, className = '', children }: AlertProps) {
    return (
        <div
            role="status"
            className={`rounded-lg border px-4 py-3 text-sm ${borderTone[tone]} ${statusTone[tone]} ${className}`.trim()}
        >
            {title ? <p className="font-semibold">{title}</p> : null}
            {children ? <div className={title ? 'mt-1 opacity-90' : ''}>{children}</div> : null}
        </div>
    );
}
