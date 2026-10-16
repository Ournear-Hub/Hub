import { FormField, MultiSelect, type MultiSelectOption } from '@/Components/molecules';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { multiSelectCatalog } from '@/ui-lab/catalog';
import { useMemo, useState } from 'react';

const skills: MultiSelectOption[] = [
    { value: 'react', label: 'React' },
    { value: 'laravel', label: 'Laravel' },
    { value: 'typescript', label: 'TypeScript' },
    { value: 'tailwind', label: 'Tailwind CSS' },
    { value: 'mysql', label: 'MySQL' },
    { value: 'legacy-stack', label: 'Legacy stack (disabled)', disabled: true },
];

const industries: MultiSelectOption[] = [
    { value: 'mining', label: 'Mining' },
    { value: 'construction', label: 'Construction' },
    { value: 'infrastructure', label: 'Infrastructure' },
    { value: 'healthcare', label: 'Healthcare' },
    { value: 'it', label: 'IT Services' },
];

const jobCategories: MultiSelectOption[] = [
    { value: 'engineering', label: 'Engineering' },
    { value: 'operations', label: 'Operations' },
    { value: 'sales', label: 'Sales & Business Development' },
    { value: 'hr', label: 'Human Resources' },
];

const locations: MultiSelectOption[] = [
    { value: 'hyderabad', label: 'Hyderabad' },
    { value: 'jabalpur', label: 'Jabalpur' },
    { value: 'bengaluru', label: 'Bengaluru' },
    { value: 'mumbai', label: 'Mumbai' },
    { value: 'delhi', label: 'Delhi NCR' },
    { value: 'remote', label: 'Remote — India' },
];

export default function MultiSelectLabSection() {
    const [selectedSkills, setSelectedSkills] = useState<string[]>(['react', 'typescript']);
    const [selectedIndustries, setSelectedIndustries] = useState<string[]>(['mining', 'construction', 'infrastructure']);
    const [formSkills, setFormSkills] = useState<string[]>([]);
    const [selectedLocations, setSelectedLocations] = useState<string[]>(['hyderabad']);
    const [categories, setCategories] = useState<string[]>(['engineering', 'operations', 'sales', 'hr']);

    const narrowOptions = useMemo(
        () => [
            { value: 'alpha', label: 'Alpha' },
            { value: 'beta', label: 'Beta' },
        ],
        [],
    );

    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Basic multi-select">
                    <div className="max-w-md">
                        <label htmlFor="ui-lab-ms-skills" className="mb-1 block text-sm font-medium text-ink">
                            Skills
                        </label>
                        <MultiSelect
                            id="ui-lab-ms-skills"
                            options={skills}
                            value={selectedSkills}
                            onChange={setSelectedSkills}
                            placeholder="Select skills"
                        />
                        <p className="mt-1 text-xs text-muted">Selected: {selectedSkills.join(', ') || '(none)'}</p>
                    </div>
                </LabPanel>

                <LabPanel title="Search / filter">
                    <div className="max-w-md">
                        <MultiSelect
                            id="ui-lab-ms-locations"
                            options={locations}
                            value={selectedLocations}
                            onChange={setSelectedLocations}
                            placeholder="Filter locations"
                            aria-label="Locations filter demo"
                        />
                        <p className="mt-1 text-xs text-muted">Type to filter; Enter/Space toggles highlight.</p>
                    </div>
                </LabPanel>

                <LabPanel title="Multiple chips">
                    <div className="max-w-lg">
                        <MultiSelect
                            id="ui-lab-ms-industries"
                            options={industries}
                            value={selectedIndustries}
                            onChange={setSelectedIndustries}
                            placeholder="Industries"
                            aria-label="Industries"
                        />
                    </div>
                </LabPanel>

                <LabPanel title="Disabled option">
                    <div className="max-w-md">
                        <MultiSelect
                            id="ui-lab-ms-disabled-opt"
                            options={skills}
                            value={selectedSkills}
                            onChange={setSelectedSkills}
                            placeholder="Skills"
                            aria-label="Skills with disabled option"
                        />
                    </div>
                </LabPanel>

                <LabPanel title="No results">
                    <div className="max-w-md">
                        <MultiSelect
                            id="ui-lab-ms-empty"
                            options={narrowOptions}
                            value={[]}
                            onChange={() => undefined}
                            placeholder="Type zzx"
                            aria-label="Empty filter demo"
                        />
                    </div>
                </LabPanel>

                <LabPanel title="Inside FormField">
                    <div className="max-w-md">
                        <FormField
                            id="ui-lab-ms-ff-skills"
                            label="Skills"
                            hint="Select all applicable skills"
                            error={formSkills.length > 0 ? undefined : 'Pick at least one skill.'}
                            required
                        >
                            <MultiSelect
                                options={skills}
                                value={formSkills}
                                onChange={setFormSkills}
                                placeholder="Search skills"
                                name="skills"
                            />
                        </FormField>
                    </div>
                </LabPanel>

                <LabPanel title="Disabled control">
                    <div className="max-w-md">
                        <MultiSelect
                            id="ui-lab-ms-disabled"
                            options={jobCategories}
                            value={['engineering', 'operations']}
                            onChange={() => undefined}
                            disabled
                            aria-label="Disabled multi-select"
                        />
                    </div>
                </LabPanel>

                <LabPanel title="Long labels / many selections">
                    <div className="max-w-xl">
                        <MultiSelect
                            id="ui-lab-ms-many"
                            options={jobCategories}
                            value={categories}
                            onChange={setCategories}
                            placeholder="Job categories"
                            aria-label="Many selections"
                        />
                    </div>
                </LabPanel>

                <LabPanel title="Keyboard">
                    <ul className="max-w-md list-inside list-disc space-y-1 text-sm text-muted">
                        <li>Arrow Down / Up — open and move highlight (skips disabled)</li>
                        <li>Enter or Space — toggle highlighted option; popup stays open</li>
                        <li>Escape — close without clearing selections</li>
                        <li>Chip remove buttons — labeled “Remove …”; Tab reachable</li>
                    </ul>
                </LabPanel>
            </div>

            <InfoPanel entry={multiSelectCatalog} />
        </div>
    );
}
