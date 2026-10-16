import { DateTimeField, FormField } from '@/Components/molecules';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { dateTimeFieldCatalog } from '@/ui-lab/catalog';

export default function DateTimeFieldLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Basic">
                    <div className="max-w-md space-y-4">
                        <DateTimeField
                            id="ui-lab-datetime-empty"
                            name="ui-lab-datetime-empty"
                            aria-label="Date and time"
                        />
                        <DateTimeField
                            id="ui-lab-datetime-value"
                            name="ui-lab-datetime-value"
                            defaultValue="2026-04-15T09:30"
                            aria-label="Date and time with value"
                        />
                    </div>
                </LabPanel>

                <LabPanel
                    title="Constraints"
                    description="min, max, and step use native HTML semantics (local date/time, no timezone in value)."
                >
                    <div className="max-w-md space-y-4">
                        <DateTimeField
                            id="ui-lab-datetime-min"
                            name="ui-lab-datetime-min"
                            min="2026-01-01T00:00"
                            aria-label="Not before 2026-01-01 00:00"
                        />
                        <DateTimeField
                            id="ui-lab-datetime-max"
                            name="ui-lab-datetime-max"
                            max="2026-12-31T23:59"
                            defaultValue="2026-06-01T12:00"
                            aria-label="Not after 2026-12-31 23:59"
                        />
                        <DateTimeField
                            id="ui-lab-datetime-step"
                            name="ui-lab-datetime-step"
                            step={900}
                            defaultValue="2026-03-01T10:00"
                            aria-label="15-minute step (900 seconds)"
                        />
                    </div>
                </LabPanel>

                <LabPanel title="States">
                    <div className="max-w-md space-y-4">
                        <DateTimeField
                            id="ui-lab-datetime-normal"
                            name="ui-lab-datetime-normal"
                            aria-label="Normal datetime"
                        />
                        <DateTimeField
                            id="ui-lab-datetime-disabled"
                            name="ui-lab-datetime-disabled"
                            disabled
                            defaultValue="2026-01-01T08:00"
                            aria-label="Disabled datetime"
                        />
                        <DateTimeField
                            id="ui-lab-datetime-required"
                            name="ui-lab-datetime-required"
                            required
                            aria-label="Required datetime"
                        />
                        <DateTimeField
                            id="ui-lab-datetime-readonly"
                            name="ui-lab-datetime-readonly"
                            readOnly
                            defaultValue="2026-02-14T14:30"
                            aria-label="Readonly datetime"
                        />
                        <DateTimeField
                            id="ui-lab-datetime-error"
                            name="ui-lab-datetime-error"
                            error
                            aria-label="Datetime error"
                        />
                    </div>
                </LabPanel>

                <LabPanel title="Surfaces">
                    <div className="max-w-md grid gap-3 sm:grid-cols-2">
                        {(['public', 'account', 'admin', 'corporate'] as const).map((surface) => (
                            <DateTimeField
                                key={surface}
                                surface={surface}
                                id={`ui-lab-datetime-surface-${surface}`}
                                name={`ui-lab-datetime-surface-${surface}`}
                                aria-label={`DateTime field ${surface}`}
                            />
                        ))}
                    </div>
                </LabPanel>

                <LabPanel title="FormField">
                    <div className="max-w-md">
                        <FormField
                            id="ui-lab-datetime-scheduled"
                            label="Scheduled at"
                            hint="Select date and time using the browser native picker."
                            error="Scheduled date and time is required."
                            required
                        >
                            <DateTimeField id="ui-lab-datetime-scheduled" name="ui-lab-datetime-scheduled" />
                        </FormField>
                    </div>
                </LabPanel>

                <LabPanel
                    title="Native behavior"
                    description="Click the field — combined date/time picker UI is browser-controlled (showPicker on click when supported, via Input)."
                >
                    <div className="max-w-md">
                        <DateTimeField
                            id="ui-lab-datetime-native"
                            name="ui-lab-datetime-native"
                            aria-label="Native picker demo"
                        />
                    </div>
                </LabPanel>
            </div>

            <InfoPanel entry={dateTimeFieldCatalog} />
        </div>
    );
}
