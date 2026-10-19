import { useCallback, useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import Button from '@/Components/atoms/Button';
import IconButton from '@/Components/atoms/IconButton';
import PageContainer from '@/Components/Public/PageContainer';
import { focusRing } from '@/design-system/classNames';
import type { SharedPageProps } from '@/types/page';
import { resolvePublicCmsHref } from '@/utils/employerHiringEntry';

export type HomeHeroSlide = {
    title: string;
    body: string | null;
    image_url: string | null;
    primary_label: string | null;
    primary_href: string | null;
    secondary_label: string | null;
    secondary_href: string | null;
    image_object_position?: string | null;
};

type Props = {
    slides: HomeHeroSlide[];
};

function hasPrimaryAction(slide: HomeHeroSlide): boolean {
    return Boolean(slide.primary_label?.trim() && slide.primary_href?.trim());
}

function hasSecondaryAction(slide: HomeHeroSlide): boolean {
    return Boolean(slide.secondary_label?.trim() && slide.secondary_href?.trim());
}

export default function HomeHeroSlider({ slides }: Props) {
    const user = usePage<SharedPageProps>().props.auth.user;
    const items = slides.length > 0 ? slides : [];
    const [index, setIndex] = useState(0);

    useEffect(() => {
        if (items.length === 0) {
            setIndex(0);
            return;
        }
        if (index >= items.length) {
            setIndex(0);
        }
    }, [index, items.length]);

    const slide = items[index] ?? items[0];

    const go = useCallback(
        (next: number) => {
            if (items.length === 0) {
                return;
            }
            setIndex((next + items.length) % items.length);
        },
        [items.length],
    );

    useEffect(() => {
        const onKeyDown = (event: KeyboardEvent) => {
            if (items.length <= 1) {
                return;
            }
            if (event.key === 'ArrowLeft') {
                go(index - 1);
            }
            if (event.key === 'ArrowRight') {
                go(index + 1);
            }
        };
        window.addEventListener('keydown', onKeyDown);

        return () => window.removeEventListener('keydown', onKeyDown);
    }, [go, index, items.length]);

    if (!slide) {
        return null;
    }

    const hasImage = Boolean(slide.image_url);
    const objectPosition = slide.image_object_position?.trim() || 'center center';
    const showTitle = Boolean(slide.title?.trim());
    const showBody = Boolean(slide.body?.trim());
    const showActions = hasPrimaryAction(slide) || hasSecondaryAction(slide);
    const showTextPanel = showTitle || showBody || showActions;

    return (
        <section
            className="relative w-full overflow-hidden border-b border-line bg-brand text-white"
            aria-roledescription="carousel"
            aria-label="Homepage hero"
        >
            <div className="relative flex min-h-[17.5rem] w-full flex-col sm:min-h-[20rem] md:min-h-[25rem] lg:min-h-[28rem]">
                {hasImage ? (
                    <img
                        src={slide.image_url ?? ''}
                        alt=""
                        className="absolute inset-0 h-full w-full object-cover"
                        style={{ objectPosition }}
                        loading="eager"
                    />
                ) : (
                    <div className="absolute inset-0 bg-gradient-to-br from-brand via-brand to-[#1a383c]" aria-hidden="true" />
                )}

                {hasImage ? (
                    <div
                        className="pointer-events-none absolute inset-0 bg-gradient-to-r from-black/55 via-black/25 to-transparent md:from-black/45 md:via-black/15 md:to-transparent"
                        aria-hidden="true"
                    />
                ) : null}

                {items.length > 1 ? (
                    <>
                        <IconButton
                            label="Previous slide"
                            icon="arrow-left"
                            variant="ghost"
                            className={`absolute left-2 top-1/2 z-20 -translate-y-1/2 border border-white/30 bg-black/25 text-white hover:bg-black/40 sm:left-4 md:left-6 ${focusRing}`}
                            onClick={() => go(index - 1)}
                        />
                        <IconButton
                            label="Next slide"
                            icon="arrow-right"
                            variant="ghost"
                            className={`absolute right-2 top-1/2 z-20 -translate-y-1/2 border border-white/30 bg-black/25 text-white hover:bg-black/40 sm:right-4 md:right-6 ${focusRing}`}
                            onClick={() => go(index + 1)}
                        />
                    </>
                ) : null}

                {showTextPanel ? (
                    <PageContainer
                        width="wide"
                        className="relative z-10 flex flex-1 items-end pb-12 pt-16 sm:items-center sm:pb-14 sm:pt-12 lg:pb-16"
                    >
                        <div className="max-w-xl text-left">
                            {showTitle ? (
                                <h1 className="text-2xl font-semibold tracking-tight text-balance drop-shadow-sm sm:text-3xl lg:text-4xl lg:leading-tight">
                                    {slide.title}
                                </h1>
                            ) : null}
                            {showBody ? (
                                <p className="mt-3 max-w-lg text-sm leading-relaxed text-white/92 sm:text-base">{slide.body}</p>
                            ) : null}
                            {showActions ? (
                                <div className="mt-5 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                                    {hasPrimaryAction(slide) ? (
                                        <Button
                                            surface="public"
                                            href={resolvePublicCmsHref(user, slide.primary_href)!}
                                            variant="primary"
                                            className="w-full sm:w-auto"
                                        >
                                            {slide.primary_label!.trim()}
                                        </Button>
                                    ) : null}
                                    {hasSecondaryAction(slide) ? (
                                        <Button
                                            surface="public"
                                            href={resolvePublicCmsHref(user, slide.secondary_href)!}
                                            variant="ghost"
                                            className="w-full border border-white/40 text-white hover:bg-white/10 sm:w-auto"
                                        >
                                            {slide.secondary_label!.trim()}
                                        </Button>
                                    ) : null}
                                </div>
                            ) : null}
                        </div>
                    </PageContainer>
                ) : null}

                {items.length > 1 ? (
                    <div
                        className="absolute bottom-3 left-1/2 z-20 flex -translate-x-1/2 gap-2 sm:bottom-4"
                        aria-label="Hero slides"
                    >
                        {items.map((_, dotIndex) => (
                            <button
                                key={dotIndex}
                                type="button"
                                aria-label={`Show slide ${dotIndex + 1}`}
                                aria-current={dotIndex === index}
                                className={`h-2.5 w-2.5 rounded-full ${dotIndex === index ? 'bg-accent' : 'bg-white/50'} ${focusRing}`}
                                onClick={() => setIndex(dotIndex)}
                            />
                        ))}
                    </div>
                ) : null}
            </div>
        </section>
    );
}
