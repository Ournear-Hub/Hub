import Button from '@/Components/atoms/Button';
import IconButton from '@/Components/atoms/IconButton';
import Select from '@/Components/atoms/Select';
import { Combobox, FormField } from '@/Components/molecules';
import Popover, { PopoverContent, PopoverTrigger } from '@/Components/molecules/Popover';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { popoverCatalog } from '@/ui-lab/catalog';
import { useState } from 'react';

const statusOptions = [
    { value: 'active', label: 'Active' },
    { value: 'archived', label: 'Archived' },
];

const roleOptions = [
    { value: 'recruiter', label: 'Recruiter' },
    { value: 'employer', label: 'Employer' },
];

export default function PopoverLabSection() {
    const [controlledOpen, setControlledOpen] = useState(false);
    const [status, setStatus] = useState('active');
    const [role, setRole] = useState('recruiter');

    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Basic filters">
                    <Popover>
                        <PopoverTrigger>
                            <Button type="button" variant="secondary">
                                Filters
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent>
                            <p className="mb-3 font-semibold text-ink">Application filters</p>
                            <FormField id="ui-lab-pop-status" label="Status">
                                <Select
                                    id="ui-lab-pop-status"
                                    name="status"
                                    value={status}
                                    onChange={(event) => setStatus(event.target.value)}
                                >
                                    {statusOptions.map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))}
                                </Select>
                            </FormField>
                            <Button type="button" className="mt-3 w-full" variant="primary">
                                Apply
                            </Button>
                        </PopoverContent>
                    </Popover>
                </LabPanel>

                <LabPanel title="IconButton — more actions">
                    <Popover>
                        <PopoverTrigger>
                            <IconButton icon="more-horizontal" label="More actions" variant="ghost" />
                        </PopoverTrigger>
                        <PopoverContent align="end">
                            <div className="flex flex-col gap-2">
                                <Button type="button" variant="ghost" className="justify-start">
                                    View details
                                </Button>
                                <Button type="button" variant="ghost" className="justify-start">
                                    Copy link
                                </Button>
                                <Button type="button" variant="ghost" className="justify-start text-danger">
                                    Delete
                                </Button>
                            </div>
                        </PopoverContent>
                    </Popover>
                </LabPanel>

                <LabPanel title="Combobox inside popover">
                    <Popover>
                        <PopoverTrigger>
                            <Button type="button" variant="outline">
                                Quick settings
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent>
                            <FormField id="ui-lab-pop-role" label="Role context">
                                <Combobox
                                    id="ui-lab-pop-role"
                                    options={roleOptions}
                                    value={role}
                                    onChange={setRole}
                                    placeholder="Select role"
                                />
                            </FormField>
                        </PopoverContent>
                    </Popover>
                </LabPanel>

                <LabPanel title="Controlled">
                    <Popover open={controlledOpen} onOpenChange={setControlledOpen}>
                        <PopoverTrigger>
                            <Button type="button">Candidate details</Button>
                        </PopoverTrigger>
                        <PopoverContent>
                            <p className="text-muted">Controlled via open/onOpenChange.</p>
                        </PopoverContent>
                    </Popover>
                    <p className="mt-2 text-xs text-muted">Open: {String(controlledOpen)}</p>
                </LabPanel>

                <LabPanel title="Placement">
                    <div className="flex flex-wrap items-center gap-6 py-6">
                        {(['top', 'right', 'bottom', 'left'] as const).map((side) => (
                            <Popover key={side}>
                                <PopoverTrigger>
                                    <Button type="button" variant="outline">
                                        {side}
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent side={side}>
                                    <p className="text-muted">side={side}</p>
                                </PopoverContent>
                            </Popover>
                        ))}
                    </div>
                </LabPanel>

                <LabPanel title="Keyboard & dismissal">
                    <ul className="max-w-lg list-inside list-disc space-y-1 text-sm text-muted">
                        <li>Click trigger toggles; first focusable in content receives focus on open</li>
                        <li>Escape closes and returns focus to trigger when focus moved into content</li>
                        <li>Pointer down outside trigger/content closes</li>
                        <li>No focus trap; not a modal or menu</li>
                    </ul>
                </LabPanel>
            </div>

            <InfoPanel entry={popoverCatalog} />
        </div>
    );
}
