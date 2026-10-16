import type { MediaOption } from '@/types/media';

const inputClass =
    'w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-brand';

const OBJECT_POSITIONS = [
    { value: 'center center', label: 'Center' },
    { value: 'top center', label: 'Top center' },
    { value: 'bottom center', label: 'Bottom center' },
    { value: 'center left', label: 'Center left' },
    { value: 'center right', label: 'Center right' },
    { value: 'top left', label: 'Top left' },
    { value: 'top right', label: 'Top right' },
] as const;

type Props = {
    slot: 'hero' | 'workforce_partner';
    mediaId: number | null;
    mobileMediaId: number | null;
    imageEnabled: boolean;
    mediaOptions: MediaOption[];
    imageAlt: string;
    objectPosition: string;
    mobileObjectPosition: string;
    onMediaChange: (mediaId: number | null) => void;
    onMobileMediaChange: (mediaId: number | null) => void;
    onEnabledChange: (enabled: boolean) => void;
    onAltChange: (value: string) => void;
    onObjectPositionChange: (value: string) => void;
    onMobileObjectPositionChange: (value: string) => void;
};

const SLOT_COPY = {
    hero: {
        title: 'Staffing Hero Illustration',
        description:
            'Workforce illustration in the shaded frame on the right of the hero. Uses object-contain with the focal position below.',
    },
    workforce_partner: {
        title: 'Staffing Support Illustration',
        description:
            'Large illustration on the left of the workforce partner section. Leave disabled to show heading and benefit cards only (no empty frame).',
    },
} as const;

export default function StaffingIllustrationFields({
    slot,
    mediaId,
    mobileMediaId,
    imageEnabled,
    mediaOptions,
    imageAlt,
    objectPosition,
    mobileObjectPosition,
    onMediaChange,
    onMobileMediaChange,
    onEnabledChange,
    onAltChange,
    onObjectPositionChange,
    onMobileObjectPositionChange,
}: Props) {
    const copy = SLOT_COPY[slot];
    const selectedDesktop = mediaOptions.find((option) => option.id === mediaId);
    const selectedMobile = mediaOptions.find((option) => option.id === mobileMediaId);

    const removeDesktop = () => {
        if (!mediaId) {
            return;
        }
        if (!window.confirm(`Remove the desktop ${copy.title}?`)) {
            return;
        }
        onMediaChange(null);
    };

    return (
        <div className="rounded-xl border border-brand/25 bg-brand/5 p-4">
            <h3 className="text-sm font-semibold text-ink">{copy.title}</h3>
            <p className="mt-1 text-sm text-muted">{copy.description}</p>
            <p className="mt-2 text-xs text-muted">
                Upload files in{' '}
                <a href="/admin/media-social?tab=media" className="font-semibold text-brand hover:text-accent">
                    Media &amp; Social
                </a>
                , then assign them here. Desktop and mobile variants are independent.
            </p>

            <label className="mt-4 flex items-center gap-2 text-sm font-medium text-ink">
                <input
                    type="checkbox"
                    checked={imageEnabled}
                    onChange={(event) => onEnabledChange(event.target.checked)}
                />
                {slot === 'hero' ? 'Show hero image on public page' : 'Show support illustration on public page'}
            </label>

            <div className="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <label className="mb-1 block text-sm font-medium text-ink">Desktop image</label>
                    <select
                        className={inputClass}
                        value={mediaId ?? ''}
                        onChange={(event) =>
                            onMediaChange(event.target.value === '' ? null : Number(event.target.value))
                        }
                        disabled={!imageEnabled}
                    >
                        <option value="">
                            {slot === 'hero' ? 'Use bundled default hero (when enabled)' : 'No image'}
                        </option>
                        {mediaOptions.map((option) => (
                            <option key={option.id} value={option.id}>
                                {option.original_name}
                            </option>
                        ))}
                    </select>
                </div>
                <div>
                    <label className="mb-1 block text-sm font-medium text-ink">Mobile image (optional)</label>
                    <select
                        className={inputClass}
                        value={mobileMediaId ?? ''}
                        onChange={(event) =>
                            onMobileMediaChange(event.target.value === '' ? null : Number(event.target.value))
                        }
                        disabled={!imageEnabled}
                    >
                        <option value="">Same as desktop</option>
                        {mediaOptions.map((option) => (
                            <option key={option.id} value={option.id}>
                                {option.original_name}
                            </option>
                        ))}
                    </select>
                </div>
            </div>

            <div className="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <label className="mb-1 block text-sm font-medium text-ink">Desktop focal position</label>
                    <select
                        className={inputClass}
                        value={objectPosition}
                        onChange={(event) => onObjectPositionChange(event.target.value)}
                        disabled={!imageEnabled}
                    >
                        {OBJECT_POSITIONS.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                </div>
                <div>
                    <label className="mb-1 block text-sm font-medium text-ink">Mobile focal position</label>
                    <select
                        className={inputClass}
                        value={mobileObjectPosition}
                        onChange={(event) => onMobileObjectPositionChange(event.target.value)}
                        disabled={!imageEnabled}
                    >
                        {OBJECT_POSITIONS.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                </div>
            </div>

            <div className="mt-4">
                <label className="mb-1 block text-sm font-medium text-ink">Alternative text</label>
                <input
                    type="text"
                    className={inputClass}
                    value={imageAlt}
                    placeholder="Describe the photograph for accessibility"
                    onChange={(event) => onAltChange(event.target.value)}
                    disabled={!imageEnabled}
                />
            </div>

            <div className="mt-4 flex flex-wrap items-start gap-4">
                {selectedDesktop?.url ? (
                    <img
                        src={selectedDesktop.url}
                        alt=""
                        className="max-h-36 max-w-[12rem] rounded-md border border-line bg-white object-cover"
                    />
                ) : (
                    <p className="text-sm text-muted">No desktop image selected.</p>
                )}
                {selectedMobile?.url && selectedMobile.id !== mediaId ? (
                    <img
                        src={selectedMobile.url}
                        alt=""
                        className="max-h-36 max-w-[12rem] rounded-md border border-line bg-white object-cover md:hidden"
                    />
                ) : null}
                {mediaId ? (
                    <button
                        type="button"
                        className="rounded-md border border-danger/30 bg-white px-3 py-2 text-sm font-semibold text-danger hover:bg-danger/5"
                        onClick={removeDesktop}
                    >
                        Remove desktop image
                    </button>
                ) : null}
            </div>
        </div>
    );
}
