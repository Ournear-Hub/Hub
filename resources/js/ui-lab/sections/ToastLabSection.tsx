import Button from '@/Components/atoms/Button';
import Toast, { type ToastPlacement, type ToastVariant } from '@/Components/molecules/Toast';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { toastCatalog } from '@/ui-lab/catalog';
import { useState } from 'react';

type ActiveToast = {
    id: number;
    variant: ToastVariant;
    title?: string;
    message: string;
    duration?: number;
    dismissible?: boolean;
    placement?: ToastPlacement;
};

export default function ToastLabSection() {
    const [active, setActive] = useState<ActiveToast | null>(null);
    const [nextId, setNextId] = useState(1);

    const show = (toast: Omit<ActiveToast, 'id'>) => {
        setActive({ ...toast, id: nextId });
        setNextId((value) => value + 1);
    };

    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Variants (portaled)">
                    <div className="flex flex-wrap gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={() =>
                                show({ variant: 'success', message: 'Application submitted successfully.' })
                            }
                        >
                            Success
                        </Button>
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={() => show({ variant: 'info', message: 'Profile saved.' })}
                        >
                            Info
                        </Button>
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={() =>
                                show({
                                    variant: 'warning',
                                    title: 'Incomplete profile',
                                    message: 'Add work history before applying.',
                                })
                            }
                        >
                            Warning
                        </Button>
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={() =>
                                show({ variant: 'error', message: 'Something went wrong. Please try again.' })
                            }
                        >
                            Error
                        </Button>
                    </div>
                </LabPanel>

                <LabPanel title="Duration & dismiss">
                    <div className="flex flex-wrap gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() =>
                                show({
                                    variant: 'info',
                                    message: 'Auto-dismiss in 8 seconds.',
                                    duration: 8000,
                                })
                            }
                        >
                            Long duration
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() =>
                                show({
                                    variant: 'success',
                                    message: 'Stays until dismissed.',
                                    duration: 0,
                                })
                            }
                        >
                            No auto-dismiss
                        </Button>
                    </div>
                </LabPanel>

                <LabPanel title="Placement">
                    <div className="flex flex-wrap gap-2">
                        {(['bottom-end', 'bottom-start', 'top-end', 'top-start'] as const).map((placement) => (
                            <Button
                                key={placement}
                                type="button"
                                variant="outline"
                                onClick={() =>
                                    show({
                                        variant: 'info',
                                        message: `placement=${placement}`,
                                        placement,
                                    })
                                }
                            >
                                {placement}
                            </Button>
                        ))}
                    </div>
                </LabPanel>

                <LabPanel title="Semantics & behavior">
                    <ul className="max-w-lg list-inside list-disc space-y-1 text-sm text-muted">
                        <li>Rendered via portal — does not affect page layout</li>
                        <li>
                            <code className="text-ink">error</code> → <code className="text-ink">role="alert"</code>;
                            others → <code className="text-ink">role="status"</code>
                        </li>
                        <li>Default auto-dismiss 5s; caller removes toast in onDismiss</li>
                        <li>No global queue/manager in PR-4E</li>
                    </ul>
                </LabPanel>
            </div>

            <InfoPanel entry={toastCatalog} />

            {active ? (
                <Toast
                    key={active.id}
                    variant={active.variant}
                    title={active.title}
                    duration={active.duration}
                    dismissible={active.dismissible}
                    placement={active.placement}
                    onDismiss={() => setActive(null)}
                >
                    {active.message}
                </Toast>
            ) : null}
        </div>
    );
}
