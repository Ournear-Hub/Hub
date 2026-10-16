import Input from '@/Components/atoms/Input';
import {
    DateField,
    EmailField,
    FieldGroup,
    FieldSet,
    FormField,
    NumberField,
} from '@/Components/molecules';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { fieldSetCatalog } from '@/ui-lab/catalog';

export default function FieldSetLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Basic">
                    <div className="max-w-md">
                        <FieldSet legend="Personal information">
                            <FormField id="ui-lab-fs-given" label="First name">
                                <Input id="ui-lab-fs-given" name="given_name" autoComplete="given-name" />
                            </FormField>
                        </FieldSet>
                    </div>
                </LabPanel>

                <LabPanel title="Description">
                    <div className="max-w-md">
                        <FieldSet
                            legend="Contact information"
                            description="How we can reach you about applications."
                        >
                            <FormField id="ui-lab-fs-email" label="Email">
                                <EmailField id="ui-lab-fs-email" name="email" autoComplete="email" />
                            </FormField>
                        </FieldSet>
                    </div>
                </LabPanel>

                <LabPanel title="FieldSet + FieldGroup + FormField">
                    <div className="max-w-xl">
                        <FieldSet legend="Personal information" description="Basic candidate details">
                            <FieldGroup orientation="horizontal">
                                <FormField id="ui-lab-fs-first" label="First name" className="min-w-0 flex-1">
                                    <Input id="ui-lab-fs-first" name="first_name" autoComplete="given-name" />
                                </FormField>
                                <FormField id="ui-lab-fs-last" label="Last name" className="min-w-0 flex-1">
                                    <Input id="ui-lab-fs-last" name="last_name" autoComplete="family-name" />
                                </FormField>
                            </FieldGroup>
                        </FieldSet>
                    </div>
                </LabPanel>

                <LabPanel title="Field molecules">
                    <div className="max-w-xl space-y-8">
                        <FieldSet legend="Availability">
                            <FieldGroup orientation="horizontal">
                                <FormField id="ui-lab-fs-from" label="From" className="min-w-0 flex-1">
                                    <DateField id="ui-lab-fs-from" name="available_from" />
                                </FormField>
                                <FormField id="ui-lab-fs-until" label="Until" className="min-w-0 flex-1">
                                    <DateField id="ui-lab-fs-until" name="available_until" />
                                </FormField>
                            </FieldGroup>
                        </FieldSet>
                        <FieldSet legend="Compensation">
                            <FormField id="ui-lab-fs-salary" label="Expected salary">
                                <NumberField id="ui-lab-fs-salary" name="expected_salary" min={0} />
                            </FormField>
                        </FieldSet>
                    </div>
                </LabPanel>

                <LabPanel title="Semantic separation">
                    <div className="max-w-xl space-y-8">
                        <FieldSet legend="Job requirements">
                            <FormField id="ui-lab-fs-role" label="Role title">
                                <Input id="ui-lab-fs-role" name="role_title" />
                            </FormField>
                        </FieldSet>
                        <FieldSet legend="Notification preferences">
                            <FormField id="ui-lab-fs-notify" label="Notify by email" hint="Optional — per-field hint stays on FormField.">
                                <Input id="ui-lab-fs-notify" name="notify_email" type="email" />
                            </FormField>
                        </FieldSet>
                    </div>
                </LabPanel>

                <LabPanel title="Required & group error">
                    <div className="max-w-md space-y-8">
                        <FieldSet legend="Employment preferences" required>
                            <FormField id="ui-lab-fs-pref-a" label="Preferred shift">
                                <Input id="ui-lab-fs-pref-a" name="shift" />
                            </FormField>
                        </FieldSet>
                        <FieldSet
                            legend="Address"
                            description="Provide a reachable mailing address."
                            error="Complete street and city before continuing."
                        >
                            <FormField id="ui-lab-fs-street" label="Street">
                                <Input id="ui-lab-fs-street" name="street" />
                            </FormField>
                            <FormField id="ui-lab-fs-city" label="City" className="mt-4">
                                <Input id="ui-lab-fs-city" name="city" />
                            </FormField>
                        </FieldSet>
                    </div>
                </LabPanel>
            </div>

            <InfoPanel entry={fieldSetCatalog} />
        </div>
    );
}
