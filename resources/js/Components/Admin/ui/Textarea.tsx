import type { TextareaHTMLAttributes } from 'react';
import { adminControlClass } from '@/Components/Admin/ui/Input';

type TextareaProps = TextareaHTMLAttributes<HTMLTextAreaElement>;

export default function Textarea({ className = '', ...props }: TextareaProps) {
    return <textarea className={`${adminControlClass} min-h-24 ${className}`} {...props} />;
}
