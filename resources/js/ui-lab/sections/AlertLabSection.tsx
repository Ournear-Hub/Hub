import Input from '@/Components/atoms/Input';
import { Alert, FormField } from '@/Components/molecules';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { alertCatalog } from '@/ui-lab/catalog';
import { useState } from 'react';

export default function AlertLabSection() {
    const [showDismissible, setShowDismissible] = useState(true);

    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Info">
                    <Alert variant="info">Please verify your email address to unlock all features.</Alert>
                </LabPanel>

                <LabPanel title="Success">
                    <Alert variant="success">Application submitted successfully.</Alert>
                </LabPanel>

                <LabPanel title="Warning">
                    <Alert variant="warning" title="Profile incomplete">
                        Complete your profile before applying to premium roles.
                    </Alert>
                </LabPanel>

                <LabPanel title="Error">
                    <Alert variant="error">Something went wrong. Please try again.</Alert>
                </LabPanel>

                <LabPanel title="Dismissible">
                    {showDismissible ? (
                        <Alert variant="info" dismissible onDismiss={() => setShowDismissible(false)}>
                            This job is no longer accepting applications.
                        </Alert>
                    ) : (
                        <p className="text-sm text-muted">Alert dismissed — caller removed it from the tree.</p>
                    )}
                </LabPanel>

                <LabPanel title="Long message">
                    <Alert variant="warning">
                        Your recruiter notes exceed the recommended length for quick scanning. Consider summarizing
                        key points at the top so hiring managers can review applications faster during peak hiring
                        periods across multiple locations and departments.
                    </Alert>
                </LabPanel>

                <LabPanel title="With canonical form context">
                    <Alert variant="info" className="mb-4">
                        Fields marked required must be completed before submit.
                    </Alert>
                    <FormField id="ui-lab-alert-name" label="Display name">
                        <Input id="ui-lab-alert-name" name="display_name" defaultValue="Sample candidate" />
                    </FormField>
                </LabPanel>

                <LabPanel title="Accessibility semantics">
                    <ul className="max-w-lg list-inside list-disc space-y-1 text-sm text-muted">
                        <li>
                            <code className="text-ink">error</code> → <code className="text-ink">role="alert"</code>{' '}
                            (assertive)
                        </li>
                        <li>
                            <code className="text-ink">info</code>, <code className="text-ink">success</code>,{' '}
                            <code className="text-ink">warning</code> → <code className="text-ink">role="status"</code>{' '}
                            (polite live region)
                        </li>
                        <li>Variant icon + border + text — not color alone</li>
                        <li>Dismiss uses IconButton with accessible name &quot;Dismiss&quot;</li>
                    </ul>
                </LabPanel>
            </div>

            <InfoPanel entry={alertCatalog} />
        </div>
    );
}
