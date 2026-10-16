import { popoverContent } from '@/design-system/classNames';
import {
    createContext,
    useCallback,
    useContext,
    useEffect,
    useId,
    useLayoutEffect,
    useMemo,
    useRef,
    useState,
    cloneElement,
    isValidElement,
    type CSSProperties,
    type MouseEvent,
    type ReactElement,
    type ReactNode,
} from 'react';
import { createPortal } from 'react-dom';

export type PopoverSide = 'top' | 'right' | 'bottom' | 'left';
export type PopoverAlign = 'start' | 'center' | 'end';

const VIEWPORT_PADDING = 8;
const TRIGGER_GAP = 8;

const FOCUSABLE_SELECTOR =
    'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

type PopoverContextValue = {
    open: boolean;
    setOpen: (next: boolean) => void;
    contentId: string;
    triggerRef: React.RefObject<HTMLElement | null>;
    contentRef: React.RefObject<HTMLDivElement | null>;
    returnFocusRef: React.RefObject<boolean>;
};

const PopoverContext = createContext<PopoverContextValue | null>(null);

function usePopoverContext(component: string): PopoverContextValue {
    const context = useContext(PopoverContext);
    if (!context) {
        throw new Error(`${component} must be used within Popover`);
    }
    return context;
}

function computePosition(
    trigger: DOMRect,
    panel: DOMRect,
    side: PopoverSide,
    align: PopoverAlign,
): { top: number; left: number } {
    let top = 0;
    let left = 0;

    if (side === 'top') {
        top = trigger.top - panel.height - TRIGGER_GAP;
    } else if (side === 'bottom') {
        top = trigger.bottom + TRIGGER_GAP;
    } else if (side === 'left') {
        left = trigger.left - panel.width - TRIGGER_GAP;
    } else {
        left = trigger.right + TRIGGER_GAP;
    }

    if (side === 'top' || side === 'bottom') {
        if (align === 'start') {
            left = trigger.left;
        } else if (align === 'center') {
            left = trigger.left + trigger.width / 2 - panel.width / 2;
        } else {
            left = trigger.right - panel.width;
        }
    } else if (align === 'start') {
        top = trigger.top;
    } else if (align === 'center') {
        top = trigger.top + trigger.height / 2 - panel.height / 2;
    } else {
        top = trigger.bottom - panel.height;
    }

    const maxLeft = window.innerWidth - panel.width - VIEWPORT_PADDING;
    const maxTop = window.innerHeight - panel.height - VIEWPORT_PADDING;
    left = Math.min(Math.max(left, VIEWPORT_PADDING), Math.max(VIEWPORT_PADDING, maxLeft));
    top = Math.min(Math.max(top, VIEWPORT_PADDING), Math.max(VIEWPORT_PADDING, maxTop));

    return { top, left };
}

function getFirstFocusable(container: HTMLElement): HTMLElement | null {
    return container.querySelector<HTMLElement>(FOCUSABLE_SELECTOR);
}

function getTriggerFocusTarget(triggerRoot: HTMLElement | null): HTMLElement | null {
    if (!triggerRoot) {
        return null;
    }
    return getFirstFocusable(triggerRoot) ?? triggerRoot;
}

export type PopoverProps = {
    open?: boolean;
    defaultOpen?: boolean;
    onOpenChange?: (open: boolean) => void;
    className?: string;
    children: ReactNode;
};

export default function Popover({
    open: openProp,
    defaultOpen = false,
    onOpenChange,
    className = '',
    children,
}: PopoverProps) {
    const isControlled = openProp !== undefined;
    const [uncontrolledOpen, setUncontrolledOpen] = useState(defaultOpen);
    const open = isControlled ? openProp : uncontrolledOpen;
    const contentId = useId();
    const triggerRef = useRef<HTMLElement | null>(null);
    const contentRef = useRef<HTMLDivElement | null>(null);
    const returnFocusRef = useRef(false);

    const setOpen = useCallback(
        (next: boolean) => {
            if (!isControlled) {
                setUncontrolledOpen(next);
            }
            onOpenChange?.(next);
        },
        [isControlled, onOpenChange],
    );

    const contextValue = useMemo(
        (): PopoverContextValue => ({
            open,
            setOpen,
            contentId,
            triggerRef,
            contentRef,
            returnFocusRef,
        }),
        [contentId, open, setOpen],
    );

    const rootClass = className.trim();

    return (
        <PopoverContext.Provider value={contextValue}>
            {rootClass ? <div className={rootClass}>{children}</div> : children}
        </PopoverContext.Provider>
    );
}

export type PopoverTriggerProps = {
    className?: string;
    children: ReactNode;
};

type TriggerElementProps = {
    className?: string;
    onClick?: (event: MouseEvent<HTMLElement>) => void;
    'aria-expanded'?: boolean;
    'aria-controls'?: string;
    disabled?: boolean;
};

export function PopoverTrigger({ className = '', children }: PopoverTriggerProps) {
    const { open, setOpen, contentId, triggerRef } = usePopoverContext('PopoverTrigger');

    if (!isValidElement(children)) {
        throw new Error('PopoverTrigger requires a single React element child');
    }

    const child = children as ReactElement<TriggerElementProps>;
    const childClass = [child.props.className, className].filter(Boolean).join(' ');

    const handleClick = (event: MouseEvent<HTMLElement>) => {
        child.props.onClick?.(event);
        if (event.defaultPrevented || child.props.disabled) {
            return;
        }
        setOpen(!open);
    };

    const wrapClass = ['inline-flex max-w-full', className].filter(Boolean).join(' ');

    return (
        <span ref={triggerRef} className={wrapClass}>
            {cloneElement(child, {
                className: childClass || undefined,
                'aria-expanded': open,
                'aria-controls': contentId,
                onClick: handleClick,
            })}
        </span>
    );
}

export type PopoverContentProps = {
    side?: PopoverSide;
    align?: PopoverAlign;
    className?: string;
    children: ReactNode;
};

export function PopoverContent({
    side = 'bottom',
    align = 'center',
    className = '',
    children,
}: PopoverContentProps) {
    const { open, setOpen, contentId, triggerRef, contentRef, returnFocusRef } =
        usePopoverContext('PopoverContent');
    const [mounted, setMounted] = useState(false);
    const [coords, setCoords] = useState<CSSProperties>({ top: 0, left: 0 });

    const updatePosition = useCallback(() => {
        const triggerEl = triggerRef.current;
        const panelEl = contentRef.current;
        if (!triggerEl || !panelEl) {
            return;
        }
        const triggerRect = triggerEl.getBoundingClientRect();
        const panelRect = panelEl.getBoundingClientRect();
        const { top, left } = computePosition(triggerRect, panelRect, side, align);
        setCoords({ top, left });
    }, [align, side, triggerRef]);

    const close = useCallback(() => {
        setOpen(false);
    }, [setOpen]);

    useEffect(() => {
        setMounted(true);
    }, []);

    useLayoutEffect(() => {
        if (!open) {
            if (returnFocusRef.current) {
                getTriggerFocusTarget(triggerRef.current)?.focus();
                returnFocusRef.current = false;
            }
            return;
        }
        updatePosition();
        const panelEl = contentRef.current;
        if (!panelEl) {
            return;
        }
        const focusable = getFirstFocusable(panelEl);
        if (focusable) {
            focusable.focus();
            returnFocusRef.current = true;
        } else {
            returnFocusRef.current = false;
        }
    }, [open, children, side, align, returnFocusRef, triggerRef, updatePosition]);

    useEffect(() => {
        if (!open) {
            return;
        }

        const onScrollOrResize = () => updatePosition();
        window.addEventListener('scroll', onScrollOrResize, true);
        window.addEventListener('resize', onScrollOrResize);

        const onPointerDown = (event: PointerEvent) => {
            const target = event.target;
            if (!(target instanceof Node)) {
                return;
            }
            if (triggerRef.current?.contains(target) || contentRef.current?.contains(target)) {
                return;
            }
            close();
        };

        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                close();
            }
        };

        document.addEventListener('pointerdown', onPointerDown, true);
        document.addEventListener('keydown', onKeyDown);

        return () => {
            window.removeEventListener('scroll', onScrollOrResize, true);
            window.removeEventListener('resize', onScrollOrResize);
            document.removeEventListener('pointerdown', onPointerDown, true);
            document.removeEventListener('keydown', onKeyDown);
        };
    }, [close, open, triggerRef, updatePosition]);

    useLayoutEffect(() => {
        if (open) {
            updatePosition();
        }
    }, [open, updatePosition]);

    if (!open || !mounted) {
        return null;
    }

    const panelClass = `${popoverContent} ${className}`.trim();

    return createPortal(
        <div ref={contentRef} id={contentId} className={panelClass} style={coords}>
            {children}
        </div>,
        document.body,
    );
}
