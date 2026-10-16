import {
    tabsList,
    tabsPanel,
    tabsRoot,
    tabsTabBase,
    tabsTabDisabled,
    tabsTabSelected,
    tabsTabUnselected,
} from '@/design-system/classNames';
import {
    useCallback,
    useId,
    useRef,
    type KeyboardEvent,
    type ReactNode,
} from 'react';

export type TabItem = {
    id: string;
    label: string;
    disabled?: boolean;
};

export type TabsProps = {
    tabs: TabItem[];
    /** Selected tab id (caller-controlled). */
    active: string;
    onChange: (id: string) => void;
    /** Accessible name for the tablist (required). */
    'aria-label': string;
    /** Panel content keyed by tab id — preferred for full tab/tabpanel wiring. */
    panels?: Partial<Record<string, ReactNode>>;
    /** Active panel body when not using `panels` (single tabpanel slot). */
    children?: ReactNode;
    className?: string;
    /** Optional class applied to each tabpanel wrapper. */
    panelsClassName?: string;
};

function tabId(base: string, id: string): string {
    return `${base}-tab-${id}`;
}

function panelId(base: string, id: string): string {
    return `${base}-panel-${id}`;
}

export default function Tabs({
    tabs,
    active,
    onChange,
    'aria-label': ariaLabel,
    panels,
    children,
    className = '',
    panelsClassName = '',
}: TabsProps) {
    const reactId = useId().replace(/:/g, '');
    const buttonRefs = useRef<Map<string, HTMLButtonElement>>(new Map());

    const hasPanels = panels !== undefined && Object.keys(panels).length > 0;
    const hasChildPanel = children !== undefined;
    const linkPanels = hasPanels || hasChildPanel;

    const enabledTabs = tabs.filter((tab) => !tab.disabled);

    const focusTab = useCallback((id: string) => {
        requestAnimationFrame(() => buttonRefs.current.get(id)?.focus());
    }, []);

    const selectTab = useCallback(
        (id: string) => {
            const tab = tabs.find((item) => item.id === id);
            if (!tab || tab.disabled) {
                return;
            }
            onChange(id);
        },
        [onChange, tabs],
    );

    const moveSelection = useCallback(
        (direction: 1 | -1) => {
            if (enabledTabs.length === 0) {
                return;
            }
            const currentIndex = enabledTabs.findIndex((tab) => tab.id === active);
            const startIndex = currentIndex >= 0 ? currentIndex : 0;
            let nextIndex = startIndex + direction;
            if (nextIndex < 0) {
                nextIndex = enabledTabs.length - 1;
            }
            if (nextIndex >= enabledTabs.length) {
                nextIndex = 0;
            }
            const next = enabledTabs[nextIndex];
            if (next) {
                selectTab(next.id);
                focusTab(next.id);
            }
        },
        [active, enabledTabs, focusTab, selectTab],
    );

    const handleListKeyDown = (event: KeyboardEvent<HTMLDivElement>) => {
        switch (event.key) {
            case 'ArrowRight':
                event.preventDefault();
                moveSelection(1);
                break;
            case 'ArrowLeft':
                event.preventDefault();
                moveSelection(-1);
                break;
            case 'Home':
                event.preventDefault();
                if (enabledTabs[0]) {
                    selectTab(enabledTabs[0].id);
                    focusTab(enabledTabs[0].id);
                }
                break;
            case 'End':
                event.preventDefault();
                if (enabledTabs.length > 0) {
                    const last = enabledTabs[enabledTabs.length - 1];
                    selectTab(last.id);
                    focusTab(last.id);
                }
                break;
            default:
                break;
        }
    };

    const rootClass = `${tabsRoot} ${className}`.trim();

    return (
        <div className={rootClass}>
            <div role="tablist" aria-label={ariaLabel} aria-orientation="horizontal" className={tabsList} onKeyDown={handleListKeyDown}>
                {tabs.map((tab) => {
                    const selected = tab.id === active;
                    const isDisabled = tab.disabled === true;
                    const stateClass = selected ? tabsTabSelected : tabsTabUnselected;
                    const tabClass = `${tabsTabBase} ${stateClass} ${isDisabled ? tabsTabDisabled : ''}`.trim();
                    const controlId = tabId(reactId, tab.id);
                    const associatedPanelId = panelId(reactId, tab.id);

                    return (
                        <button
                            key={tab.id}
                            ref={(node) => {
                                if (node) {
                                    buttonRefs.current.set(tab.id, node);
                                } else {
                                    buttonRefs.current.delete(tab.id);
                                }
                            }}
                            type="button"
                            role="tab"
                            id={controlId}
                            aria-selected={selected}
                            aria-controls={linkPanels ? associatedPanelId : undefined}
                            disabled={isDisabled}
                            tabIndex={selected ? 0 : -1}
                            className={tabClass}
                            onClick={() => selectTab(tab.id)}
                        >
                            {tab.label}
                        </button>
                    );
                })}
            </div>

            {hasPanels
                ? tabs.map((tab) => {
                      const content = panels?.[tab.id];
                      if (content === undefined) {
                          return null;
                      }
                      const selected = tab.id === active;
                      const associatedPanelId = panelId(reactId, tab.id);
                      const controlId = tabId(reactId, tab.id);

                      return (
                          <div
                              key={tab.id}
                              role="tabpanel"
                              id={associatedPanelId}
                              aria-labelledby={controlId}
                              hidden={!selected}
                              className={`${tabsPanel} ${panelsClassName}`.trim()}
                          >
                              {selected ? content : null}
                          </div>
                      );
                  })
                : null}

            {!hasPanels && hasChildPanel ? (
                <div
                    role="tabpanel"
                    id={panelId(reactId, active)}
                    aria-labelledby={tabId(reactId, active)}
                    className={tabsPanel}
                >
                    {children}
                </div>
            ) : null}
        </div>
    );
}
