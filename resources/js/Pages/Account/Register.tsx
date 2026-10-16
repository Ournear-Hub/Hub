import Auth from '@/Pages/Account/Auth';

/** Backward-compatible Inertia page — Signup tab on the unified auth screen. */
export default function Register() {
    return <Auth tab="signup" />;
}
