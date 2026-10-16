import { Label, Textarea } from '@/Components/atoms';
import { errorText } from '@/design-system/classNames';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { textareaCatalog } from '@/ui-lab/catalog';

export default function TextareaLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Basic">
                    <div className="max-w-md space-y-4">
                        <div>
                            <Label htmlFor="ui-lab-ta-empty">Empty</Label>
                            <Textarea id="ui-lab-ta-empty" name="empty" placeholder="Enter description…" rows={4} />
                        </div>
                        <div>
                            <Label htmlFor="ui-lab-ta-populated">Populated</Label>
                            <Textarea
                                id="ui-lab-ta-populated"
                                name="populated"
                                defaultValue="OurNear helps teams hire with clarity and speed."
                                rows={4}
                            />
                        </div>
                    </div>
                </LabPanel>

                <LabPanel title="States">
                    <div className="max-w-md space-y-4">
                        <div>
                            <Label htmlFor="ui-lab-ta-normal">Normal</Label>
                            <Textarea id="ui-lab-ta-normal" rows={3} />
                        </div>
                        <div>
                            <Label htmlFor="ui-lab-ta-disabled">Disabled</Label>
                            <Textarea id="ui-lab-ta-disabled" disabled defaultValue="Locked content" rows={3} />
                        </div>
                        <div>
                            <Label htmlFor="ui-lab-ta-readonly">Readonly</Label>
                            <Textarea id="ui-lab-ta-readonly" readOnly defaultValue="Read only notes" rows={3} />
                        </div>
                        <div>
                            <Label htmlFor="ui-lab-ta-error">Error</Label>
                            <Textarea
                                id="ui-lab-ta-error"
                                error
                                defaultValue="Too short"
                                rows={3}
                                aria-describedby="ui-lab-ta-error-msg"
                            />
                            <p id="ui-lab-ta-error-msg" className={errorText}>
                                Minimum 20 characters. (Not part of the Textarea atom.)
                            </p>
                        </div>
                    </div>
                </LabPanel>

                <LabPanel title="Surfaces" description="Public/account min-h-28; admin/corporate min-h-24 (V2 convention).">
                    <div className="max-w-md space-y-4">
                        <div>
                            <Label htmlFor="ui-lab-ta-public">Public</Label>
                            <Textarea id="ui-lab-ta-public" surface="public" rows={3} placeholder="Public surface" />
                        </div>
                        <div>
                            <Label htmlFor="ui-lab-ta-account">Account</Label>
                            <Textarea id="ui-lab-ta-account" surface="account" rows={3} placeholder="Account surface" />
                        </div>
                        <div>
                            <Label htmlFor="ui-lab-ta-admin">Admin</Label>
                            <Textarea id="ui-lab-ta-admin" surface="admin" rows={3} placeholder="Admin surface" />
                        </div>
                        <div>
                            <Label htmlFor="ui-lab-ta-corporate">Corporate</Label>
                            <Textarea id="ui-lab-ta-corporate" surface="corporate" rows={3} placeholder="Corporate surface" />
                        </div>
                    </div>
                </LabPanel>

                <LabPanel title="Native behavior" description="Browser default vertical resize; rows sets initial height.">
                    <div className="max-w-md">
                        <Label htmlFor="ui-lab-ta-rows">Rows = 6</Label>
                        <Textarea id="ui-lab-ta-rows" rows={6} placeholder="Drag the corner to resize vertically (browser default)." />
                    </div>
                </LabPanel>

                <LabPanel title="Accessibility">
                    <div className="max-w-md">
                        <Label htmlFor="ui-lab-ta-a11y" required>
                            Job summary
                        </Label>
                        <Textarea
                            id="ui-lab-ta-a11y"
                            name="summary"
                            rows={4}
                            aria-describedby="ui-lab-ta-a11y-hint"
                            placeholder="Brief overview for candidates"
                        />
                        <p id="ui-lab-ta-a11y-hint" className="mt-1 text-xs text-muted">
                            Shown on the public job listing when published.
                        </p>
                    </div>
                </LabPanel>
            </div>

            <InfoPanel entry={textareaCatalog} />
        </div>
    );
}
