import { Combobox, FormField, type ComboboxOption } from '@/Components/molecules';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { comboboxCatalog } from '@/ui-lab/catalog';
import { useMemo, useState } from 'react';

const industries: ComboboxOption[] = [
    { value: 'it-services', label: 'IT Services' },
    { value: 'healthcare', label: 'Healthcare' },
    { value: 'manufacturing', label: 'Manufacturing' },
    { value: 'retail', label: 'Retail' },
    { value: 'finance', label: 'Finance & Banking' },
    { value: 'education', label: 'Education' },
    { value: 'legacy', label: 'Legacy sector (disabled example)', disabled: true },
];

const jobCategories: ComboboxOption[] = [
    { value: 'engineering', label: 'Engineering' },
    { value: 'sales', label: 'Sales' },
    { value: 'operations', label: 'Operations' },
    { value: 'hr', label: 'Human Resources' },
    { value: 'design', label: 'Product Design' },
];

const locations: ComboboxOption[] = [
    { value: 'hyderabad', label: 'Hyderabad' },
    { value: 'jabalpur', label: 'Jabalpur' },
    { value: 'bengaluru', label: 'Bengaluru' },
    { value: 'mumbai', label: 'Mumbai' },
    { value: 'delhi', label: 'Delhi NCR' },
    { value: 'remote', label: 'Remote — India' },
];

export default function ComboboxLabSection() {
    const [industry, setIndustry] = useState('it-services');
    const [category, setCategory] = useState('');
    const [location, setLocation] = useState('hyderabad');
    const [formIndustry, setFormIndustry] = useState('');

    const noMatchOptions = useMemo(
        () => [{ value: 'alpha', label: 'Alpha' }, { value: 'beta', label: 'Beta' }],
        [],
    );

    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Basic single-select">
                    <div className="max-w-md">
                        <label htmlFor="ui-lab-cb-industry" className="mb-1 block text-sm font-medium text-ink">
                            Industry
                        </label>
                        <Combobox
                            id="ui-lab-cb-industry"
                            options={industries}
                            value={industry}
                            onChange={setIndustry}
                            placeholder="Select industry"
                        />
                        <p className="mt-1 text-xs text-muted">Selected value: {industry || '(empty)'}</p>
                    </div>
                </LabPanel>

                <LabPanel title="Search / filter">
                    <div className="max-w-md">
                        <label htmlFor="ui-lab-cb-location" className="mb-1 block text-sm font-medium text-ink">
                            Location
                        </label>
                        <Combobox
                            id="ui-lab-cb-location"
                            options={locations}
                            value={location}
                            onChange={setLocation}
                            placeholder="Type to filter cities"
                        />
                        <p className="mt-1 text-xs text-muted">Case-insensitive label match while open.</p>
                    </div>
                </LabPanel>

                <LabPanel title="Disabled option">
                    <div className="max-w-md">
                        <Combobox
                            id="ui-lab-cb-disabled-opt"
                            options={industries}
                            value={industry}
                            onChange={setIndustry}
                            placeholder="Try keyboard past disabled row"
                            aria-label="Industry with disabled option"
                        />
                    </div>
                </LabPanel>

                <LabPanel title="No results">
                    <div className="max-w-md">
                        <Combobox
                            id="ui-lab-cb-empty"
                            options={noMatchOptions}
                            value=""
                            onChange={() => undefined}
                            placeholder="Type zzx — no matches"
                            aria-label="Combobox empty filter demo"
                        />
                    </div>
                </LabPanel>

                <LabPanel title="Inside FormField">
                    <div className="max-w-md">
                        <FormField
                            id="ui-lab-cb-ff-industry"
                            label="Industry"
                            hint="Select your primary industry"
                            error={formIndustry ? undefined : 'Industry is required.'}
                            required
                        >
                            <Combobox
                                options={industries}
                                value={formIndustry}
                                onChange={setFormIndustry}
                                placeholder="Search industries"
                            />
                        </FormField>
                    </div>
                </LabPanel>

                <LabPanel title="Keyboard & focus">
                    <div className="max-w-md space-y-4">
                        <Combobox
                            id="ui-lab-cb-category"
                            options={jobCategories}
                            value={category}
                            onChange={setCategory}
                            placeholder="Job category"
                            aria-label="Job category"
                        />
                        <ul className="list-inside list-disc space-y-1 text-sm text-muted">
                            <li>Arrow Down / Up — open and move highlight (skips disabled)</li>
                            <li>Enter — select highlighted option</li>
                            <li>Escape — close list; selection unchanged</li>
                            <li>Home / End — first / last enabled option</li>
                            <li>Tab — normal tab order; closes list</li>
                        </ul>
                    </div>
                </LabPanel>

                <LabPanel title="Disabled control">
                    <div className="max-w-md">
                        <Combobox
                            id="ui-lab-cb-disabled"
                            options={jobCategories}
                            value="engineering"
                            onChange={() => undefined}
                            disabled
                            aria-label="Disabled combobox"
                        />
                    </div>
                </LabPanel>
            </div>

            <InfoPanel entry={comboboxCatalog} />
        </div>
    );
}
