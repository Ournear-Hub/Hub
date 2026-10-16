import { FileField, FormField } from '@/Components/molecules';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { fileFieldCatalog } from '@/ui-lab/catalog';

export default function FileFieldLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Basic">
                    <div className="max-w-md space-y-4">
                        <FileField id="ui-lab-file-single" name="ui-lab-file-single" aria-label="Single file" />
                        <FileField
                            id="ui-lab-file-multiple"
                            name="ui-lab-file-multiple"
                            multiple
                            aria-label="Multiple files"
                        />
                    </div>
                </LabPanel>

                <LabPanel title="Accept">
                    <div className="max-w-md space-y-4">
                        <FileField
                            id="ui-lab-file-pdf"
                            name="ui-lab-file-pdf"
                            accept=".pdf"
                            aria-label="PDF only"
                        />
                        <FileField
                            id="ui-lab-file-docs"
                            name="ui-lab-file-docs"
                            accept=".pdf,.doc,.docx"
                            aria-label="PDF or Word documents"
                        />
                        <FileField
                            id="ui-lab-file-images"
                            name="ui-lab-file-images"
                            accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                            aria-label="Images"
                        />
                    </div>
                </LabPanel>

                <LabPanel title="States">
                    <div className="max-w-md space-y-4">
                        <FileField id="ui-lab-file-normal" name="ui-lab-file-normal" aria-label="Normal file field" />
                        <FileField
                            id="ui-lab-file-disabled"
                            name="ui-lab-file-disabled"
                            disabled
                            aria-label="Disabled file field"
                        />
                        <FileField
                            id="ui-lab-file-required"
                            name="ui-lab-file-required"
                            required
                            aria-label="Required file field"
                        />
                        <FileField id="ui-lab-file-error" name="ui-lab-file-error" error aria-label="File field error" />
                    </div>
                </LabPanel>

                <LabPanel title="Surfaces">
                    <div className="max-w-md grid gap-3 sm:grid-cols-2">
                        {(['public', 'account', 'admin', 'corporate'] as const).map((surface) => (
                            <FileField
                                key={surface}
                                surface={surface}
                                id={`ui-lab-file-surface-${surface}`}
                                name={`ui-lab-file-surface-${surface}`}
                                aria-label={`File field ${surface}`}
                            />
                        ))}
                    </div>
                </LabPanel>

                <LabPanel title="FormField">
                    <div className="max-w-md">
                        <FormField
                            id="ui-lab-file-resume"
                            label="Resume"
                            hint="PDF or DOCX, max 5 MB (hint only — no validation in FileField)."
                            error="Please upload your resume."
                            required
                        >
                            <FileField
                                id="ui-lab-file-resume"
                                name="ui-lab-file-resume"
                                accept=".pdf,.doc,.docx,application/pdf"
                            />
                        </FormField>
                    </div>
                </LabPanel>
            </div>

            <InfoPanel entry={fileFieldCatalog} />
        </div>
    );
}
