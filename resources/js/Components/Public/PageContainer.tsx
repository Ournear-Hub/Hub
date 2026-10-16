import type { PropsWithChildren } from 'react';

type PageWidth = 'default' | 'narrow' | 'wide' | 'marketing';

type PageGutter = 'default' | 'account';

type PageContainerProps = PropsWithChildren<{
    className?: string;
    width?: PageWidth;
    /** Account shell: 16–20px mobile gutters, up to ~32px desktop */
    gutter?: PageGutter;
}>;

/** Public content max widths — default targets ~1440px with responsive gutters. */
const maxWidth: Record<PageWidth, string> = {
    default: 'max-w-[90rem]',
    marketing: 'max-w-[78rem]',
    narrow: 'max-w-3xl',
    wide: 'max-w-[96rem]',
};

const gutterClass: Record<PageGutter, string> = {
    default: 'px-4 sm:px-5 md:px-7 lg:px-10',
    account: 'px-4 sm:px-5 lg:px-6 xl:px-8',
};

export default function PageContainer({
    children,
    className = '',
    width = 'default',
    gutter = 'default',
}: PageContainerProps) {
    return (
        <div className={`mx-auto w-full min-w-0 ${maxWidth[width]} ${gutterClass[gutter]} ${className}`.trim()}>
            {children}
        </div>
    );
}
