import type { ReactNode } from 'react';

export type TabItem = {
    id: string;
    label: string;
};

type TabsProps = {
    tabs: TabItem[];
    active: string;
    onChange: (id: string) => void;
    children?: ReactNode;
};

export default function Tabs({ tabs, active, onChange, children }: TabsProps) {
    return (
        <div>
            <div role="tablist" className="flex flex-wrap gap-1 border-b border-line">
                {tabs.map((tab) => {
                    const selected = tab.id === active;

                    return (
                        <button
                            key={tab.id}
                            type="button"
                            role="tab"
                            aria-selected={selected}
                            className={`min-h-10 rounded-t-md px-3 py-2 text-sm font-medium focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/40 ${
                                selected ? 'bg-white text-brand' : 'text-muted hover:text-ink'
                            }`}
                            onClick={() => onChange(tab.id)}
                        >
                            {tab.label}
                        </button>
                    );
                })}
            </div>
            {children ? (
                <div role="tabpanel" className="pt-4">
                    {children}
                </div>
            ) : null}
        </div>
    );
}
