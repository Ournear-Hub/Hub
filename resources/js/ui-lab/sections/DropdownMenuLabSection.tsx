import Button from '@/Components/atoms/Button';
import IconButton from '@/Components/atoms/IconButton';
import DropdownMenu, {
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/molecules/DropdownMenu';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { dropdownMenuCatalog } from '@/ui-lab/catalog';
import { useState } from 'react';

export default function DropdownMenuLabSection() {
    const [lastAction, setLastAction] = useState('(none)');

    const log = (action: string) => () => setLastAction(action);

    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel title="Basic candidate actions">
                    <DropdownMenu>
                        <DropdownMenuTrigger>
                            <Button type="button" variant="secondary">
                                Actions
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent>
                            <DropdownMenuItem onSelect={log('Edit candidate')}>Edit candidate</DropdownMenuItem>
                            <DropdownMenuItem onSelect={log('View profile')}>View profile</DropdownMenuItem>
                            <DropdownMenuItem onSelect={log('Duplicate')}>Duplicate</DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                    <p className="mt-2 text-xs text-muted">Last action: {lastAction}</p>
                </LabPanel>

                <LabPanel title="IconButton trigger">
                    <DropdownMenu>
                        <DropdownMenuTrigger>
                            <IconButton icon="more-horizontal" label="More actions" variant="ghost" />
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem onSelect={log('Archive')}>Archive</DropdownMenuItem>
                            <DropdownMenuItem disabled onSelect={log('Locked')}>
                                Locked action
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem destructive onSelect={log('Delete')}>
                                Delete
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </LabPanel>

                <LabPanel title="Placement">
                    <div className="flex flex-wrap gap-4 py-4">
                        {(['bottom', 'top', 'right', 'left'] as const).map((side) => (
                            <DropdownMenu key={side}>
                                <DropdownMenuTrigger>
                                    <Button type="button" variant="outline">
                                        {side}
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent side={side}>
                                    <DropdownMenuItem onSelect={log(`Open ${side}`)}>Open</DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        ))}
                    </div>
                </LabPanel>

                <LabPanel title="Keyboard & dismissal">
                    <ul className="max-w-lg list-inside list-disc space-y-1 text-sm text-muted">
                        <li>Trigger: Enter/Space/ArrowDown/ArrowUp opens menu</li>
                        <li>Menu: ArrowUp/Down, Home/End, Enter/Space activate item</li>
                        <li>Escape closes and returns focus to trigger</li>
                        <li>Tab closes menu (no focus trap)</li>
                        <li>Pointer down outside closes</li>
                    </ul>
                </LabPanel>
            </div>

            <InfoPanel entry={dropdownMenuCatalog} />
        </div>
    );
}
