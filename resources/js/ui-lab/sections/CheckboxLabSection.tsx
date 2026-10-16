import { useState } from 'react';
import { Checkbox, Label } from '@/Components/atoms';
import { errorText } from '@/design-system/classNames';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { checkboxCatalog } from '@/ui-lab/catalog';

export default function CheckboxLabSection() {
    const [controlled, setControlled] = useState(false);

    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Basic">
                    <div className="flex flex-col gap-4">
                        <div className="flex items-center gap-2">
                            <Checkbox id="ui-lab-cb-unchecked" name="unchecked" />
                            <Label htmlFor="ui-lab-cb-unchecked" className="mb-0 font-normal">
                                Unchecked
                            </Label>
                        </div>
                        <div className="flex items-center gap-2">
                            <Checkbox id="ui-lab-cb-checked" name="checked" defaultChecked />
                            <Label htmlFor="ui-lab-cb-checked" className="mb-0 font-normal">
                                defaultChecked
                            </Label>
                        </div>
                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="ui-lab-cb-controlled"
                                name="controlled"
                                checked={controlled}
                                onChange={(event) => setControlled(event.currentTarget.checked)}
                            />
                            <Label htmlFor="ui-lab-cb-controlled" className="mb-0 font-normal">
                                Controlled ({controlled ? 'on' : 'off'})
                            </Label>
                        </div>
                    </div>
                </LabPanel>

                <LabPanel title="States">
                    <div className="flex flex-col gap-4">
                        <div className="flex items-center gap-2">
                            <Checkbox id="ui-lab-cb-normal" defaultChecked />
                            <Label htmlFor="ui-lab-cb-normal" className="mb-0 font-normal">
                                Normal
                            </Label>
                        </div>
                        <div className="flex items-center gap-2">
                            <Checkbox id="ui-lab-cb-disabled-off" disabled />
                            <Label htmlFor="ui-lab-cb-disabled-off" className="mb-0 font-normal text-disabled">
                                Disabled unchecked
                            </Label>
                        </div>
                        <div className="flex items-center gap-2">
                            <Checkbox id="ui-lab-cb-disabled-on" disabled defaultChecked />
                            <Label htmlFor="ui-lab-cb-disabled-on" className="mb-0 font-normal text-disabled">
                                Disabled checked
                            </Label>
                        </div>
                        <div>
                            <div className="flex items-center gap-2">
                                <Checkbox id="ui-lab-cb-error" error aria-describedby="ui-lab-cb-error-msg" />
                                <Label htmlFor="ui-lab-cb-error" className="mb-0 font-normal">
                                    Error
                                </Label>
                            </div>
                            <p id="ui-lab-cb-error-msg" className={`${errorText} ml-6`}>
                                You must accept before continuing. (Not part of the Checkbox atom.)
                            </p>
                        </div>
                    </div>
                </LabPanel>

                <LabPanel title="Surfaces" description="Public/account use accent; admin/corporate use brand accent-color.">
                    <div className="grid gap-3 sm:grid-cols-2">
                        {(
                            [
                                ['public', 'ui-lab-cb-s-public'],
                                ['account', 'ui-lab-cb-s-account'],
                                ['admin', 'ui-lab-cb-s-admin'],
                                ['corporate', 'ui-lab-cb-s-corporate'],
                            ] as const
                        ).map(([surface, id]) => (
                            <div key={surface} className="flex items-center gap-2">
                                <Checkbox id={id} surface={surface} defaultChecked />
                                <Label htmlFor={id} className="mb-0 font-normal capitalize">
                                    {surface}
                                </Label>
                            </div>
                        ))}
                    </div>
                </LabPanel>

                <LabPanel title="Accessibility">
                    <div className="max-w-md">
                        <div className="flex items-start gap-2">
                            <Checkbox
                                id="ui-lab-cb-terms"
                                name="terms"
                                aria-describedby="ui-lab-cb-terms-hint"
                                className="mt-0.5"
                            />
                            <div>
                                <Label htmlFor="ui-lab-cb-terms" className="mb-0 font-normal">
                                    Accept terms
                                </Label>
                                <p id="ui-lab-cb-terms-hint" className="mt-1 text-xs text-muted">
                                    Required for account registration.
                                </p>
                            </div>
                        </div>
                    </div>
                </LabPanel>
            </div>

            <InfoPanel entry={checkboxCatalog} />
        </div>
    );
}
