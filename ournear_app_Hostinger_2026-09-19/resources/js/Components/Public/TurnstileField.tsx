import { useEffect, useId, useRef } from 'react';
import { usePage } from '@inertiajs/react';
import type { SharedPageProps } from '@/types/page';

type TurnstileFieldProps = {
    onToken: (token: string) => void;
};

type TurnstileApi = {
    render: (
        element: HTMLElement,
        options: {
            sitekey: string;
            callback: (token: string) => void;
            'expired-callback': () => void;
            'error-callback': () => void;
        },
    ) => string;
};

function turnstileApi(): TurnstileApi | undefined {
    return (window as unknown as { turnstile?: TurnstileApi }).turnstile;
}

export default function TurnstileField({ onToken }: TurnstileFieldProps) {
    const siteKey = usePage<SharedPageProps>().props.turnstileSiteKey ?? null;
    const hostId = useId();
    const widgetId = useRef<string | null>(null);
    const onTokenRef = useRef(onToken);
    onTokenRef.current = onToken;

    useEffect(() => {
        if (!siteKey) {
            return;
        }

        const deliver = (token: string) => onTokenRef.current(token);

        const render = () => {
            const host = document.getElementById(hostId);
            const api = turnstileApi();
            if (!host || !api || widgetId.current) {
                return;
            }

            widgetId.current = api.render(host, {
                sitekey: siteKey,
                callback: deliver,
                'expired-callback': () => deliver(''),
                'error-callback': () => deliver(''),
            });
        };

        if (turnstileApi()) {
            render();
            return;
        }

        const existing = document.querySelector<HTMLScriptElement>('script[data-turnstile="1"]');
        if (existing) {
            existing.addEventListener('load', render);
            return () => existing.removeEventListener('load', render);
        }

        const script = document.createElement('script');
        script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
        script.async = true;
        script.dataset.turnstile = '1';
        script.addEventListener('load', render);
        document.head.appendChild(script);

        return () => {
            script.removeEventListener('load', render);
        };
    }, [hostId, siteKey]);

    if (!siteKey) {
        return null;
    }

    return <div id={hostId} className="mt-2" />;
}
