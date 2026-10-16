import Button from '@/Components/atoms/Button';
import EmptyState from '@/Components/molecules/EmptyState';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { emptyStateCatalog } from '@/ui-lab/catalog';

export default function EmptyStateLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="No search results">
                    <EmptyState
                        icon="search"
                        title="No jobs found"
                        description="Try adjusting filters or search terms."
                    />
                </LabPanel>

                <LabPanel title="No records yet">
                    <EmptyState
                        icon="clipboard"
                        title="No applications yet"
                        description="When candidates apply, applications appear here."
                        action={
                            <Button type="button" variant="primary">
                                Browse jobs
                            </Button>
                        }
                    />
                </LabPanel>

                <LabPanel title="Primary + secondary action">
                    <EmptyState
                        icon="user"
                        title="No candidates in this pool"
                        description="Import or invite candidates to get started."
                        action={
                            <Button type="button" variant="primary">
                                Add candidate
                            </Button>
                        }
                        secondaryAction={
                            <Button type="button" variant="outline">
                                Import CSV
                            </Button>
                        }
                    />
                </LabPanel>

                <LabPanel title="Align start (inline section)">
                    <EmptyState
                        align="start"
                        size="sm"
                        icon="bell"
                        title="No notifications"
                        description="You are all caught up."
                    />
                </LabPanel>

                <LabPanel title="Sizes">
                    <div className="space-y-4">
                        <EmptyState size="sm" title="Small empty section" description="Compact padding." />
                        <EmptyState size="lg" title="Large empty panel" description="Feature-style spacing." />
                    </div>
                </LabPanel>

                <LabPanel title="Long description">
                    <EmptyState
                        icon="briefcase"
                        title="No saved jobs"
                        description="Jobs you save from the board appear here so you can compare roles, revisit descriptions, and apply when you are ready without searching again."
                    />
                </LabPanel>

                <LabPanel title="Custom children">
                    <EmptyState title="Dashboard section empty">
                        <p className="text-muted">Use children for supplementary structured content.</p>
                    </EmptyState>
                </LabPanel>

                <LabPanel title="Semantics">
                    <ul className="max-w-lg list-inside list-disc space-y-1 text-sm text-muted">
                        <li>Static presentational block — not an error, toast, or live alert</li>
                        <li>No automatic heading levels (title is styled text)</li>
                        <li>Actions are caller-provided React nodes</li>
                    </ul>
                </LabPanel>
            </div>

            <InfoPanel entry={emptyStateCatalog} />
        </div>
    );
}
