import type { ReactNode } from 'react';
import Alert from '@/Components/UI/Alert';

type Props = {
    title?: string;
    message: string;
    action?: ReactNode;
    className?: string;
};

export default function ErrorState({ title = 'Something went wrong', message, action, className = '' }: Props) {
    return (
        <div className={className}>
            <Alert tone="error" title={title}>
                {message}
            </Alert>
            {action ? <div className="mt-3">{action}</div> : null}
        </div>
    );
}
