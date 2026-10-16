import { router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import {
    Button,
    Drawer,
    Input,
    SearchBar,
    Select,
    StatusBadge,
    Tabs,
    Textarea,
} from '@/Components/Admin/ui';
import FilterBar from '@/Components/molecules/FilterBar';
import DataTable from '@/Components/molecules/DataTable';
import type { DataTableColumn } from '@/Components/molecules/DataTable';
import type { LabeledOption } from '@/types/applications';
import type { Paginator } from '@/types/jobs';

type Campaign = {
    id: number;
    business_id: string | null;
    name: string;
    summary?: string | null;
    notes?: string | null;
    starts_at?: string | null;
    ends_at?: string | null;
    status: string;
    status_label: string;
    link_count: number;
    share_count: number;
    referral_count: number;
};

type LinkRow = {
    id: number;
    code: string;
    path: string;
    public_url: string;
    status: string;
    status_label: string;
    campaign?: { name: string } | null;
    job?: { title: string } | null;
    share_count: number;
    referral_count: number;
};

type ShareRow = {
    id: number;
    channel_label: string;
    path: string;
    job?: { title: string } | null;
    code?: string | null;
    created_at: string | null;
};

type ReferralRow = {
    id: number;
    conversion_label: string;
    code?: string | null;
    campaign_name?: string | null;
    referred_name?: string | null;
    registration_source?: string | null;
    job_title?: string | null;
};

type ActivityItem = { id: number; body: string | null; type: string };
type WorkItem = { id: number; title: string; status_label: string };
type SelectOption = { id: number; label: string };

type Props = {
    campaigns: Paginator<Campaign>;
    links: Paginator<LinkRow>;
    shares: Paginator<ShareRow>;
    referrals: Paginator<ReferralRow>;
    selected: Campaign | null;
    activity: ActivityItem[];
    assigned_work: WorkItem[];
    filters: {
        search: string;
        status: string;
        tab: string;
        campaign: string;
        link_status: string;
        channel: string;
        conversion_type: string;
    };
    statuses: LabeledOption[];
    link_statuses: LabeledOption[];
    channels: LabeledOption[];
    conversion_types: LabeledOption[];
    campaign_options: SelectOption[];
    job_options: SelectOption[];
    can_manage: boolean;
};

function toDateTimeLocal(value?: string | null): string {
    if (!value) {
        return '';
    }

    return value.replace(' ', 'T').slice(0, 16);
}

function CampaignEditForm({ campaign }: { campaign: Campaign }) {
    const form = useForm({
        name: campaign.name,
        summary: campaign.summary ?? '',
        notes: campaign.notes ?? '',
        starts_at: toDateTimeLocal(campaign.starts_at),
        ends_at: toDateTimeLocal(campaign.ends_at),
    });

    return (
        <form
            className="grid gap-2"
            onSubmit={(event) => {
                event.preventDefault();
                form.transform((data) => ({
                    ...data,
                    summary: data.summary || null,
                    notes: data.notes || null,
                    starts_at: data.starts_at || null,
                    ends_at: data.ends_at || null,
                }));
                form.put(`/admin/campaigns/${campaign.id}`, { preserveScroll: true });
            }}
        >
            <Input
                aria-label="Campaign name"
                value={form.data.name}
                onChange={(event) => form.setData('name', event.target.value)}
                required
            />
            <Input
                aria-label="Summary"
                placeholder="Summary"
                value={form.data.summary}
                onChange={(event) => form.setData('summary', event.target.value)}
            />
            <Textarea
                aria-label="Notes"
                placeholder="Notes"
                value={form.data.notes}
                onChange={(event) => form.setData('notes', event.target.value)}
            />
            <Input
                type="datetime-local"
                aria-label="Starts at"
                value={form.data.starts_at}
                onChange={(event) => form.setData('starts_at', event.target.value)}
            />
            <Input
                type="datetime-local"
                aria-label="Ends at"
                value={form.data.ends_at}
                onChange={(event) => form.setData('ends_at', event.target.value)}
            />
            <div>
                <Button type="submit" disabled={form.processing}>
                    Save campaign
                </Button>
            </div>
        </form>
    );
}

export default function CampaignsIndex({
    campaigns,
    links,
    shares,
    referrals,
    selected,
    activity,
    assigned_work,
    filters,
    statuses,
    link_statuses,
    channels,
    conversion_types,
    campaign_options,
    job_options,
    can_manage,
}: Props) {
    const tab = filters.tab || 'campaigns';
    const campaignForm = useForm({
        name: '',
        summary: '',
        notes: '',
        starts_at: '',
        ends_at: '',
    });
    const linkForm = useForm({
        campaign_id: filters.campaign || '',
        job_id: '',
        path: '/jobs',
        notes: '',
    });

    const visit = (overrides: Record<string, string | undefined> = {}) => {
        const next = { ...filters, ...overrides };
        const params: Record<string, string> = {};
        Object.entries(next).forEach(([key, value]) => {
            if (value) {
                params[key] = value;
            }
        });
        router.get('/admin/campaigns', params, { preserveState: true, preserveScroll: true });
    };

    const filter = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        visit(Object.fromEntries(form.entries()) as Record<string, string>);
    };

    const campaignColumns: DataTableColumn<Campaign>[] = [
        { key: 'id', header: 'ID', render: (row) => row.business_id ?? row.id },
        { key: 'name', header: 'Campaign', render: (row) => row.name },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status_label} /> },
        { key: 'counts', header: 'Links / Shares / Conv.', render: (row) => `${row.link_count} / ${row.share_count} / ${row.referral_count}` },
    ];

    const linkColumns: DataTableColumn<LinkRow>[] = [
        { key: 'code', header: 'Code', render: (row) => row.code },
        { key: 'path', header: 'Path', render: (row) => row.path },
        { key: 'campaign', header: 'Campaign', render: (row) => row.campaign?.name ?? '—' },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status_label} /> },
        { key: 'counts', header: 'Shares / Conv.', render: (row) => `${row.share_count} / ${row.referral_count}` },
    ];

    const shareColumns: DataTableColumn<ShareRow>[] = [
        { key: 'channel', header: 'Channel', render: (row) => row.channel_label },
        { key: 'job', header: 'Job', render: (row) => row.job?.title ?? '—' },
        { key: 'code', header: 'Ref', render: (row) => row.code ?? '—' },
        { key: 'when', header: 'When', render: (row) => row.created_at ?? '—' },
    ];

    const referralColumns: DataTableColumn<ReferralRow>[] = [
        { key: 'code', header: 'Code', render: (row) => row.code ?? '—' },
        { key: 'type', header: 'Conversion', render: (row) => row.conversion_label },
        { key: 'person', header: 'Attributed to', render: (row) => row.referred_name ?? '—' },
        { key: 'source', header: 'User source', render: (row) => row.registration_source ?? '—' },
        { key: 'job', header: 'Job', render: (row) => row.job_title ?? '—' },
    ];

    return (
        <AdminLayout title="Campaigns">
            <div className="space-y-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight text-ink">Campaigns</h1>
                    <p className="mt-1 text-sm text-muted">
                        Public referral links and share tracking. No WhatsApp sending, coins, or partner attribution.
                    </p>
                </div>

                <Tabs
                    tabs={[
                        { id: 'campaigns', label: 'Campaigns' },
                        { id: 'links', label: 'Referral links' },
                        { id: 'shares', label: 'Share events' },
                        { id: 'conversions', label: 'Conversions' },
                    ]}
                    active={tab}
                    onChange={(next) => visit({ tab: next })}
                />

                <FilterBar onSubmit={filter}>
                    <input type="hidden" name="tab" value={tab} />
                    {tab === 'campaigns' ? (
                        <>
                            <SearchBar name="search" defaultValue={filters.search} placeholder="Search campaigns" />
                            <Select name="status" defaultValue={filters.status} aria-label="Campaign status">
                                <option value="">All statuses</option>
                                {statuses.map((status) => (
                                    <option key={status.value} value={status.value}>
                                        {status.label}
                                    </option>
                                ))}
                            </Select>
                        </>
                    ) : (
                        <Select name="campaign" defaultValue={filters.campaign} aria-label="Campaign">
                            <option value="">All campaigns</option>
                            {campaign_options.map((campaign) => (
                                <option key={campaign.id} value={campaign.id}>
                                    {campaign.label}
                                </option>
                            ))}
                        </Select>
                    )}
                    {tab === 'links' ? (
                        <Select name="link_status" defaultValue={filters.link_status} aria-label="Link status">
                            <option value="">All links</option>
                            {link_statuses.map((status) => (
                                <option key={status.value} value={status.value}>
                                    {status.label}
                                </option>
                            ))}
                        </Select>
                    ) : null}
                    {tab === 'shares' ? (
                        <Select name="channel" defaultValue={filters.channel} aria-label="Share channel">
                            <option value="">All channels</option>
                            {channels.map((channel) => (
                                <option key={channel.value} value={channel.value}>
                                    {channel.label}
                                </option>
                            ))}
                        </Select>
                    ) : null}
                    {tab === 'conversions' ? (
                        <Select name="conversion_type" defaultValue={filters.conversion_type} aria-label="Conversion type">
                            <option value="">All conversions</option>
                            {conversion_types.map((type) => (
                                <option key={type.value} value={type.value}>
                                    {type.label}
                                </option>
                            ))}
                        </Select>
                    ) : null}
                    <Button type="submit">Filter</Button>
                </FilterBar>

                {can_manage && tab === 'campaigns' ? (
                    <form
                        className="grid gap-2 rounded-xl border border-line bg-white p-4 md:grid-cols-2"
                        onSubmit={(event) => {
                            event.preventDefault();
                            campaignForm.transform((data) => ({
                                ...data,
                                summary: data.summary || null,
                                notes: data.notes || null,
                                starts_at: data.starts_at || null,
                                ends_at: data.ends_at || null,
                            }));
                            campaignForm.post('/admin/campaigns', { onSuccess: () => campaignForm.reset() });
                        }}
                    >
                        <Input
                            placeholder="Campaign name"
                            value={campaignForm.data.name}
                            onChange={(event) => campaignForm.setData('name', event.target.value)}
                            required
                        />
                        <Input
                            placeholder="Summary"
                            value={campaignForm.data.summary}
                            onChange={(event) => campaignForm.setData('summary', event.target.value)}
                        />
                        <Input
                            type="datetime-local"
                            aria-label="Starts at"
                            value={campaignForm.data.starts_at}
                            onChange={(event) => campaignForm.setData('starts_at', event.target.value)}
                        />
                        <Input
                            type="datetime-local"
                            aria-label="Ends at"
                            value={campaignForm.data.ends_at}
                            onChange={(event) => campaignForm.setData('ends_at', event.target.value)}
                        />
                        <Textarea
                            className="md:col-span-2"
                            placeholder="Notes"
                            value={campaignForm.data.notes}
                            onChange={(event) => campaignForm.setData('notes', event.target.value)}
                        />
                        <div>
                            <Button type="submit" disabled={campaignForm.processing}>
                                Create campaign
                            </Button>
                        </div>
                    </form>
                ) : null}

                {can_manage && tab === 'links' ? (
                    <form
                        className="grid gap-2 rounded-xl border border-line bg-white p-4 md:grid-cols-2"
                        onSubmit={(event) => {
                            event.preventDefault();
                            linkForm.transform((data) => ({
                                ...data,
                                campaign_id: data.campaign_id || null,
                                job_id: data.job_id || null,
                            }));
                            linkForm.post('/admin/campaigns/links', { onSuccess: () => linkForm.reset() });
                        }}
                    >
                        <Select
                            value={linkForm.data.campaign_id}
                            onChange={(event) => linkForm.setData('campaign_id', event.target.value)}
                            aria-label="Campaign"
                        >
                            <option value="">No campaign</option>
                            {campaign_options.map((campaign) => (
                                <option key={campaign.id} value={campaign.id}>
                                    {campaign.label}
                                </option>
                            ))}
                        </Select>
                        <Select
                            value={linkForm.data.job_id}
                            onChange={(event) => linkForm.setData('job_id', event.target.value)}
                            aria-label="Job"
                        >
                            <option value="">No job</option>
                            {job_options.map((job) => (
                                <option key={job.id} value={job.id}>
                                    {job.label}
                                </option>
                            ))}
                        </Select>
                        <Input
                            placeholder="Public path, e.g. /jobs"
                            value={linkForm.data.path}
                            onChange={(event) => linkForm.setData('path', event.target.value)}
                        />
                        <div>
                            <Button type="submit" disabled={linkForm.processing}>
                                Create referral link
                            </Button>
                        </div>
                    </form>
                ) : null}

                {tab === 'campaigns' ? (
                    <>
                        <DataTable
                            columns={campaignColumns}
                            rows={campaigns.data}
                            rowKey={(row) => row.id}
                            emptyTitle="No campaigns yet."
                            emptyDescription="Create a campaign, then add referral links."
                            selectedKey={selected?.id ?? null}
                            onRowClick={(row) => visit({ tab: 'campaigns', campaign: String(row.id) })}
                        />
                        <Pagination links={campaigns.links} />
                    </>
                ) : null}

                {tab === 'links' ? (
                    <>
                        <DataTable
                            columns={linkColumns}
                            rows={links.data}
                            rowKey={(row) => row.id}
                            emptyTitle="No referral links yet."
                        />
                        <Pagination links={links.links} />
                    </>
                ) : null}

                {tab === 'shares' ? (
                    <>
                        <DataTable columns={shareColumns} rows={shares.data} rowKey={(row) => row.id} emptyTitle="No share events yet." />
                        <Pagination links={shares.links} />
                    </>
                ) : null}

                {tab === 'conversions' ? (
                    <>
                        <DataTable
                            columns={referralColumns}
                            rows={referrals.data}
                            rowKey={(row) => row.id}
                            emptyTitle="No referral conversions yet."
                        />
                        <Pagination links={referrals.links} />
                    </>
                ) : null}
            </div>

            <Drawer open={selected !== null} title={selected?.name ?? 'Campaign'} onClose={() => visit({ campaign: '' })}>
                {selected ? (
                    <div className="space-y-4">
                        <StatusBadge status={selected.status_label} />
                        {can_manage ? (
                            <>
                                <div className="flex flex-wrap gap-2">
                                    {statuses.map((status) => (
                                        <Button
                                            key={status.value}
                                            type="button"
                                            onClick={() => router.post(`/admin/campaigns/${selected.id}/status`, { status: status.value })}
                                        >
                                            {status.label}
                                        </Button>
                                    ))}
                                </div>
                                <CampaignEditForm key={selected.id} campaign={selected} />
                            </>
                        ) : null}
                        <div>
                            <h3 className="text-sm font-semibold text-ink">Assigned work</h3>
                            {assigned_work.length === 0 ? (
                                <p className="mt-1 text-sm text-muted">No assigned work yet.</p>
                            ) : (
                                <ul className="mt-2 space-y-2">
                                    {assigned_work.map((task) => (
                                        <li key={task.id} className="text-sm text-ink">
                                            {task.title} · {task.status_label}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>
                        <div>
                            <h3 className="text-sm font-semibold text-ink">Activity</h3>
                            {activity.length === 0 ? (
                                <p className="mt-1 text-sm text-muted">No activity yet.</p>
                            ) : (
                                <ul className="mt-2 space-y-2">
                                    {activity.map((item) => (
                                        <li key={item.id} className="text-sm text-muted">
                                            {item.body ?? item.type}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>
                    </div>
                ) : null}
            </Drawer>

            {tab === 'links' && can_manage ? (
                <div className="mt-2 space-y-2">
                    {links.data
                        .filter((link) => link.status === 'active')
                        .map((link) => (
                            <div key={link.id} className="flex items-center justify-between rounded-md border border-line bg-white px-3 py-2 text-sm">
                                <span>
                                    {link.code} · {link.public_url}
                                </span>
                                <Button type="button" onClick={() => router.post(`/admin/campaigns/links/${link.id}/disable`)}>
                                    Disable
                                </Button>
                            </div>
                        ))}
                </div>
            ) : null}
        </AdminLayout>
    );
}
