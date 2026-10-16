import { FormField, TimeField } from '@/Components/molecules';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { timeFieldCatalog } from '@/ui-lab/catalog';

export default function TimeFieldLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Basic">
                    <div className="max-w-md space-y-4">
                        <TimeField id="ui-lab-time-empty" name="ui-lab-time-empty" aria-label="Time" />
                        <TimeField
                            id="ui-lab-time-value"
                            name="ui-lab-time-value"
                            defaultValue="09:30"
                            aria-label="Time with value"
                        />
                    </div>
                </LabPanel>

                <LabPanel title="Constraints" description="min, max, and step use native HTML semantics (HH:MM or HH:MM:SS).">
                    <div className="max-w-md space-y-4">
                        <TimeField
                            id="ui-lab-time-min"
                            name="ui-lab-time-min"
                            min="09:00"
                            aria-label="Not before 09:00"
                        />
                        <TimeField
                            id="ui-lab-time-max"
                            name="ui-lab-time-max"
                            max="17:00"
                            defaultValue="12:00"
                            aria-label="Not after 17:00"
                        />
                        <TimeField
                            id="ui-lab-time-step"
                            name="ui-lab-time-step"
                            step={900}
                            defaultValue="10:00"
                            aria-label="15-minute step (900 seconds)"
                        />
                    </div>
                </LabPanel>

                <LabPanel title="States">
                    <div className="max-w-md space-y-4">
                        <TimeField id="ui-lab-time-normal" name="ui-lab-time-normal" aria-label="Normal time" />
                        <TimeField
                            id="ui-lab-time-disabled"
                            name="ui-lab-time-disabled"
                            disabled
                            defaultValue="14:00"
                            aria-label="Disabled time"
                        />
                        <TimeField
                            id="ui-lab-time-required"
                            name="ui-lab-time-required"
                            required
                            aria-label="Required time"
                        />
                        <TimeField
                            id="ui-lab-time-readonly"
                            name="ui-lab-time-readonly"
                            readOnly
                            defaultValue="08:00"
                            aria-label="Readonly time"
                        />
                        <TimeField id="ui-lab-time-error" name="ui-lab-time-error" error aria-label="Time error" />
                    </div>
                </LabPanel>

                <LabPanel title="Surfaces">
                    <div className="max-w-md grid gap-3 sm:grid-cols-2">
                        {(['public', 'account', 'admin', 'corporate'] as const).map((surface) => (
                            <TimeField
                                key={surface}
                                surface={surface}
                                id={`ui-lab-time-surface-${surface}`}
                                name={`ui-lab-time-surface-${surface}`}
                                aria-label={`Time field ${surface}`}
                            />
                        ))}
                    </div>
                </LabPanel>

                <LabPanel title="FormField">
                    <div className="max-w-md">
                        <FormField
                            id="ui-lab-time-start"
                            label="Start time"
                            hint="Select a time using the browser native picker."
                            error="Start time is required."
                            required
                        >
                            <TimeField id="ui-lab-time-start" name="ui-lab-time-start" />
                        </FormField>
                    </div>
                </LabPanel>

                <LabPanel
                    title="Native behavior"
                    description="Click the field — time picker UI is browser-controlled (showPicker on click when supported, via Input)."
                >
                    <div className="max-w-md">
                        <TimeField
                            id="ui-lab-time-native"
                            name="ui-lab-time-native"
                            aria-label="Native picker demo"
                        />
                    </div>
                </LabPanel>
            </div>

            <InfoPanel entry={timeFieldCatalog} />
        </div>
    );
}
