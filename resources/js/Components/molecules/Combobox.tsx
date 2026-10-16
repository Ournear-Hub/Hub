import type { InputSurface } from '@/Components/atoms/Input';
import {
    adminControlBase,
    comboboxEmpty,
    comboboxListbox,
    comboboxOptionActive,
    comboboxOptionBase,
    comboboxOptionDisabled,
    comboboxOptionSelected,
    controlBase,
    controlError,
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

export type ComboboxOption = {
    value: string;
    label: string;
    disabled?: boolean;
};

export type ComboboxProps = {
    options: ComboboxOption[];
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    disabled?: boolean;
    error?: boolean;
    surface?: InputSurface;
    className?: string;
    id?: string;
    name?: string;
    emptyMessage?: string;
    /** Sticky footer inside the dropdown list (e.g. add master-data action). */
    listFooter?: ReactNode;
    'aria-describedby'?: string;
    'aria-invalid'?: boolean | 'true' | 'false' | 'grammar' | 'spelling';
};

function isAdminFamily(surface: InputSurface): boolean {
    return surface === 'admin' || surface === 'corporate';
}

function controlClasses(surface: InputSurface, error: boolean): string {
    const base = isAdminFamily(surface) ? adminControlBase : controlBase;
    const errorClass = error ? controlError : '';
    return `${base} ${errorClass} pr-9`.trim();
}

function filterOptions(options: ComboboxOption[], query: string): ComboboxOption[] {
    const normalized = query.trim().toLowerCase();
    if (normalized.length === 0) {
        return options;
    }
    return options.filter((option) => option.label.toLowerCase().includes(normalized));
}

function firstEnabledIndex(options: ComboboxOption[]): number | null {
    const index = options.findIndex((option) => !option.disabled);
    return index >= 0 ? index : null;
}

function lastEnabledIndex(options: ComboboxOption[]): number | null {
    for (let index = options.length - 1; index >= 0; index -= 1) {
        if (!options[index].disabled) {
            return index;
        }
    }
    return null;
}

function stepEnabledIndex(options: ComboboxOption[], current: number | null, direction: 1 | -1): number | null {
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

const Combobox = forwardRef<HTMLInputElement, ComboboxProps>(function Combobox(
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

    const selectedOption = useMemo(
        () => options.find((option) => option.value === value) ?? null,
        [options, value],
    );

    const filteredOptions = useMemo(() => filterOptions(options, query), [options, query]);

    const invalid = error || ariaInvalid === true || ariaInvalid === 'true';

    const syncQueryToSelection = useCallback(() => {
        setQuery(selectedOption?.label ?? '');
    }, [selectedOption]);

    const closeListbox = useCallback(() => {
        setOpen(false);
        syncQueryToSelection();
        setActiveIndex(null);
    }, [syncQueryToSelection]);

    const openListbox = useCallback(() => {
        if (disabled) {
            return;
        }
        const initialQuery = selectedOption?.label ?? '';
        const list = filterOptions(options, initialQuery);
        setOpen(true);
        setQuery(initialQuery);
        setActiveIndex(firstEnabledIndex(list));
    }, [disabled, options, selectedOption?.label]);

    useEffect(() => {
        if (!open) {
            syncQueryToSelection();
        }
    }, [open, syncQueryToSelection, value, selectedOption?.label]);

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

    const selectOption = useCallback(
        (option: ComboboxOption) => {
            if (option.disabled) {
                return;
            }
            onChange(option.value);
            setOpen(false);
            setQuery(option.label);
            setActiveIndex(null);
            requestAnimationFrame(() => inputRef.current?.focus());
        },
        [onChange],
    );

    const handleInputChange = (nextQuery: string) => {
        setQuery(nextQuery);
        if (!open) {
            setOpen(true);
        }
        const nextFiltered = filterOptions(options, nextQuery);
        setActiveIndex(firstEnabledIndex(nextFiltered));
    };

    const handleKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (disabled) {
            return;
        }

        switch (event.key) {
            case 'ArrowDown': {
                event.preventDefault();
                if (!open) {
                    setOpen(true);
                    setQuery(selectedOption?.label ?? query);
                    const list = filterOptions(options, selectedOption?.label ?? query);
                    setActiveIndex(firstEnabledIndex(list));
                    return;
                }
                setActiveIndex((current) => stepEnabledIndex(filteredOptions, current, 1));
                break;
            }
            case 'ArrowUp': {
                event.preventDefault();
                if (!open) {
                    setOpen(true);
                    setQuery(selectedOption?.label ?? query);
                    const list = filterOptions(options, selectedOption?.label ?? query);
                    setActiveIndex(lastEnabledIndex(list));
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
                    selectOption(filteredOptions[activeIndex]);
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

    const activeOption =
        open && activeIndex != null && filteredOptions[activeIndex] ? filteredOptions[activeIndex] : null;
    const activeDescendantId = activeOption ? optionElementId(listboxId, activeOption.value) : undefined;

    return (
        <div ref={rootRef} className={`relative ${className}`.trim()}>
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
                placeholder={placeholder}
                className={controlClasses(surface, error)}
                value={open ? query : (selectedOption?.label ?? '')}
                onChange={(event) => handleInputChange(event.currentTarget.value)}
                onFocus={() => {
                    if (!disabled && !open) {
                        openListbox();
                    }
                }}
                onKeyDown={handleKeyDown}
            />
            {name ? <input type="hidden" name={name} value={value} /> : null}
            <span
                className="pointer-events-none absolute right-3 top-1/2 z-[1] -translate-y-1/2 text-muted"
                aria-hidden="true"
            >
                <OurNearIcon name="chevron-down" size="sm" decorative />
            </span>
            {open ? (
                <ul id={listboxId} role="listbox" className={comboboxListbox}>
                    {filteredOptions.length === 0 ? (
                        <li className={comboboxEmpty} role="presentation">
                            {emptyMessage}
                        </li>
                    ) : (
                        filteredOptions.map((option, index) => {
                            const isSelected = option.value === value;
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
                                    onClick={() => selectOption(option)}
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

export default Combobox;
