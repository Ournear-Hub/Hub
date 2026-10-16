import { usePage } from '@inertiajs/react';
import type { SharedPageProps } from '@/types/page';

/** Server-detected mobile browser scope: profile pages are view-only. */
export function useMobileProfileViewOnly(): boolean {
    return usePage<SharedPageProps>().props.ui?.mobile_profile_view_only === true;
}
