import { useState } from 'react';
import { Label, Switch, Text } from '@/Components/atoms';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { switchCatalog } from '@/ui-lab/catalog';

export default function SwitchLabSection() {
    const [controlled, setControlled] = useState(true);

    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Basic">
                    <div className="flex flex-col gap-4">
                        <div className="flex items-center gap-3">
                            <Switch id="ui-lab-switch-off" name="ui-lab-switch-basic-off" />
                            <Label htmlFor="ui-lab-switch-off" className="mb-0 font-normal">
                                OFF
                            </Label>
                        </div>
                        <div className="flex items-center gap-3">
                            <Switch id="ui-lab-switch-on" name="ui-lab-switch-basic-on" defaultChecked />
                            <Label htmlFor="ui-lab-switch-on" className="mb-0 font-normal">
                                ON (defaultChecked)
                            </Label>
                        </div>
                        <div className="flex items-center gap-3">
                            <Switch
                                id="ui-lab-switch-controlled"
                                name="ui-lab-switch-controlled"
                                checked={controlled}
                                onChange={(event) => setControlled(event.currentTarget.checked)}
                            />
                            <Label htmlFor="ui-lab-switch-controlled" className="mb-0 font-normal">
                                Controlled ({controlled ? 'ON' : 'OFF'})
                            </Label>
                        </div>
                    </div>
                </LabPanel>

                <LabPanel title="States">
                    <div className="flex flex-col gap-4">
                        <div className="flex items-center gap-3">
                            <Switch id="ui-lab-switch-normal-off" name="ui-lab-switch-states-a" />
                            <Label htmlFor="ui-lab-switch-normal-off" className="mb-0 font-normal">
                                Normal OFF
                            </Label>
                        </div>
                        <div className="flex items-center gap-3">
                            <Switch id="ui-lab-switch-normal-on" name="ui-lab-switch-states-b" defaultChecked />
                            <Label htmlFor="ui-lab-switch-normal-on" className="mb-0 font-normal">
                                Normal ON
                            </Label>
                        </div>
                        <div className="flex items-center gap-3">
                            <Switch id="ui-lab-switch-disabled-off" name="ui-lab-switch-states-c" disabled />
                            <Label htmlFor="ui-lab-switch-disabled-off" className="mb-0 font-normal text-disabled">
                                Disabled OFF
                            </Label>
                        </div>
                        <div className="flex items-center gap-3">
                            <Switch id="ui-lab-switch-disabled-on" name="ui-lab-switch-states-d" disabled defaultChecked />
                            <Label htmlFor="ui-lab-switch-disabled-on" className="mb-0 font-normal text-disabled">
                                Disabled ON
                            </Label>
                        </div>
                        <p className="text-xs text-muted">Tab to a switch above to verify focus-visible ring on the track.</p>
                    </div>
                </LabPanel>

                <LabPanel title="Surfaces">
                    <div className="grid gap-3 sm:grid-cols-2">
                        {(
                            [
                                ['public', 'ui-lab-switch-s-public'],
                                ['account', 'ui-lab-switch-s-account'],
                                ['admin', 'ui-lab-switch-s-admin'],
                                ['corporate', 'ui-lab-switch-s-corporate'],
                            ] as const
                        ).map(([surface, id]) => (
                            <div key={surface} className="flex items-center gap-3">
                                <Switch id={id} name={`ui-lab-switch-surface-${surface}`} surface={surface} defaultChecked />
                                <Label htmlFor={id} className="mb-0 font-normal capitalize">
                                    {surface}
                                </Label>
                            </div>
                        ))}
                    </div>
                </LabPanel>

                <LabPanel title="Accessibility">
                    <div className="max-w-md space-y-2">
                        <div className="flex items-center justify-between gap-4">
                            <div>
                                <Label htmlFor="ui-lab-switch-email" className="mb-0">
                                    Email notifications
                                </Label>
                                <span id="ui-lab-switch-email-description">
                                    <Text as="span" variant="caption" tone="muted" className="mt-1 block">
                                        Receive important account notifications by email.
                                    </Text>
                                </span>
                            </div>
                            <Switch
                                id="ui-lab-switch-email"
                                name="ui-lab-switch-email"
                                defaultChecked
                                aria-describedby="ui-lab-switch-email-description"
                            />
                        </div>
                    </div>
                </LabPanel>
            </div>

            <InfoPanel entry={switchCatalog} />
        </div>
    );
}
