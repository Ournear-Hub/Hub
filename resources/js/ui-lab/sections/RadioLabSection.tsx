import { useState } from 'react';
import { Label, Radio } from '@/Components/atoms';
import { errorText } from '@/design-system/classNames';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { radioCatalog } from '@/ui-lab/catalog';

type EmploymentType = 'full_time' | 'contract' | 'temporary';

export default function RadioLabSection() {
    const [controlled, setControlled] = useState(false);
    const [employmentType, setEmploymentType] = useState<EmploymentType>('full_time');

    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Basic">
                    <div className="flex flex-col gap-4">
                        <div className="flex items-center gap-2">
                            <Radio id="ui-lab-radio-unchecked" name="ui-lab-radio-basic-unchecked" value="off" />
                            <Label htmlFor="ui-lab-radio-unchecked" className="mb-0 font-normal">
                                Unchecked
                            </Label>
                        </div>
                        <div className="flex items-center gap-2">
                            <Radio
                                id="ui-lab-radio-checked"
                                name="ui-lab-radio-basic-checked"
                                value="on"
                                defaultChecked
                            />
                            <Label htmlFor="ui-lab-radio-checked" className="mb-0 font-normal">
                                defaultChecked
                            </Label>
                        </div>
                        <div className="flex items-center gap-2">
                            <Radio
                                id="ui-lab-radio-controlled"
                                name="ui-lab-radio-controlled"
                                value="on"
                                checked={controlled}
                                onChange={(event) => setControlled(event.currentTarget.checked)}
                            />
                            <Label htmlFor="ui-lab-radio-controlled" className="mb-0 font-normal">
                                Controlled ({controlled ? 'on' : 'off'})
                            </Label>
                        </div>
                    </div>
                </LabPanel>

                <LabPanel title="Grouping" description="Selection is coordinated by the shared name attribute only.">
                    <fieldset>
                        <legend className="mb-3 text-sm font-medium text-ink">Employment type</legend>
                        <div className="flex flex-col gap-3">
                            {(
                                [
                                    ['full_time', 'Full time', 'ui-lab-employment-full-time'],
                                    ['contract', 'Contract', 'ui-lab-employment-contract'],
                                    ['temporary', 'Temporary', 'ui-lab-employment-temporary'],
                                ] as const
                            ).map(([value, label, id]) => (
                                <div key={value} className="flex items-center gap-2">
                                    <Radio
                                        id={id}
                                        name="ui-lab-employment_type"
                                        value={value}
                                        checked={employmentType === value}
                                        onChange={() => setEmploymentType(value)}
                                    />
                                    <Label htmlFor={id} className="mb-0 font-normal">
                                        {label}
                                    </Label>
                                </div>
                            ))}
                        </div>
                    </fieldset>
                </LabPanel>

                <LabPanel title="States">
                    <div className="flex flex-col gap-4">
                        <div className="flex items-center gap-2">
                            <Radio id="ui-lab-radio-normal" name="ui-lab-radio-states-normal" value="a" defaultChecked />
                            <Label htmlFor="ui-lab-radio-normal" className="mb-0 font-normal">
                                Normal
                            </Label>
                        </div>
                        <div className="flex items-center gap-2">
                            <Radio id="ui-lab-radio-disabled-off" name="ui-lab-radio-states-disabled" value="a" disabled />
                            <Label htmlFor="ui-lab-radio-disabled-off" className="mb-0 font-normal text-disabled">
                                Disabled unchecked
                            </Label>
                        </div>
                        <div className="flex items-center gap-2">
                            <Radio
                                id="ui-lab-radio-disabled-on"
                                name="ui-lab-radio-states-disabled-on"
                                value="a"
                                disabled
                                defaultChecked
                            />
                            <Label htmlFor="ui-lab-radio-disabled-on" className="mb-0 font-normal text-disabled">
                                Disabled checked
                            </Label>
                        </div>
                        <div>
                            <div className="flex items-center gap-2">
                                <Radio
                                    id="ui-lab-radio-error"
                                    name="ui-lab-radio-error"
                                    value="a"
                                    error
                                    aria-describedby="ui-lab-radio-error-msg"
                                />
                                <Label htmlFor="ui-lab-radio-error" className="mb-0 font-normal">
                                    Error
                                </Label>
                            </div>
                            <p id="ui-lab-radio-error-msg" className={`${errorText} ml-6`}>
                                Select an option to continue. (Not part of the Radio atom.)
                            </p>
                        </div>
                    </div>
                </LabPanel>

                <LabPanel title="Surfaces" description="Public/account use accent; admin/corporate use brand accent-color.">
                    <div className="grid gap-3 sm:grid-cols-2">
                        {(
                            [
                                ['public', 'ui-lab-radio-s-public'],
                                ['account', 'ui-lab-radio-s-account'],
                                ['admin', 'ui-lab-radio-s-admin'],
                                ['corporate', 'ui-lab-radio-s-corporate'],
                            ] as const
                        ).map(([surface, id]) => (
                            <div key={surface} className="flex items-center gap-2">
                                <Radio
                                    id={id}
                                    name={`ui-lab-radio-surface-${surface}`}
                                    value={surface}
                                    surface={surface}
                                    defaultChecked
                                />
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
                            <Radio
                                id="ui-lab-radio-terms"
                                name="ui-lab-radio-terms"
                                value="yes"
                                aria-describedby="ui-lab-radio-terms-hint"
                                className="mt-0.5"
                            />
                            <div>
                                <Label htmlFor="ui-lab-radio-terms" className="mb-0 font-normal">
                                    Preferred contact method: email
                                </Label>
                                <p id="ui-lab-radio-terms-hint" className="mt-1 text-xs text-muted">
                                    We will only use this for application updates.
                                </p>
                            </div>
                        </div>
                    </div>
                </LabPanel>
            </div>

            <InfoPanel entry={radioCatalog} />
        </div>
    );
}
