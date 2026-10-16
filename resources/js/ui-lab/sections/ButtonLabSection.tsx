import { Button } from '@/Components/atoms';
import type { ButtonSurface, ButtonVariant } from '@/Components/atoms/Button';
import LabPanel from '@/ui-lab/components/LabPanel';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import { buttonCatalog } from '@/ui-lab/catalog';

const publicVariants: ButtonVariant[] = ['primary', 'secondary', 'outline', 'ghost', 'destructive', 'link'];
const adminVariants: ButtonVariant[] = ['primary', 'secondary', 'outline', 'ghost', 'destructive'];

function VariantRow({ surface, label }: { surface: ButtonSurface; label: string }) {
    const variants = surface === 'public' || surface === 'account' ? publicVariants : adminVariants;

    return (
        <LabPanel
            title={`Surface: ${label}`}
            description={
                surface === 'public' || surface === 'account'
                    ? 'Primary uses accent #F15A24'
                    : 'Primary uses brand #214449'
            }
        >
            <div className="flex flex-wrap gap-2">
                {variants.map((variant) => (
                    <Button key={variant} surface={surface} variant={variant}>
                        {variant}
                    </Button>
                ))}
            </div>
        </LabPanel>
    );
}

export default function ButtonLabSection() {
    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <VariantRow surface="public" label="Public / Account" />
                <VariantRow surface="admin" label="Admin / Corporate" />

                <LabPanel title="Sizes" description="Canonical sm / md / lg on public primary.">
                    <div className="flex flex-wrap items-center gap-2">
                        <Button surface="public" size="sm">
                            Small
                        </Button>
                        <Button surface="public" size="md">
                            Medium
                        </Button>
                        <Button surface="public" size="lg">
                            Large
                        </Button>
                    </div>
                </LabPanel>

                <LabPanel title="States" description="Disabled and loading use the real Button implementation.">
                    <div className="flex flex-wrap gap-2">
                        <Button surface="public">Normal</Button>
                        <Button surface="public" disabled>
                            Disabled
                        </Button>
                        <Button surface="public" loading>
                            Loading
                        </Button>
                        <Button surface="admin" loading>
                            Admin loading
                        </Button>
                    </div>
                    <p className="mt-3 text-xs text-muted">Tab to a button to inspect focus-visible ring (foundation focusRing).</p>
                </LabPanel>

                <LabPanel title="Icons" description="Leading and trailing OurNearIcon slots.">
                    <div className="flex flex-wrap gap-2">
                        <Button surface="public" leadingIcon="search">
                            Leading
                        </Button>
                        <Button surface="public" trailingIcon="chevron-right">
                            Trailing
                        </Button>
                        <Button surface="admin" leadingIcon="plus">
                            Admin add
                        </Button>
                    </div>
                </LabPanel>
            </div>

            <InfoPanel entry={buttonCatalog} />
        </div>
    );
}
