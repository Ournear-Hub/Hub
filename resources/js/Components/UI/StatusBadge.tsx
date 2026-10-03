import { statusTone, type StatusTone } from '@/design-system/classNames';

type Props = {
    children: string;
    tone?: StatusTone;
    className?: string;
};

export default function StatusBadge({ children, tone = 'neutral', className = '' }: Props) {
    return (
        <span
            className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ${statusTone[tone]} ${className}`.trim()}
        >
            {children}
        </span>
    );
}
