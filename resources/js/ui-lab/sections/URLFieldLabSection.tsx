import { FormField, URLField } from '@/Components/molecules';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { urlFieldCatalog } from '@/ui-lab/catalog';

export default function URLFieldLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Basic">
                    <div className="max-w-md space-y-4">
                        <URLField
                            id="ui-lab-url-empty"
                            name="ui-lab-url-empty"
                            placeholder="https://example.com"
                            aria-label="URL"
                        />
                        <URLField
                            id="ui-lab-url-value"
                            name="ui-lab-url-value"
                            defaultValue="https://ournear.com"
                            aria-label="URL with value"
                        />
                    </div>
                </LabPanel>

                <LabPanel title="Native features" description="autoComplete, inputMode, maxLength, and pattern are caller-controlled.">
                    <div className="max-w-md space-y-4">
                        <URLField
                            id="ui-lab-url-autocomplete"
                            name="ui-lab-url-autocomplete"
                            autoComplete="url"
                            placeholder="https://example.com"
                            aria-label="URL with autoComplete url"
                        />
                        <URLField
                            id="ui-lab-url-inputmode"
                            name="ui-lab-url-inputmode"
                            inputMode="url"
                            placeholder="inputMode url"
                            aria-label="URL with inputMode url"
                        />
                        <URLField
                            id="ui-lab-url-maxlength"
                            name="ui-lab-url-maxlength"
                            maxLength={2048}
                            placeholder="maxLength 2048"
                            aria-label="URL max length"
                        />
                        <URLField
                            id="ui-lab-url-pattern"
                            name="ui-lab-url-pattern"
                            pattern="https://.*"
                            title="Must start with https:// (native pattern hint only)"
                            placeholder="https://"
                            aria-label="URL with pattern"
                        />
                    </div>
                </LabPanel>

                <LabPanel title="States">
                    <div className="max-w-md space-y-4">
                        <URLField id="ui-lab-url-normal" name="ui-lab-url-normal" aria-label="Normal URL" />
                        <URLField
                            id="ui-lab-url-disabled"
                            name="ui-lab-url-disabled"
                            disabled
                            defaultValue="https://locked.example"
                            aria-label="Disabled URL"
                        />
                        <URLField
                            id="ui-lab-url-required"
                            name="ui-lab-url-required"
                            required
                            aria-label="Required URL"
                        />
                        <URLField
                            id="ui-lab-url-readonly"
                            name="ui-lab-url-readonly"
                            readOnly
                            defaultValue="https://readonly.example"
                            aria-label="Readonly URL"
                        />
                        <URLField id="ui-lab-url-error" name="ui-lab-url-error" error aria-label="URL error" />
                    </div>
                </LabPanel>

                <LabPanel title="Surfaces">
                    <div className="max-w-md grid gap-3 sm:grid-cols-2">
                        {(['public', 'account', 'admin', 'corporate'] as const).map((surface) => (
                            <URLField
                                key={surface}
                                surface={surface}
                                id={`ui-lab-url-surface-${surface}`}
                                name={`ui-lab-url-surface-${surface}`}
                                placeholder={`https://${surface}.example`}
                                aria-label={`URL field ${surface}`}
                            />
                        ))}
                    </div>
                </LabPanel>

                <LabPanel title="FormField">
                    <div className="max-w-md">
                        <FormField
                            id="ui-lab-url-website"
                            label="Website"
                            hint="Enter the full website URL."
                            error="Website URL is required."
                            required
                        >
                            <URLField
                                id="ui-lab-url-website"
                                name="ui-lab-url-website"
                                placeholder="https://example.com"
                            />
                        </FormField>
                    </div>
                </LabPanel>
            </div>

            <InfoPanel entry={urlFieldCatalog} />
        </div>
    );
}
