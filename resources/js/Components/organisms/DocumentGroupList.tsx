import Card from '@/Components/atoms/Card';
import Stack from '@/Components/atoms/Stack';
import Text from '@/Components/atoms/Text';
import EmptyState from '@/Components/molecules/EmptyState';
import {
    documentGroupListActions,
    documentGroupListExtra,
    documentGroupListGroupLabel,
    documentGroupListIdentity,
    documentGroupListItemTitle,
    documentGroupListList,
    documentGroupListRoot,
} from '@/design-system/classNames';
import type { ReactNode } from 'react';

export type DocumentGroupItem = {
    id: string | number;
    group: string;
    title: string;
    meta?: string | null;
    identity?: string | null;
    /** Caller-provided links/buttons/forms — file lifecycle stays on the page (File System). */
    actions?: ReactNode;
    /** Optional replace/upload UI supplied by the page — not rendered by default templates. */
    extra?: ReactNode;
};

export type DocumentGroupListProps = {
    items: DocumentGroupItem[];
    /** Shown when `items` is empty (display copy only). */
    empty: string;
    className?: string;
};

function groupItems(items: DocumentGroupItem[]): Record<string, DocumentGroupItem[]> {
    return items.reduce<Record<string, DocumentGroupItem[]>>((carry, item) => {
        const key = item.group || 'Other';
        carry[key] = [...(carry[key] ?? []), item];
        return carry;
    }, {});
}

export default function DocumentGroupList({ items, empty, className = '' }: DocumentGroupListProps) {
    if (items.length === 0) {
        return (
            <EmptyState align="start" size="sm" description={empty} className={className} />
        );
    }

    const groups = groupItems(items);
    const rootClass = `${documentGroupListRoot} ${className}`.trim();

    return (
        <div className={rootClass}>
            {Object.entries(groups).map(([group, groupItemsForSection]) => (
                <section key={group} aria-labelledby={`document-group-${group}`}>
                    <h3 id={`document-group-${group}`} className={documentGroupListGroupLabel}>
                        {group}
                    </h3>
                    <ul className={documentGroupListList}>
                        {groupItemsForSection.map((item) => (
                            <li key={item.id}>
                                <Card padding="standard">
                                    <Stack gap="xs">
                                        {item.identity ? (
                                            <Text as="p" variant="caption" className={documentGroupListIdentity}>
                                                {item.identity}
                                            </Text>
                                        ) : null}
                                        <p className={documentGroupListItemTitle}>{item.title}</p>
                                        {item.meta ? (
                                            <Text as="p" variant="secondary">
                                                {item.meta}
                                            </Text>
                                        ) : null}
                                        {item.actions ? (
                                            <div className={documentGroupListActions}>{item.actions}</div>
                                        ) : null}
                                        {item.extra ? <div className={documentGroupListExtra}>{item.extra}</div> : null}
                                    </Stack>
                                </Card>
                            </li>
                        ))}
                    </ul>
                </section>
            ))}
        </div>
    );
}
