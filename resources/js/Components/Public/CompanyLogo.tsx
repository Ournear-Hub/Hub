import { useEffect, useState } from 'react';

type Props = {
    name: string;
    src?: string | null;
    sizeClass: string;
    shape?: 'rounded-lg' | 'rounded-md' | 'rounded-full';
    className?: string;
};

function initials(name: string): string {
    return (
        name
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 2)
            .map((part) => part[0])
            .join('')
            .toUpperCase() || 'CO'
    );
}

export default function CompanyLogo({ name, src, sizeClass, shape = 'rounded-lg', className = '' }: Props) {
    const [failed, setFailed] = useState(false);
    const trimmed = src?.trim();

    useEffect(() => {
        setFailed(false);
    }, [trimmed]);

    if (trimmed && !failed) {
        return (
            <img
                src={trimmed}
                alt=""
                className={`${sizeClass} shrink-0 border border-line object-contain ${shape} ${className}`.trim()}
                onError={() => setFailed(true)}
            />
        );
    }

    return (
        <div
            aria-hidden
            className={`flex ${sizeClass} shrink-0 items-center justify-center bg-brand text-xs font-semibold text-white ${shape} ${className}`.trim()}
        >
            {initials(name)}
        </div>
    );
}
