import { Link, router, usePage } from '@inertiajs/react';
import type { SharedPageProps } from '@/types/page';

const linkClass = (active: boolean) =>
    `block rounded-md px-3 py-2 text-sm font-medium ${active ? 'bg-brand text-white' : 'text-muted hover:bg-surface hover:text-ink'}`;

export default function AccountNav() {
    const { url, props } = usePage<SharedPageProps>();
    const role = props.auth.user?.role;
    const path = url.split('?')[0] ?? url;
    const candidateItems = props.candidateNav ?? [];

    const items =
        role === 'candidate'
            ? candidateItems.map((item) => ({
                  ...item,
                  match: (value: string) => {
                      if (item.href.includes('#')) {
                          return false;
                      }
                      if (item.href === '/account') {
                          return value === '/account' || value === '/account/';
                      }
                      return value === item.href || value.startsWith(`${item.href}/`);
                  },
              }))
            : [
                  { href: '/account', label: 'Dashboard', match: (value: string) => value === '/account' || value === '/account/' },
                  { href: '/account/profile', label: role === 'employer' ? 'Company Profile' : 'Profile', match: (value: string) => value === '/account/profile' },
                  ...(role === 'employer'
                      ? [
                            { href: '/account/jobs', label: 'Job Management', match: (value: string) => value.startsWith('/account/jobs') },
                            { href: '/account/enquiries', label: 'Enquiries', match: (value: string) => value.startsWith('/account/enquiries') },
                        ]
                      : []),
                  { href: '/account/settings', label: 'Settings', match: (value: string) => value.startsWith('/account/settings') },
              ];

    return (
        <nav className="rounded-xl border border-line bg-white p-2" aria-label="Account">
            {items.map((item) => (
                <Link key={item.href} href={item.href} className={linkClass(item.match(path))}>
                    {item.label}
                </Link>
            ))}
            <button
                type="button"
                className="mt-1 w-full rounded-md px-3 py-2 text-left text-sm font-medium text-muted hover:bg-surface hover:text-ink"
                onClick={() => router.post('/logout')}
            >
                Log out
            </button>
        </nav>
    );
}
