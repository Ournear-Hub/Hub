import type { PropsWithChildren } from 'react';

type AdminContainerProps = PropsWithChildren<{
    className?: string;
}>;

export default function AdminContainer({ children, className = '' }: AdminContainerProps) {
    return <div className={`mx-auto w-full max-w-6xl ${className}`}>{children}</div>;
}
