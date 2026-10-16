import OurNearIcon from '@/icons/OurNearIcon';
import { homeRecruitmentStages } from '@/content/homeRecruitmentStages';

type Props = {
    className?: string;
    tone?: 'light' | 'dark';
    stages?: readonly string[];
};

export default function PublicPipelineStrip({
    className = '',
    tone = 'light',
    stages = homeRecruitmentStages,
}: Props) {
    const isDark = tone === 'dark';

    return (
        <div className={`min-w-0 ${className}`}>
            <ol
                className="flex gap-3 overflow-x-auto pb-1 [-ms-overflow-style:none] [scrollbar-width:thin] lg:overflow-visible lg:pb-0"
                aria-label="Recruitment pipeline stages"
            >
                {stages.map((stage, index) => (
                    <li key={stage} className="flex min-w-[9.5rem] shrink-0 items-stretch gap-2 lg:min-w-0 lg:flex-1">
                        <div
                            className={`flex flex-1 flex-col items-center gap-2 rounded-lg border px-2 py-3 text-center ${
                                isDark
                                    ? 'border-white/20 bg-white/10'
                                    : 'border-brand/15 bg-brand/[0.04] shadow-sm'
                            }`}
                        >
                            <span
                                className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-bold ${
                                    isDark ? 'bg-white/15 text-white' : 'bg-brand text-white ring-2 ring-accent/30'
                                }`}
                                aria-hidden="true"
                            >
                                {index + 1}
                            </span>
                            <span className={`text-xs font-semibold leading-snug ${isDark ? 'text-white' : 'text-brand'}`}>
                                {stage}
                            </span>
                        </div>
                        {index < stages.length - 1 ? (
                            <OurNearIcon
                                name="chevron-right"
                                size="sm"
                                className={`hidden shrink-0 self-center lg:inline ${isDark ? 'text-white/50' : 'text-accent'}`}
                                decorative
                            />
                        ) : null}
                    </li>
                ))}
            </ol>
        </div>
    );
}
