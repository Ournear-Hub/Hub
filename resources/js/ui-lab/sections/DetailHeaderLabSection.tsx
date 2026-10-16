import { Badge, Button, Cluster, StatusBadge } from '@/Components/atoms';
import DetailHeader from '@/Components/organisms/DetailHeader';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { detailHeaderCatalog } from '@/ui-lab/catalog';

export default function DetailHeaderLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Entity detail (level 1)">
                    <DetailHeader
                        title="Acme Corp — Employer account"
                        description="Review profile, jobs, billing, and compliance from this workspace."
                        meta={
                            <Cluster gap="sm" wrap>
                                <StatusBadge tone="success">Active</StatusBadge>
                                <Badge variant="neutral">Enterprise</Badge>
                            </Cluster>
                        }
                        actions={
                            <Button type="button" variant="primary">
                                Edit employer
                            </Button>
                        }
                    />
                </LabPanel>

                <LabPanel title="Nested section (level 2 + eyebrow)">
                    <DetailHeader
                        eyebrow="Applications"
                        headingLevel={2}
                        title="Recent submissions"
                        description="Latest candidate applications linked to this job."
                        actions={
                            <Button type="button" variant="outline">
                                Export CSV
                            </Button>
                        }
                    />
                </LabPanel>

                <LabPanel title="Title only">
                    <DetailHeader title="Security settings" />
                </LabPanel>

                <LabPanel title="Boundary note">
                    <p className="text-sm text-muted">
                        DetailHeader supports the Detail pattern (spec §12). It is not the admin index{' '}
                        <code className="text-ink">h1 + CTA</code> row (PR-5P) and does not replace{' '}
                        <code className="text-ink">Components/UI/SectionHeader.tsx</code> on public pages until
                        an explicit adoption PR.
                    </p>
                </LabPanel>
            </div>

            <InfoPanel entry={detailHeaderCatalog} />
        </div>
    );
}
