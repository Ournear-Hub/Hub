import {
    detailSectionActionButton,
    detailSectionActions,
    detailSectionBody,
    detailSectionDescription,
    detailSectionHeader,
    detailSectionRoot,
    detailSectionTitle,
} from '@/design-system/classNames';
import { useId, type ReactNode } from 'react';

export type DetailSectionProps = {
    /** Section heading (Detail pattern nested block). */
    title: string;
    description?: string;
    children: ReactNode;
    /** Custom action controls (Edit links, buttons, clusters). */
    actions?: ReactNode;
    /** Profile-style Edit control when `actions` is not supplied. */
    onEdit?: () => void;
    /** Profile-style Add control when `actions` is not supplied. */
    onAdd?: () => void;
    addLabel?: string;
    className?: string;
};

export default function DetailSection({
    title,
    description,
    children,
    actions,
    onEdit,
    onAdd,
    addLabel,
    className = '',
}: DetailSectionProps) {
    const headingId = useId();
    const rootClass = `${detailSectionRoot} ${className}`.trim();
    const showDefaultActions = !actions && (onEdit || onAdd);

    return (
        <section className={rootClass} aria-labelledby={headingId}>
            <div className={detailSectionHeader}>
                <div className="min-w-0">
                    <h2 id={headingId} className={detailSectionTitle}>
                        {title}
                    </h2>
                    {description ? <p className={detailSectionDescription}>{description}</p> : null}
                </div>
                {actions ? <div className={detailSectionActions}>{actions}</div> : null}
                {showDefaultActions ? (
                    <div className={detailSectionActions}>
                        {onAdd ? (
                            <button type="button" className={detailSectionActionButton} onClick={onAdd}>
                                {addLabel ?? 'Add'}
                            </button>
                        ) : null}
                        {onEdit ? (
                            <button type="button" className={detailSectionActionButton} onClick={onEdit}>
                                Edit
                            </button>
                        ) : null}
                    </div>
                ) : null}
            </div>
            <div className={detailSectionBody}>{children}</div>
        </section>
    );
}
