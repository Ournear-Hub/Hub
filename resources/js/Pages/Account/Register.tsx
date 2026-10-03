import { Head, Link, useForm } from '@inertiajs/react';
import { useEffect, useState, type FormEvent } from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import FormField from '@/Components/Public/FormField';
import PageContainer from '@/Components/Public/PageContainer';
import PasswordStrength from '@/Components/Account/PasswordStrength';

type LanguageOption = {
    value: string;
    label: string;
};

type RegisterProps = {
    languages?: LanguageOption[];
};

export default function Register({ languages = [] }: RegisterProps) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        username: '',
        email: '',
        mobile: '',
        password: '',
        password_confirmation: '',
        preferred_language: 'en',
        role: 'candidate',
        accept_terms: false,
    });
    const [availability, setAvailability] = useState<'idle' | 'available' | 'taken' | 'invalid'>('idle');

    useEffect(() => {
        const username = data.username.trim();
        if (username.length < 3) {
            setAvailability('idle');
            return;
        }

        const handle = window.setTimeout(() => {
            fetch(`/register/check-username?username=${encodeURIComponent(username)}`, {
                headers: { Accept: 'application/json' },
            })
                .then((response) => response.json().then((payload) => ({ ok: response.ok, payload })))
                .then(({ ok, payload }) => {
                    if (!ok) {
                        setAvailability('invalid');
                        return;
                    }
                    setAvailability(payload.available ? 'available' : 'taken');
                })
                .catch(() => setAvailability('idle'));
        }, 350);

        return () => window.clearTimeout(handle);
    }, [data.username]);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post('/register');
    };

    const passwordsMatch =
        data.password_confirmation.length > 0 && data.password === data.password_confirmation;

    return (
        <PublicLayout title="Create Account">
            <Head title="Create Account" />
            <section className="bg-brand py-14 text-white">
                <PageContainer>
                    <h1 className="text-4xl font-semibold">Create Account</h1>
                    <p className="mt-3 max-w-2xl text-white/80">
                        Register with a unique username to apply for jobs using your profile and resume.
                    </p>
                </PageContainer>
            </section>
            <section className="py-12">
                <PageContainer className="max-w-md">
                    <form onSubmit={submit} className="space-y-4 rounded-xl border border-line bg-white p-6">
                        <fieldset>
                            <legend className="mb-2 text-sm font-medium text-ink">Account type</legend>
                            <div className="flex gap-4">
                                <label className="flex items-center gap-2 text-sm text-ink">
                                    <input
                                        type="radio"
                                        name="role"
                                        value="candidate"
                                        checked={data.role === 'candidate'}
                                        onChange={() => setData('role', 'candidate')}
                                    />
                                    Candidate
                                </label>
                                <label className="flex items-center gap-2 text-sm text-ink">
                                    <input
                                        type="radio"
                                        name="role"
                                        value="employer"
                                        checked={data.role === 'employer'}
                                        onChange={() => setData('role', 'employer')}
                                    />
                                    Employer
                                </label>
                            </div>
                            {errors.role && <p className="mt-1 text-sm text-danger">{errors.role}</p>}
                        </fieldset>
                        <FormField
                            id="name"
                            label="Name"
                            value={data.name}
                            autoComplete="name"
                            onChange={(event) => setData('name', event.target.value)}
                        />
                        {errors.name && <p className="-mt-2 text-sm text-danger">{errors.name}</p>}
                        <FormField
                            id="username"
                            label="Username"
                            value={data.username}
                            autoComplete="username"
                            onChange={(event) => setData('username', event.target.value)}
                        />
                        {availability === 'available' ? (
                            <p className="-mt-2 text-sm text-success">✓ Username available</p>
                        ) : null}
                        {availability === 'taken' ? (
                            <p className="-mt-2 text-sm text-danger">✕ Username not available. Try another username.</p>
                        ) : null}
                        {availability === 'invalid' ? (
                            <p className="-mt-2 text-sm text-danger">Use 3–64 letters, numbers, dashes, or underscores.</p>
                        ) : null}
                        {errors.username && <p className="-mt-2 text-sm text-danger">{errors.username}</p>}
                        <FormField
                            id="email"
                            label="Email"
                            type="email"
                            value={data.email}
                            autoComplete="email"
                            onChange={(event) => setData('email', event.target.value)}
                        />
                        {errors.email && <p className="-mt-2 text-sm text-danger">{errors.email}</p>}
                        <p className="-mt-2 text-xs text-muted">
                            If you are registering as a company or employer, use your official work email.
                        </p>
                        <FormField
                            id="mobile"
                            label="Mobile"
                            value={data.mobile}
                            autoComplete="tel"
                            onChange={(event) => setData('mobile', event.target.value)}
                        />
                        {errors.mobile && <p className="-mt-2 text-sm text-danger">{errors.mobile}</p>}
                        <FormField
                            id="password"
                            label="Password"
                            type="password"
                            value={data.password}
                            autoComplete="new-password"
                            onChange={(event) => setData('password', event.target.value)}
                        />
                        <PasswordStrength password={data.password} />
                        {errors.password && <p className="-mt-2 text-sm text-danger">{errors.password}</p>}
                        <FormField
                            id="password_confirmation"
                            label="Confirm password"
                            type="password"
                            value={data.password_confirmation}
                            autoComplete="new-password"
                            onChange={(event) => setData('password_confirmation', event.target.value)}
                        />
                        {data.password_confirmation ? (
                            <p className={`-mt-2 text-sm ${passwordsMatch ? 'text-success' : 'text-danger'}`}>
                                {passwordsMatch ? '✓ Passwords match' : '✕ Passwords do not match'}
                            </p>
                        ) : null}
                        {languages.length > 0 ? (
                            <div>
                                <label htmlFor="preferred_language" className="mb-1 block text-sm font-medium text-ink">
                                    Language
                                </label>
                                <select
                                    id="preferred_language"
                                    className="w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink"
                                    value={data.preferred_language}
                                    onChange={(event) => setData('preferred_language', event.target.value)}
                                >
                                    {languages.map((language) => (
                                        <option key={language.value} value={language.value}>
                                            {language.label}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        ) : null}
                        <label className="flex items-start gap-2 text-sm text-ink">
                            <input
                                type="checkbox"
                                checked={data.accept_terms}
                                onChange={(event) => setData('accept_terms', event.target.checked)}
                                className="mt-1"
                            />
                            <span>
                                I agree to the{' '}
                                <Link href="/terms-and-conditions" className="font-semibold text-brand hover:underline" target="_blank">
                                    Terms &amp; Conditions
                                </Link>{' '}
                                and{' '}
                                <Link href="/privacy-policy" className="font-semibold text-brand hover:underline" target="_blank">
                                    Privacy Policy
                                </Link>
                                .
                            </span>
                        </label>
                        {errors.accept_terms ? <p className="-mt-2 text-sm text-danger">{errors.accept_terms}</p> : null}
                        <button
                            type="submit"
                            disabled={processing || !data.accept_terms}
                            className="w-full rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white hover:opacity-95 disabled:bg-disabled"
                        >
                            Create account
                        </button>
                    </form>
                    <p className="mt-6 text-center text-sm text-muted">
                        Already registered?{' '}
                        <Link href="/login" className="font-medium text-brand hover:underline">
                            Log in
                        </Link>
                    </p>
                </PageContainer>
            </section>
        </PublicLayout>
    );
}
