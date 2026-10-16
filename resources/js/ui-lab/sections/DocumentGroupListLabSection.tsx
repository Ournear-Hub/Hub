import Button from '@/Components/atoms/Button';
import Link from '@/Components/atoms/Link';
import DocumentGroupList from '@/Components/organisms/DocumentGroupList';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { documentGroupListCatalog } from '@/ui-lab/catalog';

export default function DocumentGroupListLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Grouped documents (lab data)">
                    <DocumentGroupList
                        empty="No documents uploaded yet."
                        items={[
                            {
                                id: 1,
                                group: 'Certificates',
                                identity: 'DOC-1001',
                                title: 'Forklift safety certificate',
                                meta: 'certificate.pdf · Active · Verified · 2024-01-01 – 2027-01-01',
                                actions: (
                                    <>
                                        <Link href="#" variant="default">
                                            View
                                        </Link>
                                        <Link href="#" variant="default">
                                            Download
                                        </Link>
                                        <Button type="button" variant="ghost" size="sm">
                                            Archive
                                        </Button>
                                    </>
                                ),
                            },
                            {
                                id: 2,
                                group: 'Certificates',
                                title: 'First aid training',
                                meta: 'training.pdf · Active',
                                actions: (
                                    <Link href="#" variant="default">
                                        Download
                                    </Link>
                                ),
                            },
                            {
                                id: 3,
                                group: 'Other documents',
                                title: 'Signed NDA',
                                meta: 'nda.pdf',
                            },
                        ]}
                    />
                </LabPanel>

                <LabPanel title="Empty">
                    <DocumentGroupList items={[]} empty="No company documents uploaded yet." />
                </LabPanel>

                <LabPanel title="File System boundary">
                    <p className="text-sm text-muted">
                        Upload forms, replace-file inputs, Inertia posts, and permission checks live on account document
                        pages — not in this organism. Pass pre-built <code className="text-ink">actions</code> and{' '}
                        <code className="text-ink">extra</code> nodes from the page when needed.
                    </p>
                </LabPanel>
            </div>

            <InfoPanel entry={documentGroupListCatalog} />
        </div>
    );
}
