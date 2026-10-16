import { Avatar } from '@/Components/atoms';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { avatarCatalog } from '@/ui-lab/catalog';

const samplePhoto =
    'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&w=128&h=128&q=80';

export default function AvatarLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Image avatar">
                    <Avatar src={samplePhoto} alt="Alex Morgan profile photo" size="md" />
                </LabPanel>

                <LabPanel title="Initials avatar">
                    <Avatar name="John Doe" size="md" />
                </LabPanel>

                <LabPanel title="Single-name initials">
                    <Avatar name="John" size="md" />
                </LabPanel>

                <LabPanel title="Neutral fallback">
                    <Avatar size="md" />
                </LabPanel>

                <LabPanel title="Sizes">
                    <div className="flex flex-wrap items-end gap-4">
                        <Avatar name="XS" size="xs" />
                        <Avatar name="SM User" size="sm" />
                        <Avatar name="MD User" size="md" />
                        <Avatar name="LG User" size="lg" />
                        <Avatar name="XL User" size="xl" />
                    </div>
                </LabPanel>

                <LabPanel title="Shape">
                    <div className="flex flex-wrap items-center gap-6">
                        <div className="text-center">
                            <Avatar name="Circle" size="lg" shape="circle" />
                            <p className="mt-2 text-xs text-muted">circle (default)</p>
                        </div>
                        <div className="text-center">
                            <Avatar name="Square" size="lg" shape="square" />
                            <p className="mt-2 text-xs text-muted">square</p>
                        </div>
                    </div>
                </LabPanel>

                <LabPanel title="Image load failure">
                    <Avatar
                        src="https://invalid.example/our-near-avatar-broken.png"
                        name="Fallback JD"
                        alt="Should not remain broken"
                        size="md"
                    />
                    <p className="mt-2 max-w-lg text-xs text-muted">
                        Broken URL falls back to initials when <code className="text-ink">name</code> is provided; otherwise
                        neutral icon.
                    </p>
                </LabPanel>

                <LabPanel title="Accessibility contract">
                    <ul className="max-w-lg list-inside list-disc space-y-1 text-sm text-muted">
                        <li>
                            Meaningful photos: caller supplies <code className="text-ink">alt</code> (see image demo above)
                        </li>
                        <li>
                            Decorative identity in a labeled row: use <code className="text-ink">alt=&quot;&quot;</code> on
                            image; parent exposes the name
                        </li>
                        <li>
                            Initials and neutral fallbacks are <code className="text-ink">aria-hidden</code> — surrounding UI
                            provides context (V2 menu pattern)
                        </li>
                        <li>No automatic aria-label from name; Avatar is not interactive</li>
                    </ul>
                    <div className="mt-4 flex items-center gap-3 rounded-lg border border-line bg-white px-4 py-3">
                        <Avatar src={samplePhoto} alt="" size="sm" />
                        <div>
                            <p className="text-sm font-semibold text-ink">Alex Morgan</p>
                            <p className="text-xs text-muted">Parent text carries accessible name</p>
                        </div>
                    </div>
                </LabPanel>

                <LabPanel title="API / usage">
                    <pre className="overflow-x-auto rounded-lg bg-surface p-4 text-xs text-ink">
                        {`<Avatar
  src?="https://…"
  alt?="Accessible image label"
  name?="Display name for initials"
  size="xs" | "sm" | "md" | "lg" | "xl"
  shape="circle" | "square"
  className?
/>`}
                    </pre>
                </LabPanel>

                <LabPanel title="V2 evidence (read-only)">
                    <ul className="max-w-lg list-inside list-disc space-y-1 text-sm text-muted">
                        <li>
                            <code className="text-ink">Components/Public/Header.tsx</code> — local{' '}
                            <code className="text-ink">ProfileAvatar</code>: photo + rounded-full object-cover, else brand
                            initials
                        </li>
                        <li>No shared V2 Avatar component — ad hoc pattern only</li>
                        <li>V2 unchanged in PR-4N</li>
                    </ul>
                </LabPanel>
            </div>

            <InfoPanel entry={avatarCatalog} />
        </div>
    );
}
