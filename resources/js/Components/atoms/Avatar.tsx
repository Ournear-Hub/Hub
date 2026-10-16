import { useEffect, useState } from 'react';
import {
    avatarImage,
    avatarInitials,
    avatarNeutral,
    avatarRoot,
    avatarShapeCircle,
    avatarShapeSquare,
    avatarSizeLg,
    avatarSizeMd,
    avatarSizeProfileSidebar,
    avatarSizeProfileSummary,
    avatarSizeSm,
    avatarSizeXl,
    avatarSizeXs,
} from '@/design-system/classNames';
import OurNearIcon from '@/icons/OurNearIcon';
import type { IconSize } from '@/icons/types';

export type AvatarSize = 'xs' | 'sm' | 'md' | 'lg' | 'xl' | 'profileSidebar' | 'profileSummary';

export type AvatarShape = 'circle' | 'square';

export type AvatarProps = {
    src?: string;
    alt?: string;
    name?: string;
    size?: AvatarSize;
    shape?: AvatarShape;
    className?: string;
};

const sizeClass: Record<AvatarSize, string> = {
    xs: avatarSizeXs,
    sm: avatarSizeSm,
    md: avatarSizeMd,
    lg: avatarSizeLg,
    xl: avatarSizeXl,
    profileSidebar: avatarSizeProfileSidebar,
    profileSummary: avatarSizeProfileSummary,
};

const shapeClass: Record<AvatarShape, string> = {
    circle: avatarShapeCircle,
    square: avatarShapeSquare,
};

const iconSizeForAvatar: Record<AvatarSize, IconSize> = {
    xs: 'xs',
    sm: 'xs',
    md: 'sm',
    lg: 'md',
    xl: 'lg',
    profileSidebar: 'lg',
    profileSummary: 'md',
};

/** Deterministic initials from a display name (first letter of up to two words). */
function initialsFromName(name: string): string {
    const parts = name.trim().split(/\s+/).filter(Boolean);

    if (parts.length === 0) {
        return '';
    }

    if (parts.length === 1) {
        return parts[0].charAt(0).toUpperCase();
    }

    return (parts[0].charAt(0) + parts[1].charAt(0)).toUpperCase();
}

function hasUsableSrc(src: string | undefined): src is string {
    return typeof src === 'string' && src.trim().length > 0;
}

export default function Avatar({
    src,
    alt,
    name,
    size = 'md',
    shape = 'circle',
    className = '',
}: AvatarProps) {
    const [imageFailed, setImageFailed] = useState(false);

    useEffect(() => {
        setImageFailed(false);
    }, [src]);

    const showImage = hasUsableSrc(src) && !imageFailed;
    const initials = name ? initialsFromName(name) : '';
    const rootClass = `${avatarRoot} ${sizeClass[size]} ${shapeClass[shape]} ${className}`.trim();

    if (showImage) {
        return (
            <span className={rootClass}>
                <img
                    src={src}
                    alt={alt ?? ''}
                    className={avatarImage}
                    onError={() => setImageFailed(true)}
                />
            </span>
        );
    }

    if (initials) {
        return (
            <span className={rootClass} aria-hidden="true">
                <span className={avatarInitials}>{initials}</span>
            </span>
        );
    }

    return (
        <span className={rootClass} aria-hidden="true">
            <span className={avatarNeutral}>
                <OurNearIcon name="user" size={iconSizeForAvatar[size]} decorative />
            </span>
        </span>
    );
}
