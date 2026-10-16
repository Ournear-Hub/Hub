import type { InputSurface } from '@/Components/atoms/Input';
import {
    comboboxEmpty,
    comboboxListbox,
    comboboxOptionActive,
    comboboxOptionBase,
    comboboxOptionDisabled,
    comboboxOptionSelected,
    focusRing,
    multiSelectChip,
    multiSelectChipLabel,
    multiSelectChipRemove,
    multiSelectControl,
    multiSelectControlError,
    multiSelectInput,
} from '@/design-system/classNames';
import OurNearIcon from '@/icons/OurNearIcon';
import {
    forwardRef,
    useCallback,
    useEffect,
    useId,
    useImperativeHandle,
    useMemo,
    useRef,
    useState,
    type KeyboardEvent,
    type ReactNode,
} from 'react';

export type MultiSelectOption = {
    value: string;
    label: string;
    disabled?: boolean;
};

export type MultiSelectProps = {
    options: MultiSelectOption[];
    value: string[];
    onChange: (values: string[]) => void;
    placeholder?: string;
    disabled?: boolean;
    error?: boolean;
    surface?: InputSurface;
    className?: string;
    id?: string;
    /** Renders one hidden input per selected value (`name[]` for Laravel). */
    name?: string;
    emptyMessage?: string;
    listFooter?: ReactNode;
    'aria-describedby'?: string;
    'aria-invalid'?: boolean | 'true' | 'false' | 'grammar' | 'spelling';
};

function filterOptions(options: MultiSelectOption[], query: string): MultiSelectOption[] {
    const normalized = query.trim().toLowerCase();
    if (normalized.length === 0) {
        return options;
    }
    return options.filter((option) => option.label.toLowerCase().includes(normalized));
}

function firstEnabledIndex(options: MultiSelectOption[]): number | null {
    const index = options.findIndex((option) => !option.disabled);
    return index >= 0 ? index : null;
}

function lastEnabledIndex(options: MultiSelectOption[]): number | null {
    for (let index = options.length - 1; index >= 0; index -= 1) {
        if (!options[index].disabled) {
            return index;
        }
    }
    return null;
}

function stepEnabledIndex(options: MultiSelectOption[], current: number | null, direction: 1 | -1): number | null {
    if (options.length === 0) {
        return null;
    }

    let index = current ?? (direction === 1 ? -1 : options.length);
    for (let attempts = 0; attempts < options.length; attempts += 1) {
        index += direction;
        if (index < 0) {
            index = options.length - 1;
        }
        if (index >= options.length) {
            index = 0;
        }
        if (!options[index].disabled) {
            return index;
        }
    }
    return null;
}

function optionElementId(listboxId: string, value: string): string {
    return `${listboxId}-opt-${value.replace(/[^a-zA-Z0-9_-]/g, '_')}`;
}

function hiddenInputName(name: string): string {
    return name.endsWith('[]') ? name : `${name}[]`;
}

const MultiSelect = forwardRef<HTMLInputElement, MultiSelectProps>(function MultiSelect(
    {
        options,
        value,
        onChange,
        placeholder,
        disabled = false,
        error = false,
        surface = 'public',
        className = '',
        id,
        name,
        emptyMessage = 'No results',
        listFooter,
        'aria-describedby': ariaDescribedBy,
        'aria-invalid': ariaInvalid,
    },
    ref,
) {
    const listboxId = useId();
    const rootRef = useRef<HTMLDivElement>(null);
    const inputRef = useRef<HTMLInputElement>(null);
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [activeIndex, setActiveIndex] = useState<number | null>(null);

    useImperativeHandle(ref, () => inputRef.current as HTMLInputElement);

    const labelByValue = useMemo(() => {
        const map = new Map<string, string>();
        options.forEach((option) => map.set(option.value, option.label));
        return map;
    }, [options]);

    const filteredOptions = useMemo(() => filterOptions(options, query), [options, query]);

    const invalid = error || ariaInvalid === true || ariaInvalid === 'true';

    const closeListbox = useCallback(() => {
        setOpen(false);
        setQuery('');
        setActiveIndex(null);
    }, []);

    const openListbox = useCallback(() => {
        if (disabled) {
            return;
        }
        setOpen(true);
        setQuery('');
        setActiveIndex(firstEnabledIndex(filterOptions(options, '')));
    }, [disabled, options]);

    useEffect(() => {
        if (!open) {
            return;
        }
        function handlePointerDown(event: MouseEvent) {
            if (!rootRef.current?.contains(event.target as Node)) {
                closeListbox();
            }
        }
        document.addEventListener('mousedown', handlePointerDown);
        return () => document.removeEventListener('mousedown', handlePointerDown);
    }, [open, closeListbox]);

    useEffect(() => {
        if (!open || activeIndex == null) {
            return;
        }
        const active = filteredOptions[activeIndex];
        if (!active) {
            return;
        }
        const node = document.getElementById(optionElementId(listboxId, active.value));
        node?.scrollIntoView({ block: 'nearest' });
    }, [activeIndex, filteredOptions, listboxId, open]);

    const toggleOption = useCallback(
        (option: MultiSelectOption) => {
            if (option.disabled) {
                return;
            }
            const selected = value.includes(option.value);
            const next = selected ? value.filter((item) => item !== option.value) : [...value, option.value];
            onChange(next);
            setQuery('');
            setActiveIndex(firstEnabledIndex(filterOptions(options, '')));
            requestAnimationFrame(() => inputRef.current?.focus());
        },
        [onChange, options, value],
    );

    const removeValue = useCallback(
        (optionValue: string) => {
            onChange(value.filter((item) => item !== optionValue));
            requestAnimationFrame(() => inputRef.current?.focus());
        },
        [onChange, value],
    );

    const handleInputChange = (nextQuery: string) => {
        setQuery(nextQuery);
        if (!open) {
            setOpen(true);
        }
        setActiveIndex(firstEnabledIndex(filterOptions(options, nextQuery)));
    };

    const handleKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (disabled) {
            return;
        }

        switch (event.key) {
            case 'ArrowDown': {
                event.preventDefault();
                if (!open) {
                    openListbox();
                    return;
                }
                setActiveIndex((current) => stepEnabledIndex(filteredOptions, current, 1));
                break;
            }
            case 'ArrowUp': {
                event.preventDefault();
                if (!open) {
                    openListbox();
                    setActiveIndex(lastEnabledIndex(filterOptions(options, '')));
                    return;
                }
                setActiveIndex((current) => stepEnabledIndex(filteredOptions, current, -1));
                break;
            }
            case 'Enter': {
                if (!open) {
                    return;
                }
                event.preventDefault();
                if (activeIndex != null && filteredOptions[activeIndex]) {
                    toggleOption(filteredOptions[activeIndex]);
                }
                break;
            }
            case ' ': {
                if (!open) {
                    return;
                }
                event.preventDefault();
                if (activeIndex != null && filteredOptions[activeIndex]) {
                    toggleOption(filteredOptions[activeIndex]);
                }
                break;
            }
            case 'Escape': {
                if (!open) {
                    return;
                }
                event.preventDefault();
                closeListbox();
                break;
            }
            case 'Home': {
                if (!open) {
                    return;
                }
                event.preventDefault();
                setActiveIndex(firstEnabledIndex(filteredOptions));
                break;
            }
            case 'End': {
                if (!open) {
                    return;
                }
                event.preventDefault();
                setActiveIndex(lastEnabledIndex(filteredOptions));
                break;
            }
            case 'Tab': {
                if (open) {
                    closeListbox();
                }
                break;
            }
            default:
                break;
        }
    };

    const showPlaceholder = value.length === 0 && query.length === 0;

    const controlClasses = [
        multiSelectControl,
        focusRing,
        error ? multiSelectControlError : '',
        disabled ? 'cursor-not-allowed bg-surface opacity-60' : '',
    ]
        .filter(Boolean)
        .join(' ');

    const activeOption =
        open && activeIndex != null && filteredOptions[activeIndex] ? filteredOptions[activeIndex] : null;
    const activeDescendantId = activeOption ? optionElementId(listboxId, activeOption.value) : undefined;

    const hiddenName = name ? hiddenInputName(name) : null;

    return (
        <div ref={rootRef} className={`relative ${className}`.trim()}>
            <div
                className={controlClasses}
                onClick={() => {
                    if (!disabled) {
                        inputRef.current?.focus();
                        if (!open) {
                            openListbox();
                        }
                    }
                }}
            >
                {value.map((selectedValue) => {
                    const chipLabel = labelByValue.get(selectedValue) ?? selectedValue;
                    return (
                        <span key={selectedValue} className={multiSelectChip}>
                            <span className={multiSelectChipLabel}>{chipLabel}</span>
                            {!disabled ? (
                                <button
                                    type="button"
                                    className={multiSelectChipRemove}
                                    aria-label={`Remove ${chipLabel}`}
                                    onClick={(event) => {
                                        event.stopPropagation();
                                        removeValue(selectedValue);
                                    }}
                                >
                                    <OurNearIcon name="close" size="xs" decorative />
                                </button>
                            ) : null}
                        </span>
                    );
                })}
                <input
                    ref={inputRef}
                    id={id}
                    type="text"
                    role="combobox"
                    autoComplete="off"
                    aria-autocomplete="list"
                    aria-expanded={open}
                    aria-controls={listboxId}
                    aria-activedescendant={activeDescendantId}
                    aria-describedby={ariaDescribedBy}
                    aria-invalid={invalid ? true : ariaInvalid}
                    disabled={disabled}
                    placeholder={showPlaceholder ? placeholder : undefined}
                    className={multiSelectInput}
                    value={query}
                    onChange={(event) => handleInputChange(event.currentTarget.value)}
                    onFocus={() => {
                        if (!disabled && !open) {
                            openListbox();
                        }
                    }}
                    onKeyDown={handleKeyDown}
                />
                <span className="pointer-events-none shrink-0 pr-1 text-muted" aria-hidden="true">
                    <OurNearIcon name="chevron-down" size="sm" decorative />
                </span>
            </div>
            {hiddenName
                ? value.map((selectedValue) => (
                      <input key={selectedValue} type="hidden" name={hiddenName} value={selectedValue} />
                  ))
                : null}
            {open ? (
                <ul id={listboxId} role="listbox" aria-multiselectable={true} className={comboboxListbox}>
                    {filteredOptions.length === 0 ? (
                        <li className={comboboxEmpty} role="presentation">
                            {emptyMessage}
                        </li>
                    ) : (
                        filteredOptions.map((option, index) => {
                            const isSelected = value.includes(option.value);
                            const isActive = index === activeIndex;
                            const optionClasses = [
                                comboboxOptionBase,
                                option.disabled ? comboboxOptionDisabled : 'cursor-pointer',
                                isActive ? comboboxOptionActive : '',
                                isSelected ? comboboxOptionSelected : '',
                            ]
                                .filter(Boolean)
                                .join(' ');

                            return (
                                <li
                                    key={option.value}
                                    id={optionElementId(listboxId, option.value)}
                                    role="option"
                                    aria-selected={isSelected}
                                    aria-disabled={option.disabled ? true : undefined}
                                    className={optionClasses}
                                    onMouseDown={(event) => event.preventDefault()}
                                    onMouseEnter={() => {
                                        if (!option.disabled) {
                                            setActiveIndex(index);
                                        }
                                    }}
                                    onClick={() => toggleOption(option)}
                                >
                                    {option.label}
                                </li>
                            );
                        })
                    )}
                    {listFooter ? (
                        <li role="presentation" className="border-t border-line bg-white p-2">
                            {listFooter}
                        </li>
                    ) : null}
                </ul>
            ) : null}
        </div>
    );
});

export default MultiSelect;
