import type { PublicSocialLink } from '@/types/media';

function Icon({ platform }: { platform: string }) {
    const className = 'h-4 w-4 fill-current';
    switch (platform) {
        case 'linkedin':
            return (
                <svg viewBox="0 0 24 24" className={className} aria-hidden="true">
                    <path d="M4.98 3.5C4.98 4.88 3.88 6 2.5 6S0 4.88 0 3.5 1.12 1 2.5 1s2.48 1.12 2.48 2.5zM.24 8.98H4.7V24H.24zM8.34 8.98h4.26v2.05h.06c.59-1.12 2.04-2.3 4.2-2.3 4.49 0 5.32 2.95 5.32 6.79V24h-4.46v-6.96c0-1.66-.03-3.8-2.31-3.8-2.31 0-2.67 1.8-2.67 3.67V24H8.34z" />
                </svg>
            );
        case 'facebook':
            return (
                <svg viewBox="0 0 24 24" className={className} aria-hidden="true">
                    <path d="M22 12a10 10 0 1 0-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.77-3.89 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.44 2.89h-2.34v6.99A10 10 0 0 0 22 12" />
                </svg>
            );
        case 'instagram':
            return (
                <svg viewBox="0 0 24 24" className={className} aria-hidden="true">
                    <path d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5zm10 2H7a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3zm-5 3.5A4.5 4.5 0 1 1 7.5 12 4.5 4.5 0 0 1 12 7.5zm0 2A2.5 2.5 0 1 0 14.5 12 2.5 2.5 0 0 0 12 9.5zm5.25-3.25a1 1 0 1 1-1 1 1 1 0 0 1 1-1z" />
                </svg>
            );
        case 'youtube':
            return (
                <svg viewBox="0 0 24 24" className={className} aria-hidden="true">
                    <path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.6 12 3.6 12 3.6s-7.5 0-9.4.5A3 3 0 0 0 .5 6.2 31.6 31.6 0 0 0 0 12a31.6 31.6 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.9.5 9.4.5 9.4.5s7.5 0 9.4-.5a3 3 0 0 0 2.1-2.1A31.6 31.6 0 0 0 24 12a31.6 31.6 0 0 0-.5-5.8zM9.75 15.5v-7l6.5 3.5-6.5 3.5z" />
                </svg>
            );
        case 'whatsapp':
            return (
                <svg viewBox="0 0 24 24" className={className} aria-hidden="true">
                    <path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.4-.7-1.6-.8s-.4-.1-.5.1-.6.8-.7.9-.3.2-.5.1a6.7 6.7 0 0 1-2-1.2 7.3 7.3 0 0 1-1.3-1.7c-.1-.2 0-.4.1-.5l.4-.4.1-.3c0-.1 0-.3 0-.4s-.5-1.2-.7-1.6-.4-.4-.5-.4h-.4c-.1 0-.3.1-.5.3s-.6.6-.6 1.5.7 1.8.8 1.9 1.3 2.1 3.2 2.9a10.7 10.7 0 0 0 1.1.4 2.6 2.6 0 0 0 1.2.1c.4-.1 1.4-.6 1.6-1.1s.2-1 .1-1.1-.2-.2-.4-.3z" />
                </svg>
            );
        case 'telegram':
            return (
                <svg viewBox="0 0 24 24" className={className} aria-hidden="true">
                    <path d="M21.9 4.3 2.8 11.6c-1.3.5-1.3 1.2-.2 1.5l4.9 1.5 11.3-7.1c.5-.3 1-.1.6.2l-9.1 8.3-.4 4.5c.6 0 .8-.2 1.1-.5l2.7-2.6 5.6 4.1c1 .6 1.8.3 2-.9l3.7-17.4c.4-1.6-.6-2.3-1.7-1.9z" />
                </svg>
            );
        default:
            return (
                <svg viewBox="0 0 24 24" className={className} aria-hidden="true">
                    <circle cx="12" cy="12" r="8" />
                </svg>
            );
    }
}

export default function SocialIcons({ links }: { links: PublicSocialLink[] }) {
    if (links.length === 0) {
        return null;
    }

    return (
        <ul className="flex flex-wrap items-center gap-3">
            {links.map((item) => (
                <li key={item.platform}>
                    <a
                        href={item.href}
                        className="inline-flex h-8 w-8 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20"
                        target="_blank"
                        rel="noreferrer"
                        aria-label={item.label}
                    >
                        <Icon platform={item.platform} />
                    </a>
                </li>
            ))}
        </ul>
    );
}
