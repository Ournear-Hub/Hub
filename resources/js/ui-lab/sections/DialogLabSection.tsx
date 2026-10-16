import Button from '@/Components/atoms/Button';
import Dialog from '@/Components/molecules/Dialog';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { dialogCatalog } from '@/ui-lab/catalog';
import { useState } from 'react';

export default function DialogLabSection() {
    const [basicOpen, setBasicOpen] = useState(false);
    const [confirmOpen, setConfirmOpen] = useState(false);

    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Basic dialog">
                    <Button type="button" surface="admin" onClick={() => setBasicOpen(true)}>
                        Open dialog
                    </Button>
                    <Dialog
                        open={basicOpen}
                        onClose={() => setBasicOpen(false)}
                        title="Review submission"
                        description="This dialog demonstrates title, description, body, and footer actions."
                        footer={
                            <>
                                <Button type="button" surface="admin" variant="secondary" onClick={() => setBasicOpen(false)}>
                                    Cancel
                                </Button>
                                <Button type="button" surface="admin" onClick={() => setBasicOpen(false)}>
                                    Continue
                                </Button>
                            </>
                        }
                    >
                        <p className="text-left text-ink">Caller-owned content — forms, copy, or lists belong here.</p>
                    </Dialog>
                </LabPanel>

                <LabPanel title="Confirmation pattern (caller-owned)">
                    <Button type="button" surface="admin" variant="destructive" onClick={() => setConfirmOpen(true)}>
                        Delete draft
                    </Button>
                    <Dialog
                        open={confirmOpen}
                        onClose={() => setConfirmOpen(false)}
                        title="Delete draft?"
                        description="This removes the draft from your workspace. Published records are not affected."
                        footer={
                            <>
                                <Button type="button" surface="admin" variant="secondary" onClick={() => setConfirmOpen(false)}>
                                    Cancel
                                </Button>
                                <Button type="button" surface="admin" variant="destructive" onClick={() => setConfirmOpen(false)}>
                                    Delete
                                </Button>
                            </>
                        }
                    >
                        {null}
                    </Dialog>
                    <p className="mt-3 text-sm text-muted">
                        V2 <code className="text-ink">ConfirmDialog</code> wraps <code className="text-ink">Modal</code> with async
                        confirm busy state — that workflow stays caller-owned; no separate ConfirmDialog molecule.
                    </p>
                </LabPanel>

                <LabPanel title="Popover / Drawer boundaries">
                    <ul className="max-w-lg list-inside list-disc space-y-1 text-sm text-muted">
                        <li>
                            <strong className="font-medium text-ink">Popover</strong> — non-blocking contextual panel; no{' '}
                            <code className="text-ink">aria-modal</code>.
                        </li>
                        <li>
                            <strong className="font-medium text-ink">Drawer</strong> — V2 slide-over entity workspace (
                            <code className="text-ink">Admin/ui/Drawer</code>); not canonicalized in PR-5H.
                        </li>
                        <li>
                            Mobile nav sheets (<code className="text-ink">PublicMobileMenu</code>, account “More”) — page-local
                            dialog markup, not the admin modal shell.
                        </li>
                    </ul>
                </LabPanel>

                <LabPanel title="V2 evidence (read-only)">
                    <ul className="max-w-lg list-inside list-disc space-y-1 text-sm text-muted">
                        <li>
                            <code className="text-ink">Components/Admin/ui/Modal.tsx</code> — shared centered modal (~18{' '}
                            <code className="text-ink">&lt;Modal</code> usages).
                        </li>
                        <li>
                            <code className="text-ink">ConfirmDialog.tsx</code> — confirmation use case on top of Modal (~9 admin
                            flows).
                        </li>
                        <li>
                            <code className="text-ink">Drawer.tsx</code> — dominant pattern for admin index detail (~24 pages).
                        </li>
                    </ul>
                </LabPanel>
            </div>

            <InfoPanel entry={dialogCatalog} />
        </div>
    );
}
