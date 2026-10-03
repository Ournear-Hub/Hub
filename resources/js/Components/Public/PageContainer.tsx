import type { PropsWithChildren } from 'react';

type PageWidth = 'default' | 'narrow' | 'wide';

type PageContainerProps = PropsWithChildren<{
    className?: string;
    width?: PageWidth;
}>;

const maxWidth: Record<PageWidth, string> = {
    default: 'max-w-6xl',
    narrow: 'max-w-3xl',
    wide: 'max-w-7xl',
};

export default function PageContainer({ children, className = '', width = 'default' }: PageContainerProps) {
    return (
        <div className={`mx-auto w-full min-w-0 ${maxWidth[width]} px-4 sm:px-6 lg:px-8 ${className}`.trim()}>{children}</div>
    );
}
