import { Label, Select } from '@/Components/atoms';
import { errorText } from '@/design-system/classNames';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { selectCatalog } from '@/ui-lab/catalog';

export default function SelectLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Basic">
                    <div className="max-w-md space-y-4">
                        <div>
                            <Label htmlFor="ui-lab-select-empty">Placeholder option</Label>
                            <Select id="ui-lab-select-empty" name="employment_type" defaultValue="">
                                <option value="">Select employment type</option>
                                <option value="full_time">Full time</option>
                                <option value="contract">Contract</option>
                            </Select>
                        </div>
                        <div>
                            <Label htmlFor="ui-lab-select-selected">Selected value</Label>
                            <Select id="ui-lab-select-selected" name="status" defaultValue="active">
                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                                <option value="paused">Paused</option>
                            </Select>
                        </div>
                        <div>
                            <Label htmlFor="ui-lab-select-disabled">Disabled select</Label>
                            <Select id="ui-lab-select-disabled" disabled defaultValue="locked">
                                <option value="locked">Locked</option>
                            </Select>
                        </div>
                    </div>
                </LabPanel>

                <LabPanel title="States">
                    <div className="max-w-md space-y-4">
                        <div>
                            <Label htmlFor="ui-lab-select-normal">Normal</Label>
                            <Select id="ui-lab-select-normal" defaultValue="">
                                <option value="">Choose…</option>
                                <option value="a">Option A</option>
                            </Select>
                        </div>
                        <div>
                            <Label htmlFor="ui-lab-select-error">Error</Label>
                            <Select
                                id="ui-lab-select-error"
                                error
                                defaultValue=""
                                aria-describedby="ui-lab-select-error-msg"
                            >
                                <option value="">Required field</option>
                                <option value="ok">OK</option>
                            </Select>
                            <p id="ui-lab-select-error-msg" className={errorText}>
                                Selection is required. (Not part of the Select atom.)
                            </p>
                        </div>
                    </div>
                </LabPanel>

                <LabPanel title="Surfaces">
                    <div className="max-w-md space-y-4">
                        <div>
                            <Label htmlFor="ui-lab-select-public">Public</Label>
                            <Select id="ui-lab-select-public" surface="public" defaultValue="one">
                                <option value="one">One</option>
                            </Select>
                        </div>
                        <div>
                            <Label htmlFor="ui-lab-select-account">Account</Label>
                            <Select id="ui-lab-select-account" surface="account" defaultValue="one">
                                <option value="one">One</option>
                            </Select>
                        </div>
                        <div>
                            <Label htmlFor="ui-lab-select-admin">Admin</Label>
                            <Select id="ui-lab-select-admin" surface="admin" defaultValue="one">
                                <option value="one">One</option>
                            </Select>
                        </div>
                        <div>
                            <Label htmlFor="ui-lab-select-corporate">Corporate</Label>
                            <Select id="ui-lab-select-corporate" surface="corporate" defaultValue="one">
                                <option value="one">One</option>
                            </Select>
                        </div>
                    </div>
                </LabPanel>

                <LabPanel title="Native behavior" description="Disabled options and native multiple selection.">
                    <div className="max-w-md space-y-4">
                        <div>
                            <Label htmlFor="ui-lab-select-options">Disabled option</Label>
                            <Select id="ui-lab-select-options" defaultValue="b">
                                <option value="a">Available</option>
                                <option value="b">Selected</option>
                                <option value="c" disabled>
                                    Unavailable (disabled option)
                                </option>
                            </Select>
                        </div>
                        <div>
                            <Label htmlFor="ui-lab-select-multiple">Multiple (native)</Label>
                            <Select id="ui-lab-select-multiple" multiple size={4} defaultValue={['red', 'blue']}>
                                <option value="red">Red</option>
                                <option value="green">Green</option>
                                <option value="blue">Blue</option>
                                <option value="yellow">Yellow</option>
                            </Select>
                        </div>
                    </div>
                </LabPanel>

                <LabPanel title="Accessibility">
                    <div className="max-w-md">
                        <Label htmlFor="ui-lab-select-location" required>
                            Location
                        </Label>
                        <Select id="ui-lab-select-location" name="location" aria-describedby="ui-lab-select-location-hint" defaultValue="">
                            <option value="">Select location</option>
                            <option value="remote">Remote</option>
                            <option value="hybrid">Hybrid</option>
                            <option value="onsite">On-site</option>
                        </Select>
                        <p id="ui-lab-select-location-hint" className="mt-1 text-xs text-muted">
                            Used for job filtering preferences.
                        </p>
                    </div>
                </LabPanel>
            </div>

            <InfoPanel entry={selectCatalog} />
        </div>
    );
}
