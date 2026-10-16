import { Head, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { resolveAuthSymbolUrl, resolveAuthWordmarkUrl, type PublicBrandingProps } from '@/lib/brandAssets';
import type { SharedPageProps } from '@/types/page';

type Props = {
    title: string;
    layout?: 'login' | 'signup';
    children: ReactNode;
};

export default function AuthExperienceLayout({ title, layout = 'login', children }: Props) {
    const branding = usePage<SharedPageProps>().props.branding as PublicBrandingProps | undefined;
    const symbol = resolveAuthSymbolUrl(branding);
    const wordmark = resolveAuthWordmarkUrl(branding);
    const name = branding?.website_name?.trim() || 'OurNear';
    const isLogin = layout === 'login';

    return (
        <div className="min-h-screen bg-[#F7F9F9] text-ink">
            <Head title={title} />
            <div className="flex min-h-screen min-w-0 flex-col items-center justify-center overflow-y-auto px-4 py-8 sm:px-6">
                <div
                    className={`w-full rounded-xl border border-line/80 bg-white shadow-sm ${
                        isLogin ? 'max-w-[640px] p-7 sm:p-10' : 'max-w-[480px] p-6 sm:p-8'
                    }`}
                >
                    <div
                        className={`mb-6 flex flex-col items-center justify-center gap-4 ${isLogin ? 'sm:gap-5' : 'gap-3'}`}
                    >
                        <img
                            src={symbol}
                            alt=""
                            className={
                                isLogin
                                    ? 'h-16 w-auto max-h-20 object-contain sm:h-20'
                                    : 'h-10 w-auto max-h-12 object-contain'
                            }
                            width={80}
                            height={80}
                        />
                        <img
                            src={wordmark}
                            alt={name}
                            className={
                                isLogin
                                    ? 'h-10 w-auto max-w-[min(100%,22rem)] object-contain sm:h-12 sm:max-w-[26rem]'
                                    : 'h-8 w-auto max-w-[12rem] object-contain'
                            }
                            width={260}
                            height={48}
                        />
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
