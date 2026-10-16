import type { TextareaHTMLAttributes } from 'react';
import CanonicalTextarea from '@/Components/atoms/Textarea';

type TextareaProps = TextareaHTMLAttributes<HTMLTextAreaElement>;

/** Admin UI adapter — delegates to canonical Textarea with admin surface. */
export default function Textarea(props: TextareaProps) {
    return <CanonicalTextarea surface="admin" {...props} />;
}
