import { IconButton } from '@/Components/atoms';
import type { IconButtonVariant } from '@/Components/atoms/IconButton';
import LabPanel from '@/ui-lab/components/LabPanel';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import { iconButtonCatalog } from '@/ui-lab/catalog';

const variants: IconButtonVariant[] = ['default', 'primary', 'secondary', 'ghost', 'destructive', 'outline'];

export default function IconButtonLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Public surface" description="Primary icon button uses accent on public surface.">
                    <div className="flex flex-wrap gap-2">
                        {variants.map((variant) => (
                            <IconButton key={variant} surface="public" variant={variant} icon="search" label={`${variant} search`} />
                        ))}
                    </div>
                </LabPanel>

                <LabPanel title="Admin surface" description="Primary uses brand teal on admin/corporate surfaces.">
                    <div className="flex flex-wrap gap-2">
                        {variants.map((variant) => (
                            <IconButton key={variant} surface="admin" variant={variant} icon="settings" label={`${variant} settings`} />
                        ))}
                    </div>
                </LabPanel>

                <LabPanel title="Sizes">
                    <div className="flex flex-wrap items-center gap-2">
                        <IconButton surface="public" size="sm" icon="bell" label="Notifications small" />
                        <IconButton surface="public" size="md" icon="bell" label="Notifications medium" />
                        <IconButton surface="public" size="lg" icon="bell" label="Notifications large" />
                    </div>
                </LabPanel>

                <LabPanel title="States">
                    <div className="flex flex-wrap gap-2">
                        <IconButton surface="public" icon="edit" label="Edit" />
                        <IconButton surface="public" icon="edit" label="Edit disabled" disabled />
                        <IconButton surface="public" icon="edit" label="Edit loading" loading />
                    </div>
                </LabPanel>
            </div>

            <InfoPanel entry={iconButtonCatalog} />
        </div>
    );
}
