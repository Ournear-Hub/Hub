import { forwardRef, type ImgHTMLAttributes } from 'react';
import {
    imageFitContain,
    imageFitCover,
    imageFitFill,
    imageFitNone,
} from '@/design-system/classNames';

export type ImageFit = 'cover' | 'contain' | 'fill' | 'none';

export type ImageLoading = 'lazy' | 'eager';

export type ImageProps = Omit<
    ImgHTMLAttributes<HTMLImageElement>,
    'src' | 'alt' | 'width' | 'height' | 'loading' | 'className'
> & {
    src: string;
    alt: string;
    width?: number | string;
    height?: number | string;
    fit?: ImageFit;
    loading?: ImageLoading;
    className?: string;
};

const fitClass: Record<ImageFit, string> = {
    cover: imageFitCover,
    contain: imageFitContain,
    fill: imageFitFill,
    none: imageFitNone,
};

const Image = forwardRef<HTMLImageElement, ImageProps>(function Image(
    {
        src,
        alt,
        width,
        height,
        fit = 'cover',
        loading = 'lazy',
        className = '',
        ...rest
    },
    ref,
) {
    const imgClass = `${fitClass[fit]} ${className}`.trim();

    return (
        <img
            ref={ref}
            src={src}
            alt={alt}
            width={width}
            height={height}
            loading={loading}
            className={imgClass}
            {...rest}
        />
    );
});

export default Image;
