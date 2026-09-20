import { usePage } from '@inertiajs/react';
import type { SharedPageProps } from '@/types/page';

export default function FlashMessage() {
    const { flash } = usePage<SharedPageProps>().props;

    if (!flash.success && !flash.error && !flash.status) {
        return null;
    }

    if (flash.error) {
        return (
            <p className="mb-4 rounded-md border border-danger/20 bg-danger/10 px-3 py-2 text-sm text-danger">
                {flash.error}
            </p>
        );
    }

    const message = flash.success ?? flash.status;

    return (
        <p className="mb-4 rounded-md border border-success/20 bg-success/10 px-3 py-2 text-sm text-success">
            {message}
        </p>
    );
}
