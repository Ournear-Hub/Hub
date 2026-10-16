import { useState } from 'react';
import { FormField, SearchField } from '@/Components/molecules';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { searchFieldCatalog } from '@/ui-lab/catalog';

export default function SearchFieldLabSection() {
    const [query, setQuery] = useState('Product manager');

    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Basic">
                    <div className="max-w-md space-y-4">
                        <SearchField name="ui-lab-search-empty" placeholder="Search jobs" aria-label="Search jobs" />
                        <SearchField
                            name="ui-lab-search-populated"
                            value={query}
                            onChange={(event) => setQuery(event.currentTarget.value)}
                            placeholder="Search jobs"
                            aria-label="Search jobs"
                        />
                    </div>
                </LabPanel>

                <LabPanel title="Surfaces">
                    <div className="max-w-md grid gap-3 sm:grid-cols-2">
                        {(['public', 'account', 'admin', 'corporate'] as const).map((surface) => (
                            <SearchField
                                key={surface}
                                surface={surface}
                                name={`ui-lab-search-surface-${surface}`}
                                placeholder={`${surface} surface`}
                                aria-label={`Search on ${surface} surface`}
                            />
                        ))}
                    </div>
                </LabPanel>

                <LabPanel title="Disabled">
                    <div className="max-w-md">
                        <SearchField
                            name="ui-lab-search-disabled"
                            placeholder="Search"
                            disabled
                            defaultValue="Disabled query"
                            aria-label="Search disabled example"
                        />
                    </div>
                </LabPanel>

                <LabPanel title="Accessibility">
                    <div className="max-w-md">
                        <FormField id="ui-lab-job-search" label="Search jobs" hint="Search by title or keyword.">
                            <SearchField
                                id="ui-lab-job-search"
                                name="ui-lab-job-search"
                                placeholder="Job title or keyword"
                            />
                        </FormField>
                    </div>
                </LabPanel>
            </div>

            <InfoPanel entry={searchFieldCatalog} />
        </div>
    );
}
