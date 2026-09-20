import { usePage } from '@inertiajs/react';
import PageContainer from '@/Components/Public/PageContainer';
import type { SharedPageProps } from '@/types/page';

export default function SocialBar() {
    const socials = usePage<SharedPageProps>().props.socialLinks ?? [];

    return (
        <div className="bg-brand text-white">
            <PageContainer className="flex flex-wrap items-center justify-between gap-2 py-2 text-xs">
                <p>Right People. Right Training. Right Results.</p>
                {socials.length > 0 ? (
                    <div className="flex flex-wrap items-center gap-3">
                        <span className="text-white/80">Follow Us</span>
                        <ul className="flex flex-wrap gap-4">
                            {socials.map((item) => (
                                <li key={item.platform}>
                                    <a href={item.href} className="hover:text-accent" target="_blank" rel="noreferrer">
                                        {item.label}
                                    </a>
                                </li>
                            ))}
                        </ul>
                    </div>
                ) : null}
            </PageContainer>
        </div>
    );
}
