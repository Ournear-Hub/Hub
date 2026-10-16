import { Avatar, Image } from '@/Components/atoms';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { imageCatalog } from '@/ui-lab/catalog';

const sampleSrc =
    'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?auto=format&fit=crop&w=800&q=80';

const boxClass = 'h-40 w-full max-w-sm overflow-hidden bg-surface';

export default function ImageLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Basic image">
                    <Image
                        src={sampleSrc}
                        alt="Mountain range above clouds at sunrise"
                        className={`${boxClass} w-full`}
                    />
                </LabPanel>

                <LabPanel title="Fit: cover (default)">
                    <div className={boxClass}>
                        <Image src={sampleSrc} alt="Cover fit demo" className="h-full w-full" fit="cover" />
                    </div>
                </LabPanel>

                <LabPanel title="Fit: contain">
                    <div className={boxClass}>
                        <Image src={sampleSrc} alt="Contain fit demo" className="h-full w-full" fit="contain" />
                    </div>
                </LabPanel>

                <LabPanel title="Fit: fill">
                    <div className={boxClass}>
                        <Image src={sampleSrc} alt="Fill fit demo" className="h-full w-full" fit="fill" />
                    </div>
                </LabPanel>

                <LabPanel title="Fit: none">
                    <div className={`${boxClass} overflow-auto`}>
                        <Image src={sampleSrc} alt="None fit demo" className="h-full w-full" fit="none" />
                    </div>
                </LabPanel>

                <LabPanel title="Loading lazy (default)">
                    <p className="mb-2 text-xs text-muted">
                        Native <code className="text-ink">loading=&quot;lazy&quot;</code> unless overridden.
                    </p>
                    <Image src={sampleSrc} alt="Lazy-loaded landscape" className="max-h-32 w-auto" loading="lazy" />
                </LabPanel>

                <LabPanel title="Loading eager">
                    <Image
                        src={sampleSrc}
                        alt="Eager-loaded hero preview"
                        className="max-h-32 w-auto"
                        loading="eager"
                    />
                </LabPanel>

                <LabPanel title="Meaningful alt">
                    <Image
                        src={sampleSrc}
                        alt="Snow-capped peaks viewed from above the cloud layer"
                        className="max-h-32 w-full max-w-sm rounded-md"
                    />
                </LabPanel>

                <LabPanel title="Decorative alt=&quot;&quot;">
                    <Image src={sampleSrc} alt="" className="max-h-24 w-full max-w-sm opacity-80" />
                    <p className="mt-2 text-xs text-muted">Empty alt — decorative; pair with visible caption or context.</p>
                </LabPanel>

                <LabPanel title="Width / height attributes">
                    <Image
                        src={sampleSrc}
                        alt="Fixed intrinsic dimensions"
                        width={160}
                        height={90}
                        className="rounded-md border border-line"
                    />
                </LabPanel>

                <LabPanel title="className composition">
                    <Image
                        src={sampleSrc}
                        alt="Card thumbnail with caller styling"
                        className="h-24 w-24 rounded-md border border-line shadow-sm"
                    />
                </LabPanel>

                <LabPanel title="Image vs Avatar">
                    <div className="flex flex-wrap items-center gap-6">
                        <div>
                            <p className="mb-2 text-xs font-medium text-muted">Image (generic media)</p>
                            <Image
                                src={sampleSrc}
                                alt="Team offsite photo"
                                className="h-16 w-24 rounded-md object-cover"
                            />
                        </div>
                        <div>
                            <p className="mb-2 text-xs font-medium text-muted">Avatar (identity)</p>
                            <Avatar name="Alex Morgan" size="lg" />
                        </div>
                    </div>
                    <p className="mt-3 max-w-lg text-xs text-muted">
                        Image does not provide initials, identity fallback, or shape presets. Avatar unchanged (PR-4N).
                    </p>
                </LabPanel>

                <LabPanel title="Native semantics">
                    <ul className="max-w-lg list-inside list-disc space-y-1 text-sm text-muted">
                        <li>Renders native &lt;img&gt; with ref forwarding</li>
                        <li>Standard attributes (id, data-*, aria-*, style, handlers) pass through</li>
                        <li>No custom fallback or error UI on load failure</li>
                    </ul>
                </LabPanel>

                <LabPanel title="API notes">
                    <pre className="overflow-x-auto rounded-lg bg-surface p-4 text-xs text-ink">
                        {`<Image
  src={required}
  alt={required /* "" for decorative */}
  width?
  height?
  fit="cover" | "contain" | "fill" | "none"
  loading="lazy" | "eager"
  className?
/>`}
                    </pre>
                </LabPanel>

                <LabPanel title="Non-goals">
                    <p className="max-w-lg text-sm text-muted">
                        No srcset/sizes, upload/storage, placeholders, captions, links, or Avatar-style fallbacks. Radius,
                        borders, and layout dimensions belong to callers (V2 pattern).
                    </p>
                </LabPanel>

                <LabPanel title="V2 evidence (read-only)">
                    <ul className="max-w-lg list-inside list-disc space-y-1 text-sm text-muted">
                        <li>
                            Raw <code className="text-ink">&lt;img&gt;</code> in JobCard, Header profile photo, etc.
                        </li>
                        <li>
                            Common caller classes: <code className="text-ink">object-cover</code>,{' '}
                            <code className="text-ink">rounded-md</code>, explicit h/w
                        </li>
                        <li>No generic V2 Image component</li>
                    </ul>
                </LabPanel>
            </div>

            <InfoPanel entry={imageCatalog} />
        </div>
    );
}
