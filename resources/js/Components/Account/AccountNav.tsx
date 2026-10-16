import { Link, router, usePage } from '@inertiajs/react';
import type { HeaderEmployerIdentity } from '@/types/page';
import { useEffect, useMemo, useState } from 'react';
import AccountNavItemIcon from '@/Components/Account/AccountNavItemIcon';
import OurNearIcon from '@/icons/OurNearIcon';
import { matchAccountNavHref } from '@/lib/accountNavMatch';
import { usesRecruiterAccountChrome } from '@/lib/recruiterAccountPaths';
import type { SharedPageProps } from '@/types/page';

function navGroupPanelId(group: string): string {
    return `account-nav-group-${group.toLowerCase().replace(/[^a-z0-9]+/g, '-')}`;
}

const EMPLOYER_NAV_EXPANDED_BY_DEFAULT = new Set(['OVERVIEW', 'HIRING ATS', 'TALENT POOL']);

const linkClass = (active: boolean, employerSidebar: boolean, candidateSidebar: boolean) => {
    if (employerSidebar) {
        return `flex items-center gap-2.5 rounded-md px-3 py-2.5 text-sm font-medium lg:py-2 ${
            active ? 'bg-[#F15A24] text-white' : 'text-white/90 hover:bg-white/10 hover:text-white'
        }`;
    }
    if (candidateSidebar) {
        return `flex items-center gap-2.5 rounded-md border-l-2 py-2.5 pl-[calc(0.75rem-2px)] pr-3 text-sm font-medium lg:py-2 ${
            active
                ? 'border-accent bg-brand/[0.08] text-brand'
                : 'border-transparent text-muted hover:bg-surface hover:text-ink'
        }`;
    }

    return `flex items-center gap-2.5 rounded-md px-3 py-2.5 text-sm font-medium lg:py-2 ${
        active ? 'bg-brand text-white' : 'text-muted hover:bg-surface hover:text-ink'
    }`;
};

type AccountNavProps = {
    onNavigate?: () => void;
    employerSidebar?: boolean;
    /** Personal candidate sidebar (white surface, teal/orange active state). */
    candidateSidebar?: boolean;
    /** Identity rendered in AccountLayout consolidated card. */
    integratedEmployerIdentity?: boolean;
};

export default function AccountNav({
    onNavigate,
    employerSidebar = false,
    candidateSidebar = false,
    integratedEmployerIdentity = false,
}: AccountNavProps) {
    const { url, props } = usePage<SharedPageProps>();
    const role = props.auth.user?.role;
    const pathWithQuery = url.split('#')[0] ?? url;
    const path = pathWithQuery.split('?')[0] ?? pathWithQuery;
    const [locationHash, setLocationHash] = useState('');
    const candidateItems = props.candidateNav ?? [];
    const employerItems = props.employerNav ?? [];

    useEffect(() => {
        const syncHash = () => setLocationHash(window.location.hash);
        syncHash();
        window.addEventListener('hashchange', syncHash);
        window.addEventListener('popstate', syncHash);

        return () => {
            window.removeEventListener('hashchange', syncHash);
            window.removeEventListener('popstate', syncHash);
        };
    }, [url]);

    const isActive = (href: string) => matchAccountNavHref(href, pathWithQuery, locationHash);

    const hasEmployerIdentity = Boolean(props.auth.user?.employer_identity);
    const showEmployerNav =
        employerItems.length > 0 &&
        (role === 'employer' ||
            (role === 'candidate' && usesRecruiterAccountChrome(path, role, hasEmployerIdentity)));

    const items = showEmployerNav
        ? employerItems.map((item) => ({
              ...item,
              group: item.group,
              match: () => isActive(item.href),
          }))
        : role === 'candidate'
          ? candidateItems.map((item) => ({
                ...item,
                group: item.group,
                match: () => isActive(item.href),
            }))
          : [
                { href: '/account', label: 'Dashboard', icon: 'home', match: () => isActive('/account') },
                { href: '/account/profile', label: 'Profile', icon: 'user', match: () => isActive('/account/profile') },
                {
                    href: '/account/settings/account',
                    label: 'Settings',
                    icon: 'settings',
                    match: () => isActive('/account/settings/account'),
                },
            ];

    const employerCollapsibleNav = employerSidebar && showEmployerNav;

    const groupsInOrder = useMemo(() => {
        const order: string[] = [];
        for (const item of items) {
            const group = 'group' in item ? (item.group ?? '') : '';
            if (!order.includes(group)) {
                order.push(group);
            }
        }
        return order;
    }, [items]);

    const activeNavGroup = useMemo(() => {
        for (const item of items) {
            if (item.match()) {
                return 'group' in item ? (item.group ?? '') : '';
            }
        }
        return null;
    }, [items, pathWithQuery, locationHash]);

    const [expandedGroups, setExpandedGroups] = useState<Record<string, boolean>>({});

    useEffect(() => {
        if (activeNavGroup !== null && activeNavGroup !== '') {
            setExpandedGroups((prev) => ({ ...prev, [activeNavGroup]: true }));
        }
    }, [activeNavGroup]);

    const toggleNavGroup = (group: string) => {
        setExpandedGroups((prev) => ({ ...prev, [group]: !prev[group] }));
    };

    const isNavGroupExpanded = (group: string) => {
        if (Object.prototype.hasOwnProperty.call(expandedGroups, group)) {
            return expandedGroups[group];
        }

        return EMPLOYER_NAV_EXPANDED_BY_DEFAULT.has(group);
    };

    const navShellClass =
        employerCollapsibleNav && integratedEmployerIdentity
            ? 'p-2'
            : employerCollapsibleNav
              ? 'rounded-xl border border-[#214449] bg-[#214449] p-2 shadow-sm'
              : candidateSidebar
                ? 'bg-white p-2'
                : 'rounded-xl border border-line bg-white p-2';

    const employerIdentity = props.auth.user?.employer_identity as HeaderEmployerIdentity | null | undefined;
    const companySidebarLabel = employerIdentity?.name?.trim() || 'Company workspace';
    const companySidebarInitial = companySidebarLabel.charAt(0).toUpperCase() || 'C';
    const companyIndustry = employerIdentity?.industry?.trim();
    const user = props.auth.user;
    const showCompanyVerificationPrompt =
        user &&
        (!user.email_verified || ((user.role === 'employer' || user.employer_identity) && !user.mobile_verified));

    const logoutClass =
        employerSidebar && showEmployerNav
            ? 'mt-1 w-full rounded-md px-3 py-2.5 text-left text-sm font-semibold text-white/95 hover:bg-white/10 lg:py-2'
            : 'mt-1 w-full rounded-md px-3 py-2.5 text-left text-sm font-semibold text-danger hover:bg-danger/5 lg:py-2';

    return (
        <nav className={navShellClass} aria-label="Account">
            {employerSidebar && showEmployerNav && employerIdentity && !integratedEmployerIdentity ? (
                <div className="mb-2 border-b border-white/15 px-1 pb-3 pt-1">
                    <Link href={employerIdentity.href} className="block rounded-md px-2 py-1 hover:bg-white/5" onClick={onNavigate}>
                        {employerIdentity.logo_url ? (
                            <img
                                src={employerIdentity.logo_url}
                                alt=""
                                className="h-14 w-14 rounded-lg border border-white/20 bg-white object-contain p-1"
                            />
                        ) : (
                            <div className="flex h-14 w-14 items-center justify-center rounded-lg border border-white/20 bg-white/10 text-base font-semibold text-white">
                                {companySidebarInitial}
                            </div>
                        )}
                        <p className="mt-2 text-sm font-semibold leading-snug text-white">{companySidebarLabel}</p>
                        {companyIndustry ? (
                            <p className="mt-0.5 text-xs leading-snug text-white/70">{companyIndustry}</p>
                        ) : null}
                        {employerIdentity.ournear_verified ? (
                            <p className="mt-2 inline-flex rounded-full bg-white/15 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-white">
                                OurNear verified
                            </p>
                        ) : null}
                    </Link>
                    {showCompanyVerificationPrompt ? (
                        <Link
                            href="/account/settings/verification"
                            className="mt-2 inline-block px-2 text-xs font-semibold text-[#F15A24] hover:underline"
                            onClick={onNavigate}
                        >
                            Complete verification
                        </Link>
                    ) : null}
                </div>
            ) : null}
            {employerCollapsibleNav
                ? groupsInOrder.map((group) => {
                      const groupItems = items.filter((item) => ('group' in item ? (item.group ?? '') : '') === group);
                      const expanded = isNavGroupExpanded(group);
                      const panelId = navGroupPanelId(group || 'misc');

                      return (
                          <div key={group || 'ungrouped'} className="pt-0.5 first:pt-0">
                              {group ? (
                                  <button
                                      type="button"
                                      id={`${panelId}-trigger`}
                                      aria-expanded={expanded}
                                      aria-controls={panelId}
                                      onClick={() => toggleNavGroup(group)}
                                      className="flex w-full items-center justify-between gap-2 rounded-md px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wide text-white/55 hover:bg-white/5 hover:text-white/85 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#F15A24]/50"
                                  >
                                      <span className="min-w-0 truncate">{group}</span>
                                      <OurNearIcon
                                          name={expanded ? 'chevron-down' : 'chevron-right'}
                                          size="sm"
                                          className="shrink-0 text-white/50"
                                      />
                                  </button>
                              ) : null}
                              {expanded || !group ? (
                                  <div id={panelId} role="group" aria-labelledby={group ? `${panelId}-trigger` : undefined}>
                                      {groupItems.map((item) => (
                                          <Link
                                              key={item.href}
                                              href={item.href}
                                              className={linkClass(item.match(), true, false)}
                                              onClick={onNavigate}
                                          >
                                              <AccountNavItemIcon
                                                  icon={'icon' in item ? item.icon : undefined}
                                                  active={item.match()}
                                                  variant="employer-sidebar"
                                              />
                                              <span className="min-w-0 truncate">{item.label}</span>
                                          </Link>
                                      ))}
                                  </div>
                              ) : null}
                          </div>
                      );
                  })
                : items.map((item, index) => {
                      const group = 'group' in item ? item.group : undefined;
                      const previousItem = items[index - 1];
                      const previous =
                          previousItem !== undefined && 'group' in previousItem ? previousItem.group : undefined;
                      const groupLabelClass = candidateSidebar
                          ? 'px-3 pb-1 pt-3 text-[11px] font-semibold uppercase tracking-wide text-muted'
                          : 'px-3 pb-1 pt-3 text-[11px] font-semibold uppercase tracking-wide text-muted';

                      return (
                          <div key={item.href}>
                              {group && group !== previous ? <p className={groupLabelClass}>{group}</p> : null}
                              <Link
                                  href={item.href}
                                  className={linkClass(item.match(), false, candidateSidebar)}
                                  onClick={onNavigate}
                              >
                                  {'logo_url' in item && typeof item.logo_url === 'string' && item.logo_url !== '' ? (
                                      <img
                                          src={item.logo_url}
                                          alt=""
                                          className="h-6 w-6 shrink-0 rounded-md border border-line object-cover"
                                      />
                                  ) : 'nav_kind' in item && item.nav_kind === 'company' ? (
                                      <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-md border border-line bg-brand/10 text-[10px] font-semibold text-brand">
                                          {item.label.charAt(0).toUpperCase()}
                                      </span>
                                  ) : (
                                      <AccountNavItemIcon
                                          icon={'icon' in item ? item.icon : undefined}
                                          active={item.match()}
                                          variant={candidateSidebar ? 'candidate-sidebar' : 'sidebar'}
                                      />
                                  )}
                                  <span className="min-w-0 truncate">{item.label}</span>
                              </Link>
                          </div>
                      );
                  })}
            <button
                type="button"
                className={logoutClass}
                onClick={() => {
                    onNavigate?.();
                    router.post('/logout');
                }}
            >
                Log out
            </button>
        </nav>
    );
}
