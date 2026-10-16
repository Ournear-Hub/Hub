import { useCallback, useEffect, useRef, useState } from 'react';
import IndustryCard from '@/Components/Public/IndustryCard';
import PageContainer from '@/Components/Public/PageContainer';
import IconButton from '@/Components/atoms/IconButton';
import { focusRing } from '@/design-system/classNames';

export type HomeIndustry = {
    title: string;
    description?: string | null;
    icon?: string | null;
    href?: string;
};

type Props = {
    industries: HomeIndustry[];
    heading?: string;
    subheading?: string;
};

export default function HomeIndustriesScroller({
    industries,
    heading = 'Industries we serve',
    subheading = 'Sectors where OurNear Hire supports recruitment.',
}: Props) {
    const trackRef = useRef<HTMLDivElement>(null);
    const [canScrollBack, setCanScrollBack] = useState(false);
    const [canScrollForward, setCanScrollForward] = useState(false);

    const syncScrollState = useCallback(() => {
        const track = trackRef.current;
        if (!track) {
            return;
        }
        const maxScroll = track.scrollWidth - track.clientWidth;
        setCanScrollBack(track.scrollLeft > 4);
        setCanScrollForward(track.scrollLeft < maxScroll - 4);
    }, []);

    useEffect(() => {
        syncScrollState();
        const track = trackRef.current;
        if (!track) {
            return;
        }
        track.addEventListener('scroll', syncScrollState, { passive: true });
        window.addEventListener('resize', syncScrollState);

        return () => {
            track.removeEventListener('scroll', syncScrollState);
            window.removeEventListener('resize', syncScrollState);
        };
    }, [industries.length, syncScrollState]);

    const scrollByPage = (direction: -1 | 1) => {
        const track = trackRef.current;
        if (!track) {
            return;
        }
        const card = track.querySelector<HTMLElement>('[data-industry-card]');
        const step = card ? card.offsetWidth + 12 : Math.max(track.clientWidth * 0.75, 240);
        track.scrollBy({ left: direction * step, behavior: 'smooth' });
    };

    if (industries.length === 0) {
        return null;
    }

    return (
        <section className="bg-surface/40 py-8 md:py-10">
            <PageContainer className="min-w-0">
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <div className="min-w-0">
                        <h2 className="text-lg font-semibold text-ink sm:text-xl">{heading}</h2>
                        <p className="mt-1 text-sm text-muted">{subheading}</p>
                    </div>
                    <div className="flex shrink-0 gap-2">
                        <IconButton
                            label="Scroll industries left"
                            icon="arrow-left"
                            variant="secondary"
                            className={`${focusRing} disabled:opacity-40`}
                            disabled={!canScrollBack}
                            onClick={() => scrollByPage(-1)}
                        />
                        <IconButton
                            label="Scroll industries right"
                            icon="arrow-right"
                            variant="secondary"
                            className={`${focusRing} disabled:opacity-40`}
                            disabled={!canScrollForward}
                            onClick={() => scrollByPage(1)}
                        />
                    </div>
                </div>
                <div className="relative -mx-4 mt-5 min-w-0 sm:-mx-5 md:-mx-7 lg:-mx-10">
                    <div
                        ref={trackRef}
                        className="flex gap-3 overflow-x-auto overscroll-x-contain scroll-smooth px-4 pb-2 [-ms-overflow-style:none] [scrollbar-width:thin] sm:px-5 md:px-7 lg:px-10 [&::-webkit-scrollbar]:h-1.5"
                        role="list"
                        aria-label="Industries"
                        tabIndex={0}
                        onKeyDown={(event) => {
                            if (event.key === 'ArrowLeft') {
                                event.preventDefault();
                                scrollByPage(-1);
                            }
                            if (event.key === 'ArrowRight') {
                                event.preventDefault();
                                scrollByPage(1);
                            }
                        }}
                    >
                        {industries.map((industry) => (
                            <div
                                key={industry.title}
                                data-industry-card
                                className="w-[8.75rem] shrink-0 sm:w-40 md:w-44"
                                role="listitem"
                            >
                                <IndustryCard name={industry.title} icon={industry.icon ?? 'briefcase'} />
                            </div>
                        ))}
                    </div>
                </div>
            </PageContainer>
        </section>
    );
}
