import Input from '@/Components/atoms/Input';
import { FormField } from '@/Components/molecules';
import Accordion, {
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/Components/molecules/Accordion';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { accordionCatalog } from '@/ui-lab/catalog';
import { useState } from 'react';

export default function AccordionLabSection() {
    const [controlledOpen, setControlledOpen] = useState<string | string[]>('faq-1');

    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Basic (single open)">
                    <Accordion type="single" defaultValue="profile">
                        <AccordionItem value="profile">
                            <AccordionTrigger>Candidate profile</AccordionTrigger>
                            <AccordionContent>
                                <p className="text-sm text-muted">Summary, contact details, and verification status.</p>
                            </AccordionContent>
                        </AccordionItem>
                        <AccordionItem value="professional">
                            <AccordionTrigger>Professional information</AccordionTrigger>
                            <AccordionContent>
                                <p className="text-sm text-muted">Experience, skills, and preferences.</p>
                            </AccordionContent>
                        </AccordionItem>
                        <AccordionItem value="documents">
                            <AccordionTrigger>Documents</AccordionTrigger>
                            <AccordionContent>
                                <p className="text-sm text-muted">Resume and supporting files.</p>
                            </AccordionContent>
                        </AccordionItem>
                    </Accordion>
                </LabPanel>

                <LabPanel title="Multiple open">
                    <Accordion type="multiple" defaultValue={['job', 'employer']}>
                        <AccordionItem value="job">
                            <AccordionTrigger>Job details</AccordionTrigger>
                            <AccordionContent>
                                Role title, location, compensation band, and employment type.
                            </AccordionContent>
                        </AccordionItem>
                        <AccordionItem value="employer">
                            <AccordionTrigger>Employer information</AccordionTrigger>
                            <AccordionContent>
                                Company profile, hiring contact, and locations.
                            </AccordionContent>
                        </AccordionItem>
                    </Accordion>
                </LabPanel>

                <LabPanel title="FAQ">
                    <Accordion type="single" collapsible defaultValue="q1">
                        <AccordionItem value="q1">
                            <AccordionTrigger>How do applications work?</AccordionTrigger>
                            <AccordionContent>
                                Candidates apply once per job; recruiters review in the admin workspace.
                            </AccordionContent>
                        </AccordionItem>
                        <AccordionItem value="q2">
                            <AccordionTrigger>Can employers edit a posting?</AccordionTrigger>
                            <AccordionContent>
                                Yes — authorized employer users can update open roles from their account.
                            </AccordionContent>
                        </AccordionItem>
                    </Accordion>
                </LabPanel>

                <LabPanel title="Disabled item">
                    <Accordion type="single" defaultValue="open">
                        <AccordionItem value="open">
                            <AccordionTrigger>Available section</AccordionTrigger>
                            <AccordionContent>This section can expand.</AccordionContent>
                        </AccordionItem>
                        <AccordionItem value="locked" disabled>
                            <AccordionTrigger>Locked section</AccordionTrigger>
                            <AccordionContent>Should not open.</AccordionContent>
                        </AccordionItem>
                    </Accordion>
                </LabPanel>

                <LabPanel title="Long content">
                    <Accordion type="single" defaultValue="long">
                        <AccordionItem value="long">
                            <AccordionTrigger>Detailed job description</AccordionTrigger>
                            <AccordionContent>
                                <div className="max-h-48 space-y-2 overflow-y-auto text-sm text-muted">
                                    {Array.from({ length: 12 }, (_, index) => (
                                        <p key={index}>
                                            Paragraph {index + 1}: responsibilities, requirements, and workplace
                                            expectations for this role.
                                        </p>
                                    ))}
                                </div>
                            </AccordionContent>
                        </AccordionItem>
                    </Accordion>
                </LabPanel>

                <LabPanel title="Nested canonical components">
                    <Accordion type="single" defaultValue="form">
                        <AccordionItem value="form">
                            <AccordionTrigger>Quick edit</AccordionTrigger>
                            <AccordionContent>
                                <FormField id="ui-lab-acc-name" label="Display name">
                                    <Input id="ui-lab-acc-name" name="display_name" defaultValue="Sample candidate" />
                                </FormField>
                            </AccordionContent>
                        </AccordionItem>
                    </Accordion>
                </LabPanel>

                <LabPanel title="Controlled">
                    <Accordion
                        type="single"
                        value={controlledOpen}
                        onValueChange={setControlledOpen}
                        collapsible
                    >
                        <AccordionItem value="faq-1">
                            <AccordionTrigger>Controlled item A</AccordionTrigger>
                            <AccordionContent>Caller owns open state via value/onValueChange.</AccordionContent>
                        </AccordionItem>
                        <AccordionItem value="faq-2">
                            <AccordionTrigger>Controlled item B</AccordionTrigger>
                            <AccordionContent>Try toggling — state updates in React.</AccordionContent>
                        </AccordionItem>
                    </Accordion>
                    <p className="mt-2 text-xs text-muted">Open: {String(controlledOpen) || '(none)'}</p>
                </LabPanel>

                <LabPanel title="Keyboard & accessibility">
                    <ul className="max-w-lg list-inside list-disc space-y-1 text-sm text-muted">
                        <li>Native &lt;details&gt; / &lt;summary&gt; with Enter/Space on summary</li>
                        <li>aria-expanded and aria-controls on trigger; content id for panel</li>
                        <li>Closed panels are not focusable (native disclosure behavior)</li>
                        <li>No tablist, dialog, or navigation roles</li>
                    </ul>
                </LabPanel>
            </div>

            <InfoPanel entry={accordionCatalog} />
        </div>
    );
}
