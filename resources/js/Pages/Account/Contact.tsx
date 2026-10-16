import type { FormEvent } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import AccountLayout from '@/Layouts/AccountLayout';
import ContactDetailsSimple from '@/Components/Public/ContactDetailsSimple';
import FormField from '@/Components/Public/FormField';
import Button from '@/Components/atoms/Button';
import Alert from '@/Components/molecules/Alert';
import type { SharedPageProps } from '@/types/page';
import type { SiteContent } from '@/types/public';

type AccountContactProps = {
    content: SiteContent;
    showEmployerEnquiry: boolean;
    interestOptions: string[];
    defaults: {
        full_name: string;
        mobile_number: string;
        email: string;
    };
};

const selectClass =
    'w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-brand';

export default function AccountContact({
    content,
    showEmployerEnquiry,
    interestOptions,
    defaults,
}: AccountContactProps) {
    const flash = usePage<SharedPageProps>().props.flash;
    const submitted = Boolean(flash.success);

    const form = useForm({
        full_name: defaults.full_name,
        mobile_number: defaults.mobile_number,
        email: defaults.email,
        interested_in: '',
        message: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/account/contact');
    };

    return (
        <AccountLayout title="Contact Us" breadcrumbs={[{ label: 'Contact Us' }]}>
            <div className="mx-auto max-w-2xl space-y-8">
                <ContactDetailsSimple contact={content.contact} />

                {showEmployerEnquiry ? (
                    <div className="rounded-xl border border-line bg-white p-4 sm:p-5">
                        <h2 className="text-lg font-semibold text-ink">Employer enquiry</h2>
                        <p className="mt-1 text-sm text-muted">
                            Submit a hiring workspace enquiry linked to your signed-in employer account.
                        </p>
                        {submitted ? (
                            <Alert variant="success" className="mt-6">
                                {flash.success}
                            </Alert>
                        ) : (
                            <form onSubmit={submit} className="mt-4 space-y-3">
                                <FormField
                                    id="full_name"
                                    label="Contact name"
                                    required
                                    value={form.data.full_name}
                                    onChange={(event) => form.setData('full_name', event.currentTarget.value)}
                                />
                                {form.errors.full_name ? (
                                    <p className="text-sm text-danger">{form.errors.full_name}</p>
                                ) : null}
                                <FormField
                                    id="mobile_number"
                                    label="Mobile number"
                                    required
                                    value={form.data.mobile_number}
                                    onChange={(event) => form.setData('mobile_number', event.currentTarget.value)}
                                />
                                {form.errors.mobile_number ? (
                                    <p className="text-sm text-danger">{form.errors.mobile_number}</p>
                                ) : null}
                                <FormField
                                    id="email"
                                    label="Email address"
                                    type="email"
                                    value={form.data.email}
                                    onChange={(event) => form.setData('email', event.currentTarget.value)}
                                />
                                {form.errors.email ? <p className="text-sm text-danger">{form.errors.email}</p> : null}
                                <div>
                                    <label htmlFor="interested_in" className="mb-1 block text-sm font-medium text-ink">
                                        Topic <span className="text-danger">*</span>
                                    </label>
                                    <select
                                        id="interested_in"
                                        className={selectClass}
                                        required
                                        value={form.data.interested_in}
                                        onChange={(event) => form.setData('interested_in', event.target.value)}
                                    >
                                        <option value="">Select a topic</option>
                                        {interestOptions.map((option) => (
                                            <option key={option} value={option}>
                                                {option}
                                            </option>
                                        ))}
                                    </select>
                                    {form.errors.interested_in ? (
                                        <p className="text-sm text-danger">{form.errors.interested_in}</p>
                                    ) : null}
                                </div>
                                <FormField
                                    id="message"
                                    label="Message"
                                    required
                                    as="textarea"
                                    value={form.data.message}
                                    onChange={(event) => form.setData('message', event.currentTarget.value)}
                                />
                                {form.errors.message ? <p className="text-sm text-danger">{form.errors.message}</p> : null}
                                <Button surface="public" type="submit" disabled={form.processing}>
                                    {form.processing ? 'Sending…' : 'Send enquiry'}
                                </Button>
                            </form>
                        )}
                    </div>
                ) : null}
            </div>
        </AccountLayout>
    );
}
