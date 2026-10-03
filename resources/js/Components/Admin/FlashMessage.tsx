import { usePage } from '@inertiajs/react';
import type { SharedPageProps } from '@/types/page';

export default function FlashMessage() {
    const { flash } = usePage<SharedPageProps>().props;

    if (!flash.success && !flash.error && !flash.warning && !flash.status) {
        return null;
    }

    return (
        <div className="mb-4 space-y-2">
            {flash.error ? (
                <p className="rounded-md border border-danger/20 bg-danger/10 px-3 py-2 text-sm text-danger">
                    {flash.error}
                </p>
            ) : null}
            {flash.warning ? (
                <p className="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                    {flash.warning}
                </p>
            ) : null}
            {flash.success || flash.status ? (
                <p className="rounded-md border border-success/20 bg-success/10 px-3 py-2 text-sm text-success">
                    {flash.success ?? flash.status}
                </p>
            ) : null}
        </div>
    );
}
