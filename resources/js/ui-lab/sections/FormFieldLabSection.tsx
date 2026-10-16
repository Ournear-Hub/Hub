import {
    Checkbox,
    Input,
    Radio,
    Select,
    Switch,
    Textarea,
} from '@/Components/atoms';
import { FormField } from '@/Components/molecules';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { formFieldCatalog } from '@/ui-lab/catalog';

export default function FormFieldLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Input with hint">
                    <div className="max-w-md">
                        <FormField
                            id="ui-lab-ff-email"
                            label="Email address"
                            hint="Use your work email address."
                        >
                            <Input id="ui-lab-ff-email" name="email" type="email" placeholder="you@company.com" />
                        </FormField>
                    </div>
                </LabPanel>

                <LabPanel title="Input with error">
                    <div className="max-w-md">
                        <FormField
                            id="ui-lab-ff-email-error"
                            label="Email address"
                            hint="Use your work email address."
                            error="Email is required."
                            required
                        >
                            <Input id="ui-lab-ff-email-error" name="email" type="email" />
                        </FormField>
                    </div>
                </LabPanel>

                <LabPanel title="Textarea">
                    <div className="max-w-md">
                        <FormField
                            id="ui-lab-ff-description"
                            label="Description"
                            hint="Maximum 500 characters."
                        >
                            <Textarea id="ui-lab-ff-description" name="description" rows={4} />
                        </FormField>
                    </div>
                </LabPanel>

                <LabPanel title="Select">
                    <div className="max-w-md">
                        <FormField id="ui-lab-ff-employment" label="Employment type">
                            <Select id="ui-lab-ff-employment" name="employment_type" defaultValue="">
                                <option value="" disabled>
                                    Select employment type
                                </option>
                                <option value="full_time">Full time</option>
                                <option value="contract">Contract</option>
                                <option value="temporary">Temporary</option>
                            </Select>
                        </FormField>
                    </div>
                </LabPanel>

                <LabPanel title="Required label">
                    <div className="max-w-md">
                        <FormField id="ui-lab-ff-required" label="Legal name" required hint="As shown on government ID.">
                            <Input id="ui-lab-ff-required" name="legal_name" required />
                        </FormField>
                    </div>
                </LabPanel>

                <LabPanel title="Checkbox" description="Label associates with the control via shared id.">
                    <FormField
                        id="ui-lab-ff-terms"
                        label="Accept terms and conditions"
                        hint="Required before submitting your application."
                    >
                        <Checkbox id="ui-lab-ff-terms" name="terms" />
                    </FormField>
                </LabPanel>

                <LabPanel title="Radio">
                    <FormField id="ui-lab-ff-full-time" label="Full time">
                        <Radio
                            id="ui-lab-ff-full-time"
                            name="ui-lab-ff-employment-radio"
                            value="full_time"
                            defaultChecked
                        />
                    </FormField>
                </LabPanel>

                <LabPanel title="Switch">
                    <FormField
                        id="ui-lab-ff-notifications"
                        label="Email notifications"
                        hint="Receive important account notifications by email."
                    >
                        <Switch id="ui-lab-ff-notifications" name="email_notifications" defaultChecked />
                    </FormField>
                </LabPanel>
            </div>

            <InfoPanel entry={formFieldCatalog} />
        </div>
    );
}
