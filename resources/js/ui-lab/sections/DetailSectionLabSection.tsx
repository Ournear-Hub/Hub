import DetailSection from '@/Components/organisms/DetailSection';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { detailSectionCatalog } from '@/ui-lab/catalog';

export default function DetailSectionLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Profile section (Edit)">
                    <DetailSection title="About" onEdit={() => undefined}>
                        <p className="whitespace-pre-line text-muted">
                            Product manager with eight years in B2B hiring platforms and marketplace operations.
                        </p>
                    </DetailSection>
                </LabPanel>

                <LabPanel title="With description and Add + Edit">
                    <DetailSection
                        title="Work experience"
                        description="Most recent roles appear first."
                        addLabel="Add Experience"
                        onAdd={() => undefined}
                        onEdit={() => undefined}
                    >
                        <p className="text-muted">No work experience added yet.</p>
                    </DetailSection>
                </LabPanel>

                <LabPanel title="Custom actions slot">
                    <DetailSection
                        title="Documents"
                        actions={
                            <a href="#" className="text-sm font-medium text-brand">
                                Open documents
                            </a>
                        }
                    >
                        <p className="text-muted">Private documents stay in your account.</p>
                    </DetailSection>
                </LabPanel>

                <LabPanel title="Feature boundary">
                    <p className="text-sm text-muted">
                        Profile completion, module routing, and save logic stay on{' '}
                        <code className="text-ink">Pages/Account/Profile.tsx</code> and feature helpers — not in this
                        organism.
                    </p>
                </LabPanel>
            </div>

            <InfoPanel entry={detailSectionCatalog} />
        </div>
    );
}
