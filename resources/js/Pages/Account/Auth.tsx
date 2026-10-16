import { Link, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState, type FormEvent } from 'react';
import PasswordStrength from '@/Components/Account/PasswordStrength';
import PasswordInputWithToggle from '@/Components/Account/PasswordInputWithToggle';
import UsernamePrefixedInput from '@/Components/Account/UsernamePrefixedInput';
import Button from '@/Components/atoms/Button';
import FormField from '@/Components/Public/FormField';
import AuthExperienceLayout from '@/Layouts/AuthExperienceLayout';
import { authErrorText, focusRing } from '@/design-system/classNames';
import type { SharedPageProps } from '@/types/page';

type AuthTab = 'login' | 'signup';

type AuthProps = {
    tab?: AuthTab;
    registeredEmail?: string | null;
};

const AUTH_APPEARANCE = 'auth' as const;

const fieldGroupClass = 'space-y-[1.125rem]';

const primaryButtonClass = 'min-h-[48px] rounded-lg';

export default function Auth({ tab: initialTab = 'login', registeredEmail = null }: AuthProps) {
    const { flash } = usePage<SharedPageProps>().props;
    const tab: AuthTab = initialTab === 'signup' ? 'signup' : 'login';
    const [usernameAvailable, setUsernameAvailable] = useState<boolean | null>(null);
    const statusMessage = flash.status ?? null;

    const loginForm = useForm({
        login: registeredEmail ?? '',
        password: '',
        remember: false,
    });

    const signupForm = useForm({
        name: '',
        username: '',
        email: '',
        mobile: '',
        password: '',
        password_confirmation: '',
        accept_terms: false,
    });

    useEffect(() => {
        const username = signupForm.data.username.trim();
        if (username.length < 3) {
            setUsernameAvailable(null);

            return;
        }

        const handle = window.setTimeout(() => {
            void fetch(`/register/check-username?username=${encodeURIComponent(username)}`)
                .then(async (response) => {
                    if (!response.ok) {
                        setUsernameAvailable(false);

                        return;
                    }

                    const payload = (await response.json()) as { available?: boolean };
                    setUsernameAvailable(Boolean(payload.available));
                })
                .catch(() => setUsernameAvailable(null));
        }, 350);

        return () => window.clearTimeout(handle);
    }, [signupForm.data.username]);

    const submitLogin = (event: FormEvent) => {
        event.preventDefault();
        loginForm.post('/login');
    };

    const submitSignup = (event: FormEvent) => {
        event.preventDefault();
        signupForm.post('/register');
    };

    const passwordsMatch =
        signupForm.data.password_confirmation.length > 0 &&
        signupForm.data.password === signupForm.data.password_confirmation;

    return (
        <AuthExperienceLayout title={tab === 'signup' ? 'Signup' : 'Login'} layout={tab === 'signup' ? 'signup' : 'login'}>
            {statusMessage ? (
                <p
                    className="mb-4 rounded-lg border border-success/20 bg-success/10 px-3 py-2 text-sm text-success"
                    role="status"
                    aria-live="polite"
                >
                    {statusMessage}
                </p>
            ) : null}

            {tab === 'login' ? (
                <>
                    <h1 className="text-center text-[1.375rem] font-semibold leading-tight text-ink">Welcome back</h1>
                    <p className="mt-1 text-center text-sm text-muted">Sign in to your OurNear Unified Account.</p>
                    <form onSubmit={submitLogin} className={`mt-6 ${fieldGroupClass}`}>
                        <UsernamePrefixedInput
                            appearance={AUTH_APPEARANCE}
                            id="login"
                            name="login"
                            label="Username or email address"
                            placeholder="Enter your username or email"
                            autoComplete="username"
                            required
                            value={loginForm.data.login}
                            onChange={(event) => loginForm.setData('login', event.target.value)}
                            error={loginForm.errors.login}
                        />
                        <PasswordInputWithToggle
                            appearance={AUTH_APPEARANCE}
                            id="password"
                            name="password"
                            label="Password"
                            placeholder="Enter your password"
                            autoComplete="current-password"
                            required
                            value={loginForm.data.password}
                            onChange={(event) => loginForm.setData('password', event.target.value)}
                            error={loginForm.errors.password}
                        />
                        <div className="flex flex-wrap items-center justify-between gap-3 pt-0.5">
                            <label className="flex cursor-pointer items-center gap-2 text-xs text-muted">
                                <input
                                    type="checkbox"
                                    checked={loginForm.data.remember}
                                    onChange={(event) => loginForm.setData('remember', event.target.checked)}
                                    className="h-4 w-4 rounded border-[#D5DFE0] accent-accent"
                                />
                                Remember me
                            </label>
                            <Link href="/forgot-password" className={`text-xs font-semibold text-accent hover:underline ${focusRing}`}>
                                Forgot password?
                            </Link>
                        </div>
                        <Button
                            type="submit"
                            surface="public"
                            variant="primary"
                            fullWidth
                            className={primaryButtonClass}
                            loading={loginForm.processing}
                            disabled={loginForm.processing}
                        >
                            Login
                        </Button>
                    </form>
                    <p className="mt-6 text-center text-sm text-muted">
                        New to OurNear?{' '}
                        <Link href="/register" className={`font-semibold text-brand hover:underline ${focusRing}`}>
                            Create an account
                        </Link>
                    </p>
                </>
            ) : (
                <>
                    <h1 className="text-center text-[1.375rem] font-semibold leading-tight text-ink">Create Your OurNear Unified Account</h1>
                    <p className="mt-1 text-center text-sm text-muted">
                        Create your OurNear Unified Account to find opportunities or hire talent.
                    </p>
                    <form onSubmit={submitSignup} className={`mt-6 ${fieldGroupClass}`}>
                        <FormField
                            appearance={AUTH_APPEARANCE}
                            id="name"
                            label="Full name"
                            name="name"
                            placeholder="Enter your full name"
                            autoComplete="name"
                            required
                            value={signupForm.data.name}
                            onChange={(event) => signupForm.setData('name', event.target.value)}
                            error={signupForm.errors.name}
                        />
                        <div>
                            <UsernamePrefixedInput
                                appearance={AUTH_APPEARANCE}
                                id="username"
                                name="username"
                                label="Username"
                                placeholder="Choose a unique username"
                                autoComplete="username"
                                spellCheck={false}
                                required
                                value={signupForm.data.username}
                                onChange={(event) => signupForm.setData('username', event.target.value.toLowerCase())}
                                helper="This will be your OurNear identity. Choose a unique username."
                                error={signupForm.errors.username}
                            />
                            {signupForm.data.username.length >= 3 && usernameAvailable === false ? (
                                <p className={authErrorText}>That username is already taken.</p>
                            ) : null}
                        </div>
                        <FormField
                            appearance={AUTH_APPEARANCE}
                            id="email"
                            label="Email address"
                            name="email"
                            type="email"
                            placeholder="Enter your email address"
                            autoComplete="email"
                            spellCheck={false}
                            required
                            value={signupForm.data.email}
                            onChange={(event) => signupForm.setData('email', event.target.value)}
                            error={signupForm.errors.email}
                        />
                        <FormField
                            appearance={AUTH_APPEARANCE}
                            id="mobile"
                            label="Mobile number"
                            name="mobile"
                            type="tel"
                            placeholder="Enter your mobile number"
                            autoComplete="tel"
                            inputMode="tel"
                            required
                            value={signupForm.data.mobile}
                            onChange={(event) => signupForm.setData('mobile', event.target.value)}
                            error={signupForm.errors.mobile}
                        />
                        <div className="space-y-2">
                            <PasswordInputWithToggle
                                appearance={AUTH_APPEARANCE}
                                id="signup_password"
                                name="password"
                                label="Password"
                                placeholder="Enter your password"
                                autoComplete="new-password"
                                required
                                value={signupForm.data.password}
                                onChange={(event) => signupForm.setData('password', event.target.value)}
                                error={signupForm.errors.password}
                            />
                            <PasswordStrength password={signupForm.data.password} />
                        </div>
                        <PasswordInputWithToggle
                            appearance={AUTH_APPEARANCE}
                            id="password_confirmation"
                            name="password_confirmation"
                            label="Confirm password"
                            placeholder="Enter your password again"
                            autoComplete="new-password"
                            required
                            value={signupForm.data.password_confirmation}
                            onChange={(event) => signupForm.setData('password_confirmation', event.target.value)}
                            error={signupForm.errors.password_confirmation}
                        />
                        {signupForm.data.password_confirmation ? (
                            <p className={`-mt-2 text-[0.6875rem] ${passwordsMatch ? 'text-success' : 'text-danger'}`}>
                                {passwordsMatch ? 'Passwords match.' : 'Passwords do not match.'}
                            </p>
                        ) : null}
                        <label htmlFor="accept_terms" className="flex items-start gap-2 pt-1 text-xs leading-snug text-ink">
                            <input
                                id="accept_terms"
                                name="accept_terms"
                                type="checkbox"
                                checked={signupForm.data.accept_terms}
                                onChange={(event) => signupForm.setData('accept_terms', event.target.checked)}
                                className="mt-0.5 h-4 w-4 shrink-0 rounded border-[#D5DFE0] accent-accent"
                                required
                            />
                            <span>
                                I agree to the{' '}
                                <Link href="/terms-and-conditions" className="font-semibold text-brand hover:underline" target="_blank" rel="noreferrer">
                                    Terms &amp; Conditions
                                </Link>{' '}
                                and{' '}
                                <Link href="/privacy-policy" className="font-semibold text-brand hover:underline" target="_blank" rel="noreferrer">
                                    Privacy Policy
                                </Link>
                                .
                            </span>
                        </label>
                        {signupForm.errors.accept_terms ? (
                            <p className={authErrorText} role="alert">
                                {signupForm.errors.accept_terms}
                            </p>
                        ) : null}
                        <Button
                            type="submit"
                            surface="public"
                            variant="primary"
                            fullWidth
                            className={primaryButtonClass}
                            loading={signupForm.processing}
                            disabled={signupForm.processing || !signupForm.data.accept_terms}
                        >
                            Create account
                        </Button>
                    </form>
                    <p className="mt-6 text-center text-sm text-muted">
                        Already have an account?{' '}
                        <Link href="/login" className={`font-semibold text-brand hover:underline ${focusRing}`}>
                            Login
                        </Link>
                    </p>
                </>
            )}
        </AuthExperienceLayout>
    );
}
