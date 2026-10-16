import {
    segmentedControlRoot,
    segmentedControlSegmentMd,
    segmentedControlSegmentSm,
    segmentedControlSelected,
    segmentedControlUnselected,
} from '@/design-system/classNames';
import {
    useCallback,
    useRef,
    type KeyboardEvent,
    type ReactNode,
} from 'react';

export type SegmentedControlSize = 'sm' | 'md';

export type SegmentedControlOption = {
    value: string;
    label: ReactNode;
    disabled?: boolean;
};

export type SegmentedControlProps = {
    options: SegmentedControlOption[];
    value: string;
    onChange: (value: string) => void;
    /** Required accessible name for the group (no tablist / navigation semantics). */
    'aria-label': string;
    disabled?: boolean;
    size?: SegmentedControlSize;
    className?: string;
    /** Optional hidden input for native form posts (single current value). */
    name?: string;
};

function segmentSizeClass(size: SegmentedControlSize): string {
    return size === 'sm' ? segmentedControlSegmentSm : segmentedControlSegmentMd;
}

export default function SegmentedControl({
    options,
    value,
    onChange,
    'aria-label': ariaLabel,
    disabled = false,
    size = 'md',
    className = '',
    name,
}: SegmentedControlProps) {
    const buttonRefs = useRef<Map<string, HTMLButtonElement>>(new Map());

    const enabledOptions = options.filter((option) => !option.disabled);

    const focusValue = useCallback((optionValue: string) => {
        requestAnimationFrame(() => buttonRefs.current.get(optionValue)?.focus());
    }, []);

    const selectValue = useCallback(
        (optionValue: string) => {
            if (disabled) {
                return;
            }
            const option = options.find((item) => item.value === optionValue);
            if (!option || option.disabled) {
                return;
            }
            onChange(optionValue);
        },
        [disabled, onChange, options],
    );

    const moveSelection = useCallback(
        (direction: 1 | -1) => {
            if (disabled || enabledOptions.length === 0) {
                return;
            }
            const currentIndex = enabledOptions.findIndex((option) => option.value === value);
            const startIndex = currentIndex >= 0 ? currentIndex : 0;
            let nextIndex = startIndex + direction;
            if (nextIndex < 0) {
                nextIndex = enabledOptions.length - 1;
            }
            if (nextIndex >= enabledOptions.length) {
                nextIndex = 0;
            }
            const next = enabledOptions[nextIndex];
            if (next) {
                onChange(next.value);
                focusValue(next.value);
            }
        },
        [disabled, enabledOptions, focusValue, onChange, value],
    );

    const handleGroupKeyDown = (event: KeyboardEvent<HTMLDivElement>) => {
        if (disabled) {
            return;
        }

        switch (event.key) {
            case 'ArrowRight':
            case 'ArrowDown':
                event.preventDefault();
                moveSelection(1);
                break;
            case 'ArrowLeft':
            case 'ArrowUp':
                event.preventDefault();
                moveSelection(-1);
                break;
            case 'Home':
                event.preventDefault();
                if (enabledOptions[0]) {
                    onChange(enabledOptions[0].value);
                    focusValue(enabledOptions[0].value);
                }
                break;
            case 'End':
                event.preventDefault();
                if (enabledOptions.length > 0) {
                    const last = enabledOptions[enabledOptions.length - 1];
                    onChange(last.value);
                    focusValue(last.value);
                }
                break;
            default:
                break;
        }
    };

    const rootClass = `${segmentedControlRoot} ${className}`.trim();
    const segmentBase = segmentSizeClass(size);

    return (
        <div
            role="radiogroup"
            aria-label={ariaLabel}
            aria-disabled={disabled ? true : undefined}
            className={rootClass}
            onKeyDown={handleGroupKeyDown}
        >
            {options.map((option) => {
                const selected = value === option.value;
                const isDisabled = disabled || option.disabled;
                const stateClass = selected ? segmentedControlSelected : segmentedControlUnselected;
                const segmentClass = `${segmentBase} ${stateClass} ${
                    isDisabled ? 'cursor-not-allowed opacity-50' : ''
                }`.trim();

                return (
                    <button
                        key={option.value}
                        ref={(node) => {
                            if (node) {
                                buttonRefs.current.set(option.value, node);
                            } else {
                                buttonRefs.current.delete(option.value);
                            }
                        }}
                        type="button"
                        role="radio"
                        aria-checked={selected}
                        disabled={isDisabled}
                        tabIndex={selected ? 0 : -1}
                        className={segmentClass}
                        onClick={() => selectValue(option.value)}
                    >
                        {option.label}
                    </button>
                );
            })}
            {name ? <input type="hidden" name={name} value={value} readOnly /> : null}
        </div>
    );
}
