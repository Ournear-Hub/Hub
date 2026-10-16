import { useState } from 'react';
import { FormField, PasswordField } from '@/Components/molecules';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { passwordFieldCatalog } from '@/ui-lab/catalog';

export default function PasswordFieldLabSection() {
    const [password, setPassword] = useState('demo-secret-value');

    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Basic">
                    <div className="max-w-md space-y-4">
                        <PasswordField
                            id="ui-lab-pw-empty"
                            name="ui-lab-pw-empty"
                            placeholder="Password"
                            autoComplete="new-password"
                            aria-label="Password"
                        />
                        <PasswordField
                            id="ui-lab-pw-populated"
                            name="ui-lab-pw-populated"
                            value={password}
                            onChange={(event) => setPassword(event.currentTarget.value)}
                            autoComplete="new-password"
                            aria-label="Password populated"
                        />
                    </div>
                </LabPanel>

                <LabPanel title="Visibility" description="Toggle uses Show password / Hide password labels on IconButton.">
                    <div className="max-w-md">
                        <PasswordField
                            id="ui-lab-pw-visibility"
                            name="ui-lab-pw-visibility"
                            defaultValue="Secret value"
                            autoComplete="off"
                            aria-label="Password visibility demo"
                        />
                    </div>
                </LabPanel>

                <LabPanel title="States">
                    <div className="max-w-md space-y-4">
                        <PasswordField
                            id="ui-lab-pw-normal"
                            name="ui-lab-pw-normal"
                            defaultValue="Normal"
                            aria-label="Normal password"
                        />
                        <PasswordField
                            id="ui-lab-pw-disabled"
                            name="ui-lab-pw-disabled"
                            defaultValue="Disabled"
                            disabled
                            aria-label="Disabled password"
                        />
                        <PasswordField
                            id="ui-lab-pw-error"
                            name="ui-lab-pw-error"
                            error
                            defaultValue="Error"
                            aria-label="Password error"
                        />
                        <PasswordField
                            id="ui-lab-pw-no-toggle"
                            name="ui-lab-pw-no-toggle"
                            showToggle={false}
                            defaultValue="No toggle"
                            aria-label="Password without toggle"
                        />
                    </div>
                </LabPanel>

                <LabPanel title="Surfaces">
                    <div className="max-w-md grid gap-3 sm:grid-cols-2">
                        {(['public', 'account', 'admin', 'corporate'] as const).map((surface) => (
                            <PasswordField
                                key={surface}
                                surface={surface}
                                id={`ui-lab-pw-surface-${surface}`}
                                name={`ui-lab-pw-surface-${surface}`}
                                placeholder={surface}
                                aria-label={`Password ${surface}`}
                            />
                        ))}
                    </div>
                </LabPanel>

                <LabPanel title="FormField">
                    <div className="max-w-md">
                        <FormField
                            id="ui-lab-pw-form"
                            label="New password"
                            error="Password must be at least 8 characters."
                            required
                        >
                            <PasswordField
                                id="ui-lab-pw-form"
                                name="ui-lab-pw-form"
                                autoComplete="new-password"
                            />
                        </FormField>
                    </div>
                </LabPanel>
            </div>

            <InfoPanel entry={passwordFieldCatalog} />
        </div>
    );
}
