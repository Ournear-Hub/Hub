import { SpinnerIcon } from '@/Components/UI/icons';
import { typography } from '@/design-system/classNames';

type Props = {
    label?: string;
    className?: string;
};

export default function LoadingState({ label = 'Loading…', className = '' }: Props) {
    return (
        <div className={`flex items-center justify-center gap-2 py-8 ${className}`.trim()} role="status" aria-live="polite">
            <SpinnerIcon />
            <span className={typography.secondary}>{label}</span>
        </div>
    );
}
