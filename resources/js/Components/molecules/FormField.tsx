import Checkbox from '@/Components/atoms/Checkbox';
import Input from '@/Components/atoms/Input';
import Label from '@/Components/atoms/Label';
import Radio from '@/Components/atoms/Radio';
import Select from '@/Components/atoms/Select';
import Textarea from '@/Components/atoms/Textarea';
import SearchField from '@/Components/molecules/SearchField';
import PasswordField from '@/Components/molecules/PasswordField';
import FileField from '@/Components/molecules/FileField';
import DateField from '@/Components/molecules/DateField';
import TimeField from '@/Components/molecules/TimeField';
import DateTimeField from '@/Components/molecules/DateTimeField';
import NumberField from '@/Components/molecules/NumberField';
import EmailField from '@/Components/molecules/EmailField';
import URLField from '@/Components/molecules/URLField';
import Combobox from '@/Components/molecules/Combobox';
import MultiSelect from '@/Components/molecules/MultiSelect';
import { isInputGroupElement } from '@/Components/molecules/InputGroup';
import { errorText, helperText } from '@/design-system/classNames';
import {
    Children,
    cloneElement,
    isValidElement,
    type ReactElement,
    type ReactNode,
} from 'react';

export type FormFieldProps = {
    id: string;
    label?: ReactNode;
    hint?: ReactNode;
    error?: ReactNode;
    required?: boolean;
    children: ReactNode;
    className?: string;
};

function hasContent(node: ReactNode): boolean {
    if (node === null || node === undefined || node === false) {
        return false;
    }
    if (typeof node === 'string') {
        return node.trim().length > 0;
    }
    return true;
}

function mergeDescribedBy(existing: string | undefined, ids: string[]): string | undefined {
    const parts = new Set<string>();
    if (existing) {
        existing
            .split(/\s+/)
            .filter(Boolean)
            .forEach((part) => parts.add(part));
    }
    ids.forEach((part) => parts.add(part));
    const merged = [...parts].join(' ');
    return merged.length > 0 ? merged : undefined;
}

const controlsWithErrorProp = new Set([
    Input,
    Textarea,
    Select,
    Checkbox,
    Radio,
    SearchField,
    PasswordField,
    FileField,
    DateField,
    TimeField,
    DateTimeField,
    NumberField,
    EmailField,
    URLField,
    Combobox,
    MultiSelect,
]);

function supportsVisualError(type: unknown): boolean {
    return typeof type === 'function' && controlsWithErrorProp.has(type as typeof Input);
}

function augmentControl(
    child: ReactElement<{
        id?: string;
        'aria-describedby'?: string;
        'aria-invalid'?: boolean | 'true' | 'false' | 'grammar' | 'spelling';
        error?: boolean;
    }>,
    id: string,
    describedByIds: string[],
    hasError: boolean,
): ReactElement {
    const patch: Record<string, unknown> = {};

    if (child.props.id == null) {
        patch.id = id;
    }

    if (describedByIds.length > 0) {
        patch['aria-describedby'] = mergeDescribedBy(child.props['aria-describedby'], describedByIds);
    }

    if (hasError) {
        patch['aria-invalid'] = true;
        if (supportsVisualError(child.type)) {
            patch.error = true;
        }
    }

    if (Object.keys(patch).length === 0) {
        return child;
    }

    return cloneElement(child, patch);
}

export default function FormField({
    id,
    label,
    hint,
    error,
    required = false,
    children,
    className = '',
}: FormFieldProps) {
    const hintId = `${id}-hint`;
    const errorId = `${id}-error`;
    const hasError = hasContent(error);
    const showHint = !hasError && hasContent(hint);

    const describedByIds: string[] = [];
    if (showHint) {
        describedByIds.push(hintId);
    }
    if (hasError) {
        describedByIds.push(errorId);
    }

    let control = children;
    const child = Children.only(children);
    if (isValidElement(child)) {
        if (isInputGroupElement(child)) {
            const inner = Children.only(child.props.children);
            if (isValidElement(inner)) {
                const augmentedInner = augmentControl(
                    inner as ReactElement<{
                        id?: string;
                        'aria-describedby'?: string;
                        'aria-invalid'?: boolean | 'true' | 'false' | 'grammar' | 'spelling';
                        error?: boolean;
                    }>,
                    id,
                    describedByIds,
                    hasError,
                );
                control = cloneElement(child, {}, augmentedInner);
            }
        } else {
            control = augmentControl(
                child as ReactElement<{
                    id?: string;
                    'aria-describedby'?: string;
                    'aria-invalid'?: boolean | 'true' | 'false' | 'grammar' | 'spelling';
                    error?: boolean;
                }>,
                id,
                describedByIds,
                hasError,
            );
        }
    }

    return (
        <div className={className}>
            {hasContent(label) ? (
                <Label htmlFor={id} required={required}>
                    {label}
                </Label>
            ) : null}
            {control}
            {hasError ? (
                <p id={errorId} className={errorText} role="alert">
                    {error}
                </p>
            ) : showHint ? (
                <p id={hintId} className={helperText}>
                    {hint}
                </p>
            ) : null}
        </div>
    );
}
