import Input from '@/Components/atoms/Input';
import { DateField, FieldGroup, FormField, NumberField } from '@/Components/molecules';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { fieldGroupCatalog } from '@/ui-lab/catalog';

export default function FieldGroupLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Basic">
                    <div className="max-w-md space-y-6">
                        <FieldGroup>
                            <Input id="ui-lab-fg-stack-a" name="a" placeholder="First control" aria-label="First" />
                            <Input id="ui-lab-fg-stack-b" name="b" placeholder="Second control" aria-label="Second" />
                        </FieldGroup>
                        <FieldGroup orientation="horizontal" label="Horizontal pair">
                            <Input id="ui-lab-fg-h-a" name="h-a" placeholder="Left" aria-label="Left" />
                            <Input id="ui-lab-fg-h-b" name="h-b" placeholder="Right" aria-label="Right" />
                        </FieldGroup>
                    </div>
                </LabPanel>

                <LabPanel title="Composition">
                    <div className="max-w-xl space-y-6">
                        <FieldGroup label="Name">
                            <FormField id="ui-lab-fg-first" label="First name" className="min-w-0 flex-1 sm:min-w-[140px]">
                                <Input id="ui-lab-fg-first" name="first_name" autoComplete="given-name" />
                            </FormField>
                            <FormField id="ui-lab-fg-last" label="Last name" className="min-w-0 flex-1 sm:min-w-[140px]">
                                <Input id="ui-lab-fg-last" name="last_name" autoComplete="family-name" />
                            </FormField>
                        </FieldGroup>

                        <FieldGroup label="Date range" orientation="horizontal" gap="md">
                            <FormField id="ui-lab-fg-start" label="Start" className="min-w-0 flex-1">
                                <DateField id="ui-lab-fg-start" name="start_date" />
                            </FormField>
                            <FormField id="ui-lab-fg-end" label="End" className="min-w-0 flex-1">
                                <DateField id="ui-lab-fg-end" name="end_date" />
                            </FormField>
                        </FieldGroup>

                        <FieldGroup label="Match score" orientation="horizontal">
                            <FormField id="ui-lab-fg-min" label="Minimum" className="min-w-0 flex-1">
                                <NumberField id="ui-lab-fg-min" name="match_min" min={0} max={100} />
                            </FormField>
                            <FormField id="ui-lab-fg-max" label="Maximum" className="min-w-0 flex-1">
                                <NumberField id="ui-lab-fg-max" name="match_max" min={0} max={100} />
                            </FormField>
                        </FieldGroup>
                    </div>
                </LabPanel>

                <LabPanel title="Semantics">
                    <div className="max-w-md space-y-6">
                        <FieldGroup
                            label="Contact"
                            hint="Provide at least one reachable email."
                            required
                        >
                            <FormField id="ui-lab-fg-contact-email" label="Email">
                                <Input id="ui-lab-fg-contact-email" name="email" type="email" />
                            </FormField>
                        </FieldGroup>
                        <FieldGroup
                            label="Budget range"
                            error="Enter both minimum and maximum."
                            orientation="horizontal"
                        >
                            <FormField id="ui-lab-fg-budget-min" label="Min" className="min-w-0 flex-1">
                                <NumberField id="ui-lab-fg-budget-min" name="budget_min" min={0} />
                            </FormField>
                            <FormField id="ui-lab-fg-budget-max" label="Max" className="min-w-0 flex-1">
                                <NumberField id="ui-lab-fg-budget-max" name="budget_max" min={0} />
                            </FormField>
                        </FieldGroup>
                    </div>
                </LabPanel>

                <LabPanel title="Layout">
                    <div className="max-w-xl grid gap-6 sm:grid-cols-2">
                        <FieldGroup label="Vertical (default)" gap="sm">
                            <Input id="ui-lab-fg-v-1" aria-label="Vertical 1" />
                            <Input id="ui-lab-fg-v-2" aria-label="Vertical 2" />
                        </FieldGroup>
                        <FieldGroup label="Horizontal" orientation="horizontal" gap="lg">
                            <Input id="ui-lab-fg-ho-1" aria-label="Horizontal 1" className="min-w-0 flex-1" />
                            <Input id="ui-lab-fg-ho-2" aria-label="Horizontal 2" className="min-w-0 flex-1" />
                        </FieldGroup>
                    </div>
                </LabPanel>
            </div>

            <InfoPanel entry={fieldGroupCatalog} />
        </div>
    );
}
