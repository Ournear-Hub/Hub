import Button from '@/Components/atoms/Button';
import Input from '@/Components/atoms/Input';
import {
    FormField,
    InputGroup,
    NumberField,
    SearchField,
    URLField,
} from '@/Components/molecules';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { inputGroupCatalog } from '@/ui-lab/catalog';

export default function InputGroupLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Leading addon">
                    <div className="max-w-md space-y-4">
                        <InputGroup leading={<span>https://</span>}>
                            <Input id="ui-lab-ig-url" name="site" placeholder="example.com" aria-label="Website host" />
                        </InputGroup>
                        <InputGroup leading={<span>$</span>}>
                            <Input id="ui-lab-ig-amount" name="amount" inputMode="decimal" placeholder="0.00" aria-label="Amount" />
                        </InputGroup>
                    </div>
                </LabPanel>

                <LabPanel title="Trailing unit">
                    <div className="max-w-xs">
                        <InputGroup trailing={<span>kg</span>}>
                            <NumberField id="ui-lab-ig-weight" name="weight" min={0} step={0.1} placeholder="0" />
                        </InputGroup>
                    </div>
                </LabPanel>

                <LabPanel title="Search + action">
                    <div className="max-w-lg">
                        <InputGroup
                            action={
                                <Button type="button" variant="secondary" size="md">
                                    Search
                                </Button>
                            }
                        >
                            <SearchField id="ui-lab-ig-search" name="q" placeholder="Search candidates…" aria-label="Search" />
                        </InputGroup>
                    </div>
                </LabPanel>

                <LabPanel title="Inside FormField">
                    <div className="max-w-md space-y-4">
                        <FormField id="ui-lab-ig-ff-website" label="Website" hint="Host only — prefix is visual.">
                            <InputGroup leading={<span>https://</span>}>
                                <URLField name="website" placeholder="example.com" />
                            </InputGroup>
                        </FormField>
                        <FormField
                            id="ui-lab-ig-ff-budget"
                            label="Budget"
                            error="Enter a valid amount."
                            required
                        >
                            <InputGroup leading={<span>₹</span>} trailing={<span>/ yr</span>}>
                                <NumberField name="budget" min={0} />
                            </InputGroup>
                        </FormField>
                    </div>
                </LabPanel>

                <LabPanel title="Disabled & responsive">
                    <div className="flex max-w-xl flex-col gap-4 sm:flex-row">
                        <InputGroup leading={<span>+91</span>} className="min-w-0 flex-1">
                            <Input id="ui-lab-ig-phone" name="phone" disabled placeholder="Mobile" aria-label="Mobile" />
                        </InputGroup>
                        <InputGroup
                            className="min-w-0 flex-1"
                            action={
                                <Button type="button" variant="outline" size="md" disabled>
                                    Go
                                </Button>
                            }
                        >
                            <Input id="ui-lab-ig-readonly" name="code" readOnly value="READ-ONLY" aria-label="Code" />
                        </InputGroup>
                    </div>
                </LabPanel>
            </div>

            <InfoPanel entry={inputGroupCatalog} />
        </div>
    );
}
