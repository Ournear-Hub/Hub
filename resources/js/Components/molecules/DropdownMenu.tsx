import {
    dropdownMenuContent,
    dropdownMenuItem,
    dropdownMenuItemActive,
    dropdownMenuItemDestructive,
    dropdownMenuItemDisabled,
    dropdownMenuSeparator,
} from '@/design-system/classNames';
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
    type KeyboardEvent as ReactKeyboardEvent,
    type MouseEvent,
    type ReactElement,
    type ReactNode,
} from 'react';
import { createPortal } from 'react-dom';

export type DropdownMenuSide = 'top' | 'right' | 'bottom' | 'left';
export type DropdownMenuAlign = 'start' | 'center' | 'end';

const VIEWPORT_PADDING = 8;
const TRIGGER_GAP = 8;

type OpenFocusTarget = 'first' | 'last' | 'none';

type DropdownMenuContextValue = {
    open: boolean;
    setOpen: (next: boolean) => void;
    contentId: string;
    triggerRef: React.RefObject<HTMLElement | null>;
    contentRef: React.RefObject<HTMLDivElement | null>;
    activeIndex: number;
    setActiveIndex: (index: number) => void;
    registerItemIndex: () => number;
    resetItemIndices: () => void;
    openFocusTargetRef: React.RefObject<OpenFocusTarget>;
    closeAndFocusTrigger: () => void;
};

const DropdownMenuContext = createContext<DropdownMenuContextValue | null>(null);

function useDropdownMenuContext(component: string): DropdownMenuContextValue {
    const context = useContext(DropdownMenuContext);
    if (!context) {
        throw new Error(`${component} must be used within DropdownMenu`);
    }
    return context;
}

function computePosition(
    trigger: DOMRect,
    panel: DOMRect,
    side: DropdownMenuSide,
    align: DropdownMenuAlign,
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

function getTriggerFocusTarget(triggerRoot: HTMLElement | null): HTMLElement | null {
    if (!triggerRoot) {
        return null;
    }
    return (
        triggerRoot.querySelector<HTMLElement>(
            'button:not([disabled]), a[href], [tabindex]:not([tabindex="-1"])',
        ) ?? triggerRoot
    );
}

function getMenuItemButtonAtIndex(container: HTMLElement | null, index: number): HTMLButtonElement | null {
    if (!container) {
        return null;
    }
    const buttons = Array.from(container.querySelectorAll<HTMLButtonElement>('button[role="menuitem"]'));
    return buttons[index] ?? null;
}

function isMenuItemDisabled(container: HTMLElement | null, index: number): boolean {
    const button = getMenuItemButtonAtIndex(container, index);
    return !button || button.disabled;
}

function findNextEnabledIndex(container: HTMLElement | null, from: number, direction: 1 | -1): number {
    const buttons = container
        ? Array.from(container.querySelectorAll<HTMLButtonElement>('button[role="menuitem"]'))
        : [];
    if (buttons.length === 0) {
        return -1;
    }
    let index = from;
    for (let step = 0; step < buttons.length; step += 1) {
        index = (index + direction + buttons.length) % buttons.length;
        if (!buttons[index]?.disabled) {
            return index;
        }
    }
    return from;
}

function findFirstEnabledIndex(container: HTMLElement | null): number {
    const buttons = container
        ? Array.from(container.querySelectorAll<HTMLButtonElement>('button[role="menuitem"]'))
        : [];
    return buttons.findIndex((button) => !button.disabled);
}

function findLastEnabledIndex(container: HTMLElement | null): number {
    const buttons = container
        ? Array.from(container.querySelectorAll<HTMLButtonElement>('button[role="menuitem"]'))
        : [];
    for (let index = buttons.length - 1; index >= 0; index -= 1) {
        if (!buttons[index]?.disabled) {
            return index;
        }
    }
    return -1;
}

export type DropdownMenuProps = {
    open?: boolean;
    defaultOpen?: boolean;
    onOpenChange?: (open: boolean) => void;
    className?: string;
    children: ReactNode;
};

export default function DropdownMenu({
    open: openProp,
    defaultOpen = false,
    onOpenChange,
    className = '',
    children,
}: DropdownMenuProps) {
    const isControlled = openProp !== undefined;
    const [uncontrolledOpen, setUncontrolledOpen] = useState(defaultOpen);
    const open = isControlled ? openProp : uncontrolledOpen;
    const contentId = useId();
    const triggerRef = useRef<HTMLElement | null>(null);
    const contentRef = useRef<HTMLDivElement | null>(null);
    const openFocusTargetRef = useRef<OpenFocusTarget>('none');
    const itemIndexRef = useRef(0);
    const [activeIndex, setActiveIndex] = useState(-1);

    const setOpen = useCallback(
        (next: boolean) => {
            if (!isControlled) {
                setUncontrolledOpen(next);
            }
            onOpenChange?.(next);
            if (!next) {
                setActiveIndex(-1);
                openFocusTargetRef.current = 'none';
            }
        },
        [isControlled, onOpenChange],
    );

    const closeAndFocusTrigger = useCallback(() => {
        setOpen(false);
        getTriggerFocusTarget(triggerRef.current)?.focus();
    }, [setOpen]);

    const registerItemIndex = useCallback(() => {
        const index = itemIndexRef.current;
        itemIndexRef.current += 1;
        return index;
    }, []);

    const resetItemIndices = useCallback(() => {
        itemIndexRef.current = 0;
    }, []);

    const contextValue = useMemo(
        (): DropdownMenuContextValue => ({
            open,
            setOpen,
            contentId,
            triggerRef,
            contentRef,
            activeIndex,
            setActiveIndex,
            registerItemIndex,
            resetItemIndices,
            openFocusTargetRef,
            closeAndFocusTrigger,
        }),
        [
            activeIndex,
            closeAndFocusTrigger,
            contentId,
            open,
            registerItemIndex,
            resetItemIndices,
            setOpen,
        ],
    );

    const rootClass = className.trim();

    return (
        <DropdownMenuContext.Provider value={contextValue}>
            {rootClass ? <div className={rootClass}>{children}</div> : children}
        </DropdownMenuContext.Provider>
    );
}

export type DropdownMenuTriggerProps = {
    className?: string;
    children: ReactNode;
};

type TriggerElementProps = {
    className?: string;
    onClick?: (event: MouseEvent<HTMLElement>) => void;
    onKeyDown?: (event: ReactKeyboardEvent<HTMLElement>) => void;
    'aria-expanded'?: boolean;
    'aria-controls'?: string;
    'aria-haspopup'?: boolean | 'menu' | 'dialog' | 'listbox' | 'tree' | 'grid';
    disabled?: boolean;
};

export function DropdownMenuTrigger({ className = '', children }: DropdownMenuTriggerProps) {
    const { open, setOpen, contentId, triggerRef, openFocusTargetRef } =
        useDropdownMenuContext('DropdownMenuTrigger');

    if (!isValidElement(children)) {
        throw new Error('DropdownMenuTrigger requires a single React element child');
    }

    const child = children as ReactElement<TriggerElementProps>;
    const childClass = [child.props.className, className].filter(Boolean).join(' ');

    const openMenu = (focusTarget: OpenFocusTarget) => {
        openFocusTargetRef.current = focusTarget;
        setOpen(true);
    };

    const handleClick = (event: MouseEvent<HTMLElement>) => {
        child.props.onClick?.(event);
        if (event.defaultPrevented || child.props.disabled) {
            return;
        }
        if (open) {
            setOpen(false);
        } else {
            openMenu('first');
        }
    };

    const handleKeyDown = (event: ReactKeyboardEvent<HTMLElement>) => {
        child.props.onKeyDown?.(event);
        if (event.defaultPrevented || child.props.disabled) {
            return;
        }

        if (open) {
            return;
        }

        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            openMenu('first');
            return;
        }
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            openMenu('first');
            return;
        }
        if (event.key === 'ArrowUp') {
            event.preventDefault();
            openMenu('last');
        }
    };

    const wrapClass = ['inline-flex max-w-full', className].filter(Boolean).join(' ');

    return (
        <span ref={triggerRef} className={wrapClass}>
            {cloneElement(child, {
                className: childClass || undefined,
                'aria-haspopup': 'menu',
                'aria-expanded': open,
                'aria-controls': contentId,
                onClick: handleClick,
                onKeyDown: handleKeyDown,
            })}
        </span>
    );
}

export type DropdownMenuContentProps = {
    side?: DropdownMenuSide;
    align?: DropdownMenuAlign;
    className?: string;
    children: ReactNode;
};

export function DropdownMenuContent({
    side = 'bottom',
    align = 'start',
    className = '',
    children,
}: DropdownMenuContentProps) {
    const {
        open,
        setOpen,
        contentId,
        triggerRef,
        contentRef,
        activeIndex,
        setActiveIndex,
        resetItemIndices,
        openFocusTargetRef,
        closeAndFocusTrigger,
    } = useDropdownMenuContext('DropdownMenuContent');
    const [mounted, setMounted] = useState(false);
    const [coords, setCoords] = useState<CSSProperties>({ top: 0, left: 0 });

    resetItemIndices();

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

    const focusMenuIndex = useCallback(
        (index: number) => {
            const button = getMenuItemButtonAtIndex(contentRef.current, index);
            if (button && !button.disabled) {
                setActiveIndex(index);
                button.focus();
            }
        },
        [contentRef, setActiveIndex],
    );

    const activateMenuIndex = useCallback(
        (index: number) => {
            const button = getMenuItemButtonAtIndex(contentRef.current, index);
            if (!button || button.disabled) {
                return;
            }
            button.click();
        },
        [contentRef],
    );

    useEffect(() => {
        setMounted(true);
    }, []);

    useLayoutEffect(() => {
        if (!open) {
            return;
        }
        updatePosition();
        const focusTarget = openFocusTargetRef.current;
        let index = -1;
        if (focusTarget === 'last') {
            index = findLastEnabledIndex(contentRef.current);
        } else if (focusTarget === 'first') {
            index = findFirstEnabledIndex(contentRef.current);
        }
        if (index >= 0) {
            focusMenuIndex(index);
        }
        openFocusTargetRef.current = 'none';
    }, [contentRef, focusMenuIndex, open, openFocusTargetRef, updatePosition]);

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
            setOpen(false);
        };

        const onKeyDown = (event: globalThis.KeyboardEvent) => {
            if (event.key === 'Tab') {
                setOpen(false);
                return;
            }
            if (event.key === 'Escape') {
                event.preventDefault();
                closeAndFocusTrigger();
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
    }, [closeAndFocusTrigger, contentRef, open, setOpen, triggerRef, updatePosition]);

    const handleMenuKeyDown = (event: ReactKeyboardEvent<HTMLDivElement>) => {
        const container = contentRef.current;
        if (!container) {
            return;
        }

        const currentIndex = activeIndex >= 0 ? activeIndex : findFirstEnabledIndex(container);

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            const next = findNextEnabledIndex(container, currentIndex, 1);
            if (next >= 0) {
                focusMenuIndex(next);
            }
            return;
        }
        if (event.key === 'ArrowUp') {
            event.preventDefault();
            const next = findNextEnabledIndex(container, currentIndex, -1);
            if (next >= 0) {
                focusMenuIndex(next);
            }
            return;
        }
        if (event.key === 'Home') {
            event.preventDefault();
            const first = findFirstEnabledIndex(container);
            if (first >= 0) {
                focusMenuIndex(first);
            }
            return;
        }
        if (event.key === 'End') {
            event.preventDefault();
            const last = findLastEnabledIndex(container);
            if (last >= 0) {
                focusMenuIndex(last);
            }
            return;
        }
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            if (currentIndex >= 0 && !isMenuItemDisabled(container, currentIndex)) {
                activateMenuIndex(currentIndex);
            }
        }
    };

    useLayoutEffect(() => {
        if (open) {
            updatePosition();
        }
    }, [open, updatePosition]);

    if (!open || !mounted) {
        return null;
    }

    const panelClass = `${dropdownMenuContent} ${className}`.trim();

    return createPortal(
        <div
            ref={contentRef}
            id={contentId}
            role="menu"
            className={panelClass}
            style={coords}
            onKeyDown={handleMenuKeyDown}
        >
            {children}
        </div>,
        document.body,
    );
}

export type DropdownMenuItemProps = {
    children: ReactNode;
    onSelect?: () => void;
    disabled?: boolean;
    destructive?: boolean;
    className?: string;
};

export function DropdownMenuItem({
    children,
    onSelect,
    disabled = false,
    destructive = false,
    className = '',
}: DropdownMenuItemProps) {
    const { open, activeIndex, setActiveIndex, registerItemIndex, closeAndFocusTrigger } =
        useDropdownMenuContext('DropdownMenuItem');

    const itemIndex = registerItemIndex();
    const isActive = open && activeIndex === itemIndex;

    const itemClass = [
        dropdownMenuItem,
        destructive ? dropdownMenuItemDestructive : '',
        disabled ? dropdownMenuItemDisabled : '',
        isActive ? dropdownMenuItemActive : '',
        className,
    ]
        .filter(Boolean)
        .join(' ');

    const handleClick = () => {
        if (disabled) {
            return;
        }
        onSelect?.();
        closeAndFocusTrigger();
    };

    return (
        <button
            type="button"
            role="menuitem"
            disabled={disabled}
            tabIndex={isActive ? 0 : -1}
            className={itemClass}
            onClick={handleClick}
            onFocus={() => setActiveIndex(itemIndex)}
        >
            {children}
        </button>
    );
}

export type DropdownMenuSeparatorProps = {
    className?: string;
};

export function DropdownMenuSeparator({ className = '' }: DropdownMenuSeparatorProps) {
    const separatorClass = `${dropdownMenuSeparator} ${className}`.trim();
    return <div role="separator" className={separatorClass} />;
}
