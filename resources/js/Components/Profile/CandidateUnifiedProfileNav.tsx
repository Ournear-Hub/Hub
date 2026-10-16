import { Link } from '@inertiajs/react';

type Section = 'hub' | 'personal' | 'professional' | 'languages' | 'preferences' | 'resumes' | 'privacy';

type Props = {
    active: Section;
    publicProfileUrl?: string | null;
    shareEnabled?: boolean;
    onOpenPersonal?: () => void;
    onOpenProfessional?: () => void;
    onOpenOverview?: () => void;
};

const linkClass = (active: boolean) =>
    `inline-flex min-h-10 shrink-0 items-center rounded-full border px-3 py-1.5 text-sm font-semibold ${
        active ? 'border-brand bg-brand/10 text-brand' : 'border-line bg-white text-ink hover:border-brand/40'
    }`;

export default function CandidateUnifiedProfileNav({
    active,
    publicProfileUrl,
    shareEnabled = false,
    onOpenPersonal,
    onOpenProfessional,
    onOpenOverview,
}: Props) {
    return (
        <nav className="mb-4 overflow-x-auto" aria-label="Profile sections">
            <ul className="flex min-w-0 gap-2 pb-1">
                <li>
                    {onOpenOverview ? (
                        <button type="button" className={linkClass(active === 'hub')} onClick={onOpenOverview}>
                            Overview
                        </button>
                    ) : (
                        <Link href="/account/profile" className={linkClass(active === 'hub')}>
                            Overview
                        </Link>
                    )}
                </li>
                <li>
                    {onOpenPersonal ? (
                        <button type="button" className={linkClass(active === 'personal')} onClick={onOpenPersonal}>
                            Personal
                        </button>
                    ) : (
                        <Link href="/account/profile#personal" className={linkClass(active === 'personal')}>
                            Personal
                        </Link>
                    )}
                </li>
                <li>
                    {onOpenProfessional ? (
                        <button
                            type="button"
                            className={linkClass(active === 'professional')}
                            onClick={onOpenProfessional}
                        >
                            Professional
                        </button>
                    ) : (
                        <Link href="/account/profile#professional" className={linkClass(active === 'professional')}>
                            Professional
                        </Link>
                    )}
                </li>
                <li>
                    <Link href="/account/profile#languages" className={linkClass(active === 'languages')}>
                        Languages
                    </Link>
                </li>
                <li>
                    <Link href="/account/profile#preferences" className={linkClass(active === 'preferences')}>
                        Job preferences
                    </Link>
                </li>
                <li>
                    <Link href="/account/resumes" className={linkClass(active === 'resumes')}>
                        Resumes
                    </Link>
                </li>
                <li>
                    {publicProfileUrl ? (
                        <Link href={publicProfileUrl} className={linkClass(false)}>
                            Preview
                            {!shareEnabled ? ' (private)' : ''}
                        </Link>
                    ) : null}
                </li>
            </ul>
        </nav>
    );
}
