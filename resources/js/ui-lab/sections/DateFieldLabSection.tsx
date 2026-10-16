import { DateField, FormField } from '@/Components/molecules';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { dateFieldCatalog } from '@/ui-lab/catalog';

export default function DateFieldLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Basic">
                    <div className="max-w-md space-y-4">
                        <DateField id="ui-lab-date-empty" name="ui-lab-date-empty" aria-label="Date" />
                        <DateField
                            id="ui-lab-date-value"
                            name="ui-lab-date-value"
                            defaultValue="2026-04-15"
                            aria-label="Date with value"
                        />
                    </div>
                </LabPanel>

                <LabPanel title="Constraints" description="min, max, and step use native HTML semantics.">
                    <div className="max-w-md space-y-4">
                        <DateField
                            id="ui-lab-date-min"
                            name="ui-lab-date-min"
                            min="2026-01-01"
                            aria-label="Not before 2026-01-01"
                        />
                        <DateField
                            id="ui-lab-date-max"
                            name="ui-lab-date-max"
                            max="2026-12-31"
                            defaultValue="2026-06-01"
                            aria-label="Not after 2026-12-31"
                        />
                        <DateField
                            id="ui-lab-date-step"
                            name="ui-lab-date-step"
                            min="2026-01-01"
                            step={7}
                            aria-label="Weekly step (7 days)"
                        />
                    </div>
                </LabPanel>

                <LabPanel title="States">
                    <div className="max-w-md space-y-4">
                        <DateField id="ui-lab-date-normal" name="ui-lab-date-normal" aria-label="Normal date" />
                        <DateField
                            id="ui-lab-date-disabled"
                            name="ui-lab-date-disabled"
                            disabled
                            defaultValue="2026-01-01"
                            aria-label="Disabled date"
                        />
                        <DateField
                            id="ui-lab-date-required"
                            name="ui-lab-date-required"
                            required
                            aria-label="Required date"
                        />
                        <DateField id="ui-lab-date-error" name="ui-lab-date-error" error aria-label="Date error" />
                    </div>
                </LabPanel>

                <LabPanel title="Surfaces">
                    <div className="max-w-md grid gap-3 sm:grid-cols-2">
                        {(['public', 'account', 'admin', 'corporate'] as const).map((surface) => (
                            <DateField
                                key={surface}
                                surface={surface}
                                id={`ui-lab-date-surface-${surface}`}
                                name={`ui-lab-date-surface-${surface}`}
                                aria-label={`Date field ${surface}`}
                            />
                        ))}
                    </div>
                </LabPanel>

                <LabPanel title="FormField">
                    <div className="max-w-md">
                        <FormField
                            id="ui-lab-date-start"
                            label="Start date"
                            hint="Uses the browser native date picker."
                            error="Start date is required."
                            required
                        >
                            <DateField id="ui-lab-date-start" name="ui-lab-date-start" />
                        </FormField>
                    </div>
                </LabPanel>

                <LabPanel
                    title="Native behavior"
                    description="Click or focus the control — calendar UI is provided by the browser (showPicker on click when supported, via Input)."
                >
                    <div className="max-w-md">
                        <DateField
                            id="ui-lab-date-native"
                            name="ui-lab-date-native"
                            aria-label="Native picker demo"
                        />
                    </div>
                </LabPanel>
            </div>

            <InfoPanel entry={dateFieldCatalog} />
        </div>
    );
}
