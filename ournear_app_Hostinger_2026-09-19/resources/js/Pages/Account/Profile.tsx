import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import FormField from '@/Components/Public/FormField';
import PageContainer from '@/Components/Public/PageContainer';
import type { SharedPageProps } from '@/types/page';

type ProfileForm = {
    name: string;
    email: string;
    mobile: string;
    location: string;
    experience: string;
    qualification: string;
    company_name: string;
    contact_person: string;
    mobile_number: string;
    industry: string;
};

type ProfileProps = {
    role: 'candidate' | 'employer';
    profile: Record<string, string | null> & { name: string; email: string };
};

export default function Profile({ role, profile }: ProfileProps) {
    const flash = usePage<SharedPageProps>().props.flash;
    const form = useForm<ProfileForm>({
        name: profile.name,
        email: profile.email,
        mobile: profile.mobile ?? '',
        location: profile.location ?? '',
        experience: profile.experience ?? '',
        qualification: profile.qualification ?? '',
        company_name: profile.company_name ?? '',
        contact_person: profile.contact_person ?? '',
        mobile_number: profile.mobile_number ?? '',
        industry: profile.industry ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put('/account/profile');
    };

    return (
        <PublicLayout title="Profile">
            <Head title="Profile" />
            <section className="bg-brand py-14 text-white">
                <PageContainer>
                    <h1 className="text-4xl font-semibold">Profile</h1>
                </PageContainer>
            </section>
            <section className="py-12">
                <PageContainer className="max-w-xl">
                    {flash.success && (
                        <p className="mb-4 rounded-md border border-success/20 bg-success/10 px-3 py-2 text-sm text-success">
                            {flash.success}
                        </p>
                    )}
                    <form onSubmit={submit} className="space-y-4 rounded-xl border border-line bg-white p-6">
                        <FormField
                            id="name"
                            label="Name"
                            value={form.data.name}
                            onChange={(event) => form.setData('name', event.target.value)}
                        />
                        {form.errors.name && <p className="-mt-2 text-sm text-danger">{form.errors.name}</p>}
                        <div>
                            <p className="mb-1 text-sm font-medium text-ink">Email</p>
                            <p className="text-sm text-muted">{profile.email}</p>
                        </div>
                        {role === 'candidate' ? (
                            <>
                                <FormField
                                    id="mobile"
                                    label="Mobile"
                                    value={form.data.mobile}
                                    onChange={(event) => form.setData('mobile', event.target.value)}
                                />
                                <FormField
                                    id="location"
                                    label="Location"
                                    value={form.data.location}
                                    onChange={(event) => form.setData('location', event.target.value)}
                                />
                                <FormField
                                    id="experience"
                                    label="Experience"
                                    value={form.data.experience}
                                    onChange={(event) => form.setData('experience', event.target.value)}
                                />
                                <FormField
                                    id="qualification"
                                    label="Qualification"
                                    value={form.data.qualification}
                                    onChange={(event) => form.setData('qualification', event.target.value)}
                                />
                            </>
                        ) : (
                            <>
                                <FormField
                                    id="company_name"
                                    label="Company name"
                                    value={form.data.company_name}
                                    onChange={(event) => form.setData('company_name', event.target.value)}
                                />
                                <FormField
                                    id="contact_person"
                                    label="Contact person"
                                    value={form.data.contact_person}
                                    onChange={(event) => form.setData('contact_person', event.target.value)}
                                />
                                <FormField
                                    id="mobile_number"
                                    label="Mobile"
                                    value={form.data.mobile_number}
                                    onChange={(event) => form.setData('mobile_number', event.target.value)}
                                />
                                <FormField
                                    id="industry"
                                    label="Industry"
                                    value={form.data.industry}
                                    onChange={(event) => form.setData('industry', event.target.value)}
                                />
                            </>
                        )}
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white hover:opacity-95 disabled:bg-disabled"
                        >
                            Save profile
                        </button>
                    </form>
                    <p className="mt-6 text-sm">
                        <Link href="/account" className="font-medium text-brand hover:underline">
                            Back to account
                        </Link>
                    </p>
                </PageContainer>
            </section>
        </PublicLayout>
    );
}
