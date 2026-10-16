import { Input, Label } from '@/Components/atoms';
import { errorText } from '@/design-system/classNames';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { inputCatalog } from '@/ui-lab/catalog';

export default function InputLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Basic" description="Single established control size (text-sm, min-h-10 on admin surface).">
                    <div className="max-w-md space-y-4">
                        <div>
                            <Label htmlFor="ui-lab-input-default">Default</Label>
                            <Input id="ui-lab-input-default" name="default" placeholder="Placeholder text" />
                        </div>
                        <div>
                            <Label htmlFor="ui-lab-input-populated">Populated</Label>
                            <Input id="ui-lab-input-populated" name="populated" defaultValue="user@example.com" />
                        </div>
                    </div>
                </LabPanel>

                <LabPanel title="Types" description="Representative HTML types used in Hire.">
                    <div className="max-w-md grid gap-4 sm:grid-cols-2">
                        <div>
                            <Label htmlFor="ui-lab-type-text">Text</Label>
                            <Input id="ui-lab-type-text" type="text" placeholder="Text" />
                        </div>
                        <div>
                            <Label htmlFor="ui-lab-type-email">Email</Label>
                            <Input id="ui-lab-type-email" type="email" placeholder="you@example.com" autoComplete="email" />
                        </div>
                        <div>
                            <Label htmlFor="ui-lab-type-password">Password</Label>
                            <Input id="ui-lab-type-password" type="password" autoComplete="off" />
                        </div>
                        <div>
                            <Label htmlFor="ui-lab-type-number">Number</Label>
                            <Input id="ui-lab-type-number" type="number" min={0} max={100} defaultValue={42} />
                        </div>
                        <div>
                            <Label htmlFor="ui-lab-type-date">Date</Label>
                            <Input id="ui-lab-type-date" type="date" />
                        </div>
                        <div>
                            <Label htmlFor="ui-lab-type-search">Search</Label>
                            <Input id="ui-lab-type-search" type="search" placeholder="Search…" />
                        </div>
                    </div>
                </LabPanel>

                <LabPanel title="Surfaces" description="Public/account use controlBase; admin/corporate use adminControlBase.">
                    <div className="max-w-md space-y-4">
                        <div>
                            <Label htmlFor="ui-lab-surface-public">Public / account</Label>
                            <Input id="ui-lab-surface-public" surface="public" placeholder="Public surface" />
                        </div>
                        <div>
                            <Label htmlFor="ui-lab-surface-admin">Admin / corporate</Label>
                            <Input id="ui-lab-surface-admin" surface="admin" placeholder="Admin surface" />
                        </div>
                    </div>
                </LabPanel>

                <LabPanel title="States">
                    <div className="max-w-md space-y-4">
                        <div>
                            <Label htmlFor="ui-lab-state-disabled">Disabled</Label>
                            <Input id="ui-lab-state-disabled" disabled defaultValue="Cannot edit" />
                        </div>
                        <div>
                            <Label htmlFor="ui-lab-state-readonly">Readonly</Label>
                            <Input id="ui-lab-state-readonly" readOnly defaultValue="Read only value" />
                        </div>
                        <div>
                            <Label htmlFor="ui-lab-state-error">Error</Label>
                            <Input
                                id="ui-lab-state-error"
                                error
                                defaultValue="invalid@"
                                aria-describedby="ui-lab-state-error-msg"
                            />
                            <p id="ui-lab-state-error-msg" className={errorText}>
                                Enter a valid email address. (Message is not part of the Input atom.)
                            </p>
                        </div>
                    </div>
                </LabPanel>

                <LabPanel title="Accessibility" description="Label + Input + describedby — FormField deferred.">
                    <div className="max-w-md">
                        <Label htmlFor="ui-lab-a11y-email" required>
                            Work email
                        </Label>
                        <Input
                            id="ui-lab-a11y-email"
                            name="work_email"
                            type="email"
                            autoComplete="email"
                            aria-describedby="ui-lab-a11y-hint"
                            placeholder="name@company.com"
                        />
                        <p id="ui-lab-a11y-hint" className="mt-1 text-xs text-muted">
                            Used for account notifications only.
                        </p>
                    </div>
                </LabPanel>
            </div>

            <InfoPanel entry={inputCatalog} />
        </div>
    );
}
