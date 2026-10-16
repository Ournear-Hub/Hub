import type { FormEvent } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import FormField from '@/Components/Public/FormField';
import Button from '@/Components/atoms/Button';
import Alert from '@/Components/molecules/Alert';
import Card from '@/Components/atoms/Card';
import type { SharedPageProps } from '@/types/page';

export type StaffingFormDefaults = {
    company_name: string;
    contact_person: string;
    mobile_number: string;
    email: string;
    industry: string;
};

type Props = {
    industries: string[];
    workforce_types: string[];
    defaults: StaffingFormDefaults;
    submitUrl?: string;
};

const selectClass =
    'w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-brand focus-visible:ring-2 focus-visible:ring-brand/30';

export default function StaffingRequirementsForm({
    industries,
    workforce_types,
    defaults,
    submitUrl = '/account/enquiries',
}: Props) {
    const flash = usePage<SharedPageProps>().props.flash;
    const submitted = Boolean(flash.success);

    const form = useForm({
        company_name: defaults.company_name,
        contact_person: defaults.contact_person,
        mobile_number: defaults.mobile_number,
        email: defaults.email,
        industry: defaults.industry,
        project_location: '',
        workforce_type: '',
        number_of_people: 1,
        required_skills: '',
        period_from: '',
        period_to: '',
        training_required: '0',
        additional_requirements: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(submitUrl, { preserveScroll: true });
    };

    return (
        <Card as="section" padding="standard" aria-labelledby="staffing-requirements-heading">
            <h2 id="staffing-requirements-heading" className="text-lg font-semibold text-ink">
                Staffing Requirements
            </h2>
            <p className="mt-1 text-sm text-muted">
                Submit workforce staffing needs for your company. OurNear will follow up on your request.
            </p>

            {submitted ? (
                <Alert variant="success" className="mt-4">
                    {flash.success}
                </Alert>
            ) : null}

            <form onSubmit={submit} className="mt-4 grid gap-4 md:grid-cols-2">
                <div className="md:col-span-2">
                    <FormField
                        id="staffing_company_name"
                        label="Company name"
                        required
                        value={form.data.company_name}
                        onChange={(event) => form.setData('company_name', event.currentTarget.value)}
                    />
                    {form.errors.company_name ? (
                        <p className="mt-1 text-sm text-danger">{form.errors.company_name}</p>
                    ) : null}
                </div>
                <FormField
                    id="staffing_contact_person"
                    label="Contact person"
                    required
                    value={form.data.contact_person}
                    onChange={(event) => form.setData('contact_person', event.currentTarget.value)}
                />
                {form.errors.contact_person ? (
                    <p className="text-sm text-danger md:col-span-1">{form.errors.contact_person}</p>
                ) : null}
                <FormField
                    id="staffing_mobile_number"
                    label="Mobile number"
                    required
                    value={form.data.mobile_number}
                    onChange={(event) => form.setData('mobile_number', event.currentTarget.value)}
                />
                {form.errors.mobile_number ? (
                    <p className="text-sm text-danger md:col-span-1">{form.errors.mobile_number}</p>
                ) : null}
                <div className="md:col-span-2">
                    <FormField
                        id="staffing_email"
                        label="Email"
                        type="email"
                        required
                        value={form.data.email}
                        onChange={(event) => form.setData('email', event.currentTarget.value)}
                    />
                    {form.errors.email ? <p className="mt-1 text-sm text-danger">{form.errors.email}</p> : null}
                </div>
                <div>
                    <label htmlFor="staffing_industry" className="mb-1 block text-sm font-medium text-ink">
                        Industry <span className="text-danger">*</span>
                    </label>
                    <select
                        id="staffing_industry"
                        required
                        className={selectClass}
                        value={form.data.industry}
                        onChange={(event) => form.setData('industry', event.target.value)}
                    >
                        <option value="">Select industry</option>
                        {industries.map((industry) => (
                            <option key={industry} value={industry}>
                                {industry}
                            </option>
                        ))}
                    </select>
                    {form.errors.industry ? <p className="mt-1 text-sm text-danger">{form.errors.industry}</p> : null}
                </div>
                <div>
                    <label htmlFor="staffing_workforce_type" className="mb-1 block text-sm font-medium text-ink">
                        Staffing requirement type <span className="text-danger">*</span>
                    </label>
                    <select
                        id="staffing_workforce_type"
                        required
                        className={selectClass}
                        value={form.data.workforce_type}
                        onChange={(event) => form.setData('workforce_type', event.target.value)}
                    >
                        <option value="">Select type</option>
                        {workforce_types.map((type) => (
                            <option key={type} value={type}>
                                {type}
                            </option>
                        ))}
                    </select>
                    {form.errors.workforce_type ? (
                        <p className="mt-1 text-sm text-danger">{form.errors.workforce_type}</p>
                    ) : null}
                </div>
                <FormField
                    id="staffing_project_location"
                    label="Location"
                    required
                    value={form.data.project_location}
                    onChange={(event) => form.setData('project_location', event.currentTarget.value)}
                />
                {form.errors.project_location ? (
                    <p className="text-sm text-danger">{form.errors.project_location}</p>
                ) : null}
                <FormField
                    id="staffing_number_of_people"
                    label="Number of workers"
                    type="number"
                    min={1}
                    required
                    value={String(form.data.number_of_people)}
                    onChange={(event) => form.setData('number_of_people', Number(event.currentTarget.value) || 1)}
                />
                {form.errors.number_of_people ? (
                    <p className="text-sm text-danger">{form.errors.number_of_people}</p>
                ) : null}
                <div className="md:col-span-2">
                    <FormField
                        id="staffing_required_skills"
                        label="Skills and qualifications"
                        as="textarea"
                        required
                        value={form.data.required_skills}
                        onChange={(event) => form.setData('required_skills', event.currentTarget.value)}
                    />
                    {form.errors.required_skills ? (
                        <p className="mt-1 text-sm text-danger">{form.errors.required_skills}</p>
                    ) : null}
                </div>
                <FormField
                    id="staffing_period_from"
                    label="Start date"
                    type="date"
                    required
                    value={form.data.period_from}
                    onChange={(event) => form.setData('period_from', event.currentTarget.value)}
                />
                {form.errors.period_from ? <p className="text-sm text-danger">{form.errors.period_from}</p> : null}
                <FormField
                    id="staffing_period_to"
                    label="End date"
                    type="date"
                    required
                    value={form.data.period_to}
                    onChange={(event) => form.setData('period_to', event.currentTarget.value)}
                />
                {form.errors.period_to ? <p className="text-sm text-danger">{form.errors.period_to}</p> : null}
                <div className="md:col-span-2">
                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            checked={form.data.training_required === '1'}
                            onChange={(event) => form.setData('training_required', event.target.checked ? '1' : '0')}
                        />
                        Training required
                    </label>
                </div>
                <div className="md:col-span-2">
                    <FormField
                        id="staffing_additional_requirements"
                        label="Additional requirements"
                        as="textarea"
                        value={form.data.additional_requirements}
                        onChange={(event) => form.setData('additional_requirements', event.currentTarget.value)}
                    />
                </div>
                <div className="md:col-span-2">
                    <Button surface="public" type="submit" disabled={form.processing} className="min-h-11">
                        {form.processing ? 'Submitting…' : 'Submit staffing requirement'}
                    </Button>
                </div>
            </form>
        </Card>
    );
}
