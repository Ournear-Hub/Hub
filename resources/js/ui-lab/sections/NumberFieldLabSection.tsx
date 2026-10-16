import { FormField, NumberField } from '@/Components/molecules';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { numberFieldCatalog } from '@/ui-lab/catalog';

export default function NumberFieldLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Basic">
                    <div className="max-w-md space-y-4">
                        <NumberField id="ui-lab-number-empty" name="ui-lab-number-empty" aria-label="Number" />
                        <NumberField
                            id="ui-lab-number-value"
                            name="ui-lab-number-value"
                            defaultValue={42}
                            aria-label="Number with value"
                        />
                    </div>
                </LabPanel>

                <LabPanel title="Constraints" description="min, max, and step use native HTML number semantics.">
                    <div className="max-w-md space-y-4">
                        <NumberField
                            id="ui-lab-number-min-max"
                            name="ui-lab-number-min-max"
                            min={0}
                            max={100}
                            defaultValue={50}
                            aria-label="0 to 100"
                        />
                        <NumberField
                            id="ui-lab-number-integer"
                            name="ui-lab-number-integer"
                            step={1}
                            min={0}
                            defaultValue={3}
                            aria-label="Integer step 1"
                        />
                        <NumberField
                            id="ui-lab-number-decimal"
                            name="ui-lab-number-decimal"
                            step={0.01}
                            min={0}
                            defaultValue={19.99}
                            aria-label="Decimal step 0.01"
                        />
                    </div>
                </LabPanel>

                <LabPanel title="States">
                    <div className="max-w-md space-y-4">
                        <NumberField id="ui-lab-number-normal" name="ui-lab-number-normal" aria-label="Normal" />
                        <NumberField
                            id="ui-lab-number-disabled"
                            name="ui-lab-number-disabled"
                            disabled
                            defaultValue={10}
                            aria-label="Disabled"
                        />
                        <NumberField
                            id="ui-lab-number-required"
                            name="ui-lab-number-required"
                            required
                            aria-label="Required"
                        />
                        <NumberField
                            id="ui-lab-number-readonly"
                            name="ui-lab-number-readonly"
                            readOnly
                            defaultValue={7}
                            aria-label="Readonly"
                        />
                        <NumberField id="ui-lab-number-error" name="ui-lab-number-error" error aria-label="Error" />
                    </div>
                </LabPanel>

                <LabPanel title="Surfaces">
                    <div className="max-w-md grid gap-3 sm:grid-cols-2">
                        {(['public', 'account', 'admin', 'corporate'] as const).map((surface) => (
                            <NumberField
                                key={surface}
                                surface={surface}
                                id={`ui-lab-number-surface-${surface}`}
                                name={`ui-lab-number-surface-${surface}`}
                                aria-label={`Number field ${surface}`}
                            />
                        ))}
                    </div>
                </LabPanel>

                <LabPanel title="FormField">
                    <div className="max-w-md">
                        <FormField
                            id="ui-lab-number-quantity"
                            label="Quantity"
                            hint="Enter the required quantity."
                            error="Quantity must be at least 1."
                            required
                        >
                            <NumberField id="ui-lab-number-quantity" name="ui-lab-number-quantity" min={1} step={1} />
                        </FormField>
                    </div>
                </LabPanel>

                <LabPanel
                    title="Native behavior"
                    description="Browser provides native increment/decrement controls and constraint validation — no custom spinners."
                >
                    <div className="max-w-md">
                        <NumberField
                            id="ui-lab-number-native"
                            name="ui-lab-number-native"
                            min={0}
                            max={10}
                            defaultValue={5}
                            aria-label="Native number demo"
                        />
                    </div>
                </LabPanel>
            </div>

            <InfoPanel entry={numberFieldCatalog} />
        </div>
    );
}
