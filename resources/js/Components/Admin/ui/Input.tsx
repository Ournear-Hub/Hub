import type { InputHTMLAttributes } from 'react';
import CanonicalInput from '@/Components/atoms/Input';
import { adminControlBase } from '@/design-system/classNames';

/** @deprecated Import from classNames — kept for existing barrel consumers. */
export const adminControlClass = adminControlBase;

type InputProps = InputHTMLAttributes<HTMLInputElement>;

/** Admin UI adapter — delegates to canonical Input with admin surface. */
export default function Input(props: InputProps) {
    return <CanonicalInput surface="admin" {...props} />;
}
