import { EmailField, FormField } from '@/Components/molecules';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { emailFieldCatalog } from '@/ui-lab/catalog';

export default function EmailFieldLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Basic">
                    <div className="max-w-md space-y-4">
                        <EmailField
                            id="ui-lab-email-empty"
                            name="ui-lab-email-empty"
                            placeholder="you@example.com"
                            aria-label="Email"
                        />
                        <EmailField
                            id="ui-lab-email-value"
                            name="ui-lab-email-value"
                            defaultValue="user@example.com"
                            aria-label="Email with value"
                        />
                    </div>
                </LabPanel>

                <LabPanel title="Native features" description="autoComplete, inputMode, maxLength, and pattern are caller-controlled — not enforced by EmailField.">
                    <div className="max-w-md space-y-4">
                        <EmailField
                            id="ui-lab-email-autocomplete"
                            name="ui-lab-email-autocomplete"
                            autoComplete="email"
                            placeholder="you@example.com"
                            aria-label="Email with autoComplete email"
                        />
                        <EmailField
                            id="ui-lab-email-inputmode"
                            name="ui-lab-email-inputmode"
                            inputMode="email"
                            placeholder="inputMode email"
                            aria-label="Email with inputMode email"
                        />
                        <EmailField
                            id="ui-lab-email-maxlength"
                            name="ui-lab-email-maxlength"
                            maxLength={120}
                            placeholder="maxLength 120"
                            aria-label="Email max length"
                        />
                        <EmailField
                            id="ui-lab-email-pattern"
                            name="ui-lab-email-pattern"
                            pattern=".+@example\\.com"
                            title="Must be an @example.com address (native pattern hint only)"
                            placeholder="name@example.com"
                            aria-label="Email with pattern"
                        />
                    </div>
                </LabPanel>

                <LabPanel title="States">
                    <div className="max-w-md space-y-4">
                        <EmailField id="ui-lab-email-normal" name="ui-lab-email-normal" aria-label="Normal email" />
                        <EmailField
                            id="ui-lab-email-disabled"
                            name="ui-lab-email-disabled"
                            disabled
                            defaultValue="locked@example.com"
                            aria-label="Disabled email"
                        />
                        <EmailField
                            id="ui-lab-email-required"
                            name="ui-lab-email-required"
                            required
                            aria-label="Required email"
                        />
                        <EmailField
                            id="ui-lab-email-readonly"
                            name="ui-lab-email-readonly"
                            readOnly
                            defaultValue="readonly@example.com"
                            aria-label="Readonly email"
                        />
                        <EmailField id="ui-lab-email-error" name="ui-lab-email-error" error aria-label="Email error" />
                    </div>
                </LabPanel>

                <LabPanel title="Surfaces">
                    <div className="max-w-md grid gap-3 sm:grid-cols-2">
                        {(['public', 'account', 'admin', 'corporate'] as const).map((surface) => (
                            <EmailField
                                key={surface}
                                surface={surface}
                                id={`ui-lab-email-surface-${surface}`}
                                name={`ui-lab-email-surface-${surface}`}
                                placeholder={`${surface}@example.com`}
                                aria-label={`Email field ${surface}`}
                            />
                        ))}
                    </div>
                </LabPanel>

                <LabPanel title="FormField">
                    <div className="max-w-md">
                        <FormField
                            id="ui-lab-email-work"
                            label="Email address"
                            hint="Enter your work email address."
                            error="Email is required."
                            required
                        >
                            <EmailField
                                id="ui-lab-email-work"
                                name="ui-lab-email-work"
                                autoComplete="email"
                                placeholder="you@company.com"
                            />
                        </FormField>
                    </div>
                </LabPanel>
            </div>

            <InfoPanel entry={emailFieldCatalog} />
        </div>
    );
}
