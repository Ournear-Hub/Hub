import type { ReactNode } from 'react';

type CardProps = {
    children: ReactNode;
    className?: string;
};

export default function Card({ children, className = '' }: CardProps) {
    return <section className={`rounded-xl border border-line bg-white p-5 ${className}`}>{children}</section>;
}
