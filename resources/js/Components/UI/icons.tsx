type IconProps = {
    className?: string;
    title?: string;
};

export function BellIcon({ className = 'h-5 w-5', title = 'Notifications' }: IconProps) {
    return (
        <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden={title ? undefined : true}>
            {title ? <title>{title}</title> : null}
            <path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9" strokeLinecap="round" strokeLinejoin="round" />
            <path d="M13.73 21a2 2 0 01-3.46 0" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}

export function CloseIcon({ className = 'h-5 w-5', title = 'Close' }: IconProps) {
    return (
        <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden={title ? undefined : true}>
            {title ? <title>{title}</title> : null}
            <path d="M18 6L6 18M6 6l12 12" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}

export function MenuIcon({ className = 'h-5 w-5', title = 'Menu' }: IconProps) {
    return (
        <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden={title ? undefined : true}>
            {title ? <title>{title}</title> : null}
            <path d="M4 7h16M4 12h16M4 17h16" strokeLinecap="round" />
        </svg>
    );
}

export function HomeIcon({ className = 'h-5 w-5' }: { className?: string }) {
    return (
        <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
            <path d="M3 10.5L12 3l9 7.5V20a1 1 0 01-1 1h-5v-6H9v6H4a1 1 0 01-1-1v-9.5z" strokeLinejoin="round" />
        </svg>
    );
}

export function BriefcaseIcon({ className = 'h-5 w-5' }: { className?: string }) {
    return (
        <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
            <path d="M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2M4 7h16v12a2 2 0 01-2 2H6a2 2 0 01-2-2V7z" strokeLinejoin="round" />
        </svg>
    );
}

export function ClipboardIcon({ className = 'h-5 w-5' }: { className?: string }) {
    return (
        <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
            <path d="M9 5h6a2 2 0 012 2v14H7V7a2 2 0 012-2z" />
            <path d="M9 3h6v4H9V3z" strokeLinejoin="round" />
        </svg>
    );
}

export function UserCircleIcon({ className = 'h-5 w-5' }: { className?: string }) {
    return (
        <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
            <circle cx="12" cy="8" r="4" />
            <path d="M4 20c0-4 3.6-6 8-6s8 2 8 6" strokeLinecap="round" />
        </svg>
    );
}

export function MoreHorizontalIcon({ className = 'h-5 w-5' }: { className?: string }) {
    return (
        <svg className={className} viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <circle cx="6" cy="12" r="1.5" />
            <circle cx="12" cy="12" r="1.5" />
            <circle cx="18" cy="12" r="1.5" />
        </svg>
    );
}

export function SpinnerIcon({ className = 'h-4 w-4 animate-spin' }: { className?: string }) {
    return (
        <svg className={className} viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
            <path
                className="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            />
        </svg>
    );
}
