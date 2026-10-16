import Button from '@/Components/atoms/Button';
import IconButton from '@/Components/atoms/IconButton';
import Tooltip from '@/Components/molecules/Tooltip';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { tooltipCatalog } from '@/ui-lab/catalog';

export default function TooltipLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Basic text">
                    <Tooltip content="Copy candidate ID to clipboard">
                        <Button type="button" variant="secondary">
                            Hover or focus me
                        </Button>
                    </Tooltip>
                </LabPanel>

                <LabPanel title="IconButton (accessible name on control)">
                    <div className="flex flex-wrap gap-3">
                        <Tooltip content="Edit candidate profile">
                            <IconButton icon="edit" label="Edit candidate profile" variant="ghost" />
                        </Tooltip>
                        <Tooltip content="Delete application">
                            <IconButton icon="delete" label="Delete application" variant="ghost" />
                        </Tooltip>
                        <Tooltip content="View details">
                            <IconButton icon="eye" label="View details" variant="ghost" />
                        </Tooltip>
                    </div>
                    <p className="mt-2 text-xs text-muted">
                        IconButton keeps <code className="text-ink">aria-label</code>; tooltip supplements visually.
                    </p>
                </LabPanel>

                <LabPanel title="Labeled button">
                    <Tooltip content="Opens the employer submission workflow">
                        <Button type="button">Submit to employer</Button>
                    </Tooltip>
                </LabPanel>

                <LabPanel title="Placement">
                    <div className="flex flex-wrap items-center gap-8 py-8">
                        <Tooltip content="Information" side="top">
                            <Button type="button" variant="outline">
                                Top
                            </Button>
                        </Tooltip>
                        <Tooltip content="Copy link" side="right">
                            <Button type="button" variant="outline">
                                Right
                            </Button>
                        </Tooltip>
                        <Tooltip content="View details" side="bottom">
                            <Button type="button" variant="outline">
                                Bottom
                            </Button>
                        </Tooltip>
                        <Tooltip content="Edit" side="left">
                            <Button type="button" variant="outline">
                                Left
                            </Button>
                        </Tooltip>
                    </div>
                </LabPanel>

                <LabPanel title="Keyboard focus">
                    <Tooltip content="Tab to this control — tooltip appears on focus">
                        <Button type="button" variant="secondary">
                            Keyboard accessible
                        </Button>
                    </Tooltip>
                </LabPanel>

                <LabPanel title="Disabled control (Tooltip disabled prop)">
                    <Tooltip content="Complete screening before submitting" disabled>
                        <Button type="button" disabled>
                            Submit (disabled)
                        </Button>
                    </Tooltip>
                    <p className="mt-2 text-xs text-muted">
                        Wrapper receives focus/hover; native button stays disabled.
                    </p>
                </LabPanel>

                <LabPanel title="Concise ReactNode content">
                    <Tooltip
                        content={
                            <span>
                                <strong className="font-semibold">Private</strong> — not shown on public profile
                            </span>
                        }
                    >
                        <IconButton icon="info" label="Salary visibility" variant="ghost" />
                    </Tooltip>
                </LabPanel>
            </div>

            <InfoPanel entry={tooltipCatalog} />
        </div>
    );
}
