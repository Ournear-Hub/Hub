import {
    tooltipContent,
    tooltipContentHidden,
    tooltipContentVisible,
    tooltipDisabledWrap,
} from '@/design-system/classNames';
import {
    cloneElement,
    isValidElement,
    useCallback,
    useEffect,
    useId,
    useLayoutEffect,
    useRef,
    useState,
    type CSSProperties,
    type FocusEvent,
    type ReactElement,
    type ReactNode,
} from 'react';
import { createPortal } from 'react-dom';

export type TooltipSide = 'top' | 'right' | 'bottom' | 'left';
export type TooltipAlign = 'start' | 'center' | 'end';

const DEFAULT_SHOW_DELAY_MS = 400;
const VIEWPORT_PADDING = 8;
const TRIGGER_GAP = 8;

export type TooltipProps = {
    content: ReactNode;
    side?: TooltipSide;
    align?: TooltipAlign;
    /** When the trigger control is disabled, use focusable wrapper hover/focus (pass explicitly). */
    disabled?: boolean;
    /** Milliseconds before show; hide is immediate. */
    delay?: number;
    className?: string;
    children: ReactNode;
};

function mergeDescribedBy(existing: string | undefined, tooltipId: string): string {
    if (!existing) {
        return tooltipId;
    }
    const parts = existing.split(/\s+/).filter(Boolean);
    if (parts.includes(tooltipId)) {
        return existing;
    }
    return `${existing} ${tooltipId}`;
}

function computePosition(
    trigger: DOMRect,
    tooltip: DOMRect,
    side: TooltipSide,
    align: TooltipAlign,
): { top: number; left: number } {
    let top = 0;
    let left = 0;

    if (side === 'top') {
        top = trigger.top - tooltip.height - TRIGGER_GAP;
    } else if (side === 'bottom') {
        top = trigger.bottom + TRIGGER_GAP;
    } else if (side === 'left') {
        left = trigger.left - tooltip.width - TRIGGER_GAP;
    } else {
        left = trigger.right + TRIGGER_GAP;
    }

    if (side === 'top' || side === 'bottom') {
        if (align === 'start') {
            left = trigger.left;
        } else if (align === 'center') {
            left = trigger.left + trigger.width / 2 - tooltip.width / 2;
        } else {
            left = trigger.right - tooltip.width;
        }
    } else if (align === 'start') {
        top = trigger.top;
    } else if (align === 'center') {
        top = trigger.top + trigger.height / 2 - tooltip.height / 2;
    } else {
        top = trigger.bottom - tooltip.height;
    }

    const maxLeft = window.innerWidth - tooltip.width - VIEWPORT_PADDING;
    const maxTop = window.innerHeight - tooltip.height - VIEWPORT_PADDING;
    left = Math.min(Math.max(left, VIEWPORT_PADDING), Math.max(VIEWPORT_PADDING, maxLeft));
    top = Math.min(Math.max(top, VIEWPORT_PADDING), Math.max(VIEWPORT_PADDING, maxTop));

    return { top, left };
}

export default function Tooltip({
    content,
    side = 'top',
    align = 'center',
    disabled = false,
    delay = DEFAULT_SHOW_DELAY_MS,
    className = '',
    children,
}: TooltipProps) {
    const tooltipId = useId();
    const triggerRef = useRef<HTMLSpanElement>(null);
    const tooltipRef = useRef<HTMLDivElement>(null);
    const showTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const [visible, setVisible] = useState(false);
    const [mounted, setMounted] = useState(false);
    const [coords, setCoords] = useState<CSSProperties>({ top: 0, left: 0 });

    const clearShowTimer = useCallback(() => {
        if (showTimerRef.current !== null) {
            clearTimeout(showTimerRef.current);
            showTimerRef.current = null;
        }
    }, []);

    const hide = useCallback(() => {
        clearShowTimer();
        setVisible(false);
    }, [clearShowTimer]);

    const updatePosition = useCallback(() => {
        const triggerEl = triggerRef.current;
        const tooltipEl = tooltipRef.current;
        if (!triggerEl || !tooltipEl) {
            return;
        }
        const triggerRect = triggerEl.getBoundingClientRect();
        const tooltipRect = tooltipEl.getBoundingClientRect();
        const { top, left } = computePosition(triggerRect, tooltipRect, side, align);
        setCoords({ top, left });
    }, [align, side]);

    const show = useCallback(() => {
        if (disabled && !content) {
            return;
        }
        clearShowTimer();
        showTimerRef.current = setTimeout(() => {
            setVisible(true);
            showTimerRef.current = null;
        }, Math.max(0, delay));
    }, [clearShowTimer, content, delay, disabled]);

    useEffect(() => {
        setMounted(true);
        return () => {
            clearShowTimer();
        };
    }, [clearShowTimer]);

    useLayoutEffect(() => {
        if (!visible) {
            return;
        }
        updatePosition();
    }, [visible, content, side, align, updatePosition]);

    useEffect(() => {
        if (!visible) {
            return;
        }

        const onScrollOrResize = () => updatePosition();
        window.addEventListener('scroll', onScrollOrResize, true);
        window.addEventListener('resize', onScrollOrResize);

        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                hide();
            }
        };
        window.addEventListener('keydown', onKeyDown);

        return () => {
            window.removeEventListener('scroll', onScrollOrResize, true);
            window.removeEventListener('resize', onScrollOrResize);
            window.removeEventListener('keydown', onKeyDown);
        };
    }, [hide, updatePosition, visible]);

    const panelClass = `${tooltipContent} ${visible ? tooltipContentVisible : tooltipContentHidden} ${className}`.trim();

    const tooltipNode =
        mounted && visible && content
            ? createPortal(
                  <div
                      ref={tooltipRef}
                      id={tooltipId}
                      role="tooltip"
                      className={panelClass}
                      style={coords}
                  >
                      {content}
                  </div>,
                  document.body,
              )
            : null;

    const eventHandlers = {
        onMouseEnter: show,
        onMouseLeave: hide,
        onFocusCapture: show,
        onBlurCapture: (event: FocusEvent<HTMLSpanElement>) => {
            const root = triggerRef.current;
            if (root && event.relatedTarget instanceof Node && root.contains(event.relatedTarget)) {
                return;
            }
            hide();
        },
    };

    if (disabled) {
        return (
            <>
                <span
                    ref={triggerRef}
                    className={tooltipDisabledWrap}
                    tabIndex={0}
                    aria-describedby={visible ? tooltipId : undefined}
                    {...eventHandlers}
                >
                    {children}
                </span>
                {tooltipNode}
            </>
        );
    }

    let triggerChild: ReactNode = children;
    if (isValidElement(children)) {
        const element = children as ReactElement<{ 'aria-describedby'?: string }>;
        triggerChild = cloneElement(element, {
            'aria-describedby': visible
                ? mergeDescribedBy(element.props['aria-describedby'], tooltipId)
                : element.props['aria-describedby'],
        });
    }

    return (
        <>
            <span ref={triggerRef} className="inline-flex max-w-full" {...eventHandlers}>
                {triggerChild}
            </span>
            {tooltipNode}
        </>
    );
}
