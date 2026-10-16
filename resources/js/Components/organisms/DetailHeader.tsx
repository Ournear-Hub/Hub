import Heading from '@/Components/atoms/Heading';
import type { HeadingLevel } from '@/Components/atoms/Heading';
import Stack from '@/Components/atoms/Stack';
import Text from '@/Components/atoms/Text';
import {
    detailHeaderActions,
    detailHeaderMain,
    detailHeaderRoot,
} from '@/design-system/classNames';
import type { ReactNode } from 'react';

export type DetailHeaderProps = {
    /** Primary entity or section title (Detail pattern §12). */
    title: string;
    description?: string;
    eyebrow?: string;
    /** Default 1 for entity detail pages; use 2–3 for nested sections within a detail view. */
    headingLevel?: Extract<HeadingLevel, 1 | 2 | 3>;
    /** Status badges, metadata chips, or summary nodes below the title block. */
    meta?: ReactNode;
    /** Primary/secondary actions (Button, LinkButton, Cluster, etc.) — caller-owned. */
    actions?: ReactNode;
    className?: string;
};

export default function DetailHeader({
    title,
    description,
    eyebrow,
    headingLevel = 1,
    meta,
    actions,
    className = '',
}: DetailHeaderProps) {
    const rootClass = `${detailHeaderRoot} ${className}`.trim();

    return (
        <header className={rootClass}>
            <div className={detailHeaderMain}>
                <Stack gap="xs">
                    {eyebrow ? (
                        <Text as="p" variant="eyebrow">
                            {eyebrow}
                        </Text>
                    ) : null}
                    <Heading level={headingLevel}>{title}</Heading>
                    {description ? (
                        <Text as="p" variant="secondary" className="max-w-2xl">
                            {description}
                        </Text>
                    ) : null}
                    {meta ? <div className="pt-1">{meta}</div> : null}
                </Stack>
            </div>
            {actions ? <div className={detailHeaderActions}>{actions}</div> : null}
        </header>
    );
}
