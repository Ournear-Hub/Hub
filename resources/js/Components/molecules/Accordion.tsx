import {
    accordionChevron,
    accordionContent,
    accordionItem,
    accordionRoot,
    accordionSummary,
    accordionSummaryDisabled,
} from '@/design-system/classNames';
import OurNearIcon from '@/icons/OurNearIcon';
import {
    createContext,
    useCallback,
    useContext,
    useId,
    useMemo,
    useState,
    type MouseEvent,
    type ReactNode,
    type SyntheticEvent,
} from 'react';

export type AccordionType = 'single' | 'multiple';

export type AccordionProps = {
    type?: AccordionType;
    /** When type=single, allow closing the open item (default true). */
    collapsible?: boolean;
    defaultValue?: string | string[];
    value?: string | string[];
    onValueChange?: (value: string | string[]) => void;
    className?: string;
    children: ReactNode;
};

type AccordionContextValue = {
    type: AccordionType;
    collapsible: boolean;
    isControlled: boolean;
    isOpen: (itemValue: string) => boolean;
    requestToggle: (itemValue: string, nextOpen: boolean, disabled?: boolean) => void;
    getContentId: (itemValue: string) => string;
};

type AccordionItemContextValue = {
    value: string;
    disabled: boolean;
};

const AccordionContext = createContext<AccordionContextValue | null>(null);
const AccordionItemContext = createContext<AccordionItemContextValue | null>(null);

function normalizeToArray(input: string | string[] | undefined): string[] {
    if (input === undefined) {
        return [];
    }
    return Array.isArray(input) ? input : [input];
}

function toExternalValue(type: AccordionType, openValues: string[]): string | string[] {
    if (type === 'single') {
        return openValues[0] ?? '';
    }
    return openValues;
}

function useAccordionContext(component: string): AccordionContextValue {
    const context = useContext(AccordionContext);
    if (!context) {
        throw new Error(`${component} must be used within Accordion`);
    }
    return context;
}

function useAccordionItemContext(component: string): AccordionItemContextValue {
    const context = useContext(AccordionItemContext);
    if (!context) {
        throw new Error(`${component} must be used within AccordionItem`);
    }
    return context;
}

export default function Accordion({
    type = 'single',
    collapsible = true,
    defaultValue,
    value,
    onValueChange,
    className = '',
    children,
}: AccordionProps) {
    const isControlled = value !== undefined;
    const [uncontrolledOpen, setUncontrolledOpen] = useState<string[]>(() => normalizeToArray(defaultValue));

    const openValues = isControlled ? normalizeToArray(value) : uncontrolledOpen;
    const baseId = useId();
    const contentIds = useMemo(() => new Map<string, string>(), []);

    const setOpenValues = useCallback(
        (next: string[]) => {
            const external = toExternalValue(type, next);
            if (!isControlled) {
                setUncontrolledOpen(next);
            }
            onValueChange?.(external);
        },
        [isControlled, onValueChange, type],
    );

    const isOpen = useCallback((itemValue: string) => openValues.includes(itemValue), [openValues]);

    const requestToggle = useCallback(
        (itemValue: string, nextOpen: boolean, disabled?: boolean) => {
            if (disabled) {
                return;
            }

            if (type === 'multiple') {
                const next = nextOpen
                    ? [...openValues.filter((v) => v !== itemValue), itemValue]
                    : openValues.filter((v) => v !== itemValue);
                setOpenValues(next);
                return;
            }

            if (nextOpen) {
                setOpenValues([itemValue]);
                return;
            }

            if (collapsible) {
                setOpenValues([]);
            }
        },
        [collapsible, openValues, setOpenValues, type],
    );

    const getContentId = useCallback(
        (itemValue: string) => {
            const existing = contentIds.get(itemValue);
            if (existing) {
                return existing;
            }
            const id = `${baseId}-content-${itemValue.replace(/[^a-zA-Z0-9_-]/g, '_')}`;
            contentIds.set(itemValue, id);
            return id;
        },
        [baseId, contentIds],
    );

    const contextValue = useMemo(
        (): AccordionContextValue => ({
            type,
            collapsible,
            isControlled,
            isOpen,
            requestToggle,
            getContentId,
        }),
        [collapsible, getContentId, isControlled, isOpen, requestToggle, type],
    );

    const rootClass = `${accordionRoot} ${className}`.trim();

    return (
        <AccordionContext.Provider value={contextValue}>
            <div className={rootClass}>{children}</div>
        </AccordionContext.Provider>
    );
}

export type AccordionItemProps = {
    value: string;
    disabled?: boolean;
    className?: string;
    children: ReactNode;
};

export function AccordionItem({ value, disabled = false, className = '', children }: AccordionItemProps) {
    const { isOpen, requestToggle, isControlled } = useAccordionContext('AccordionItem');
    const open = isOpen(value);
    const itemClass = `${accordionItem} ${className}`.trim();

    const handleToggle = (event: SyntheticEvent<HTMLDetailsElement>) => {
        if (disabled || isControlled) {
            event.preventDefault();
            return;
        }
        requestToggle(value, event.currentTarget.open, disabled);
    };

    const detailsOpen = disabled ? false : open;

    return (
        <AccordionItemContext.Provider value={{ value, disabled }}>
            <details open={detailsOpen} onToggle={handleToggle} className={itemClass}>
                {children}
            </details>
        </AccordionItemContext.Provider>
    );
}

export type AccordionTriggerProps = {
    className?: string;
    children: ReactNode;
};

export function AccordionTrigger({ className = '', children }: AccordionTriggerProps) {
    const { isOpen, getContentId, isControlled, requestToggle } = useAccordionContext('AccordionTrigger');
    const { value, disabled } = useAccordionItemContext('AccordionTrigger');
    const contentId = getContentId(value);
    const expanded = isOpen(value);

    const summaryClass = `${accordionSummary} ${disabled ? accordionSummaryDisabled : ''} ${className}`.trim();

    const handleClick = (event: MouseEvent<HTMLElement>) => {
        if (disabled) {
            event.preventDefault();
            return;
        }
        if (isControlled) {
            event.preventDefault();
            requestToggle(value, !expanded, disabled);
        }
    };

    return (
        <summary
            className={summaryClass}
            aria-controls={contentId}
            aria-expanded={disabled ? false : expanded}
            aria-disabled={disabled || undefined}
            onClick={handleClick}
        >
            <span className="min-w-0 flex-1 text-left">{children}</span>
            <OurNearIcon name="chevron-down" size="sm" decorative className={accordionChevron} />
        </summary>
    );
}

export type AccordionContentProps = {
    className?: string;
    children: ReactNode;
};

export function AccordionContent({ className = '', children }: AccordionContentProps) {
    const { getContentId } = useAccordionContext('AccordionContent');
    const { value } = useAccordionItemContext('AccordionContent');
    const contentId = getContentId(value);
    const panelClass = `${accordionContent} ${className}`.trim();

    return (
        <div id={contentId} className={panelClass}>
            {children}
        </div>
    );
}
