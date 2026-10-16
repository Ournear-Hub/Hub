import type { PropsWithChildren } from 'react';
import { accountLayout } from '@/design-system/classNames';

type Props = PropsWithChildren<{
    className?: string;
}>;

/** Standard vertical rhythm for personal account main content. */
export default function AccountPageStack({ children, className = '' }: Props) {
    return <div className={`${accountLayout.pageStack} ${className}`.trim()}>{children}</div>;
}
