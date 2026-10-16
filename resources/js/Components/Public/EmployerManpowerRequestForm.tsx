import type { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import Button from '@/Components/atoms/Button';
import FormField from '@/Components/Public/FormField';
import HoneypotField from '@/Components/Public/HoneypotField';
import TurnstileField from '@/Components/Public/TurnstileField';

const selectClass =
    'w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-brand focus-visible:ring-2 focus-visible:ring-brand/30';

type EmployerManpowerRequestFormProps = {
    industries: string[];
    workforceTypes: string[];
    successMessage?: string | null;
};

export default function EmployerManpowerRequestForm({
    industries,
    workforceTypes,
    successMessage,
}: EmployerManpowerRequestFormProps) {
    const form = useForm({
        company_name: '',
        contact_person: '',
        mobile_number: '',
        email: '',
        industry: '',
        project_location: '',
        workforce_type: '',
        number_of_people: 1,
        required_skills: '',
        period_from: '',
        period_to: '',
        training_required: '0',
        additional_requirements: '',
        fax: '',
        'cf-turnstile-response': '',
        from_staffing: '1',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/for-employers');
    };

    if (successMessage) {
        return (
            <p
                className="rounded-md border border-success/20 bg-success/10 px-4 py-3 text-sm text-success"
                role="status"
                aria-live="polite"
            >
                {successMessage}
            </p>
        );
    }

    return (
        <form onSubmit={submit} className="grid gap-4 md:grid-cols-2" noValidate>
            <div>
                <FormField
                    id="staffing_company_name"
                    label="Company or organization name"
                    name="company_name"
                    autoComplete="organization"
                    required
                    value={form.data.company_name}
                    onChange={(event) => form.setData('company_name', event.currentTarget.value)}
                />
                {form.errors.company_name ? (
                    <p className="mt-1 text-sm text-danger">{form.errors.company_name}</p>
                ) : null}
            </div>
            <div>
                <FormField
                    id="staffing_contact_person"
                    label="Contact person"
                    name="contact_person"
                    autoComplete="name"
                    required
                    value={form.data.contact_person}
                    onChange={(event) => form.setData('contact_person', event.currentTarget.value)}
                />
                {form.errors.contact_person ? (
                    <p className="mt-1 text-sm text-danger">{form.errors.contact_person}</p>
                ) : null}
            </div>
            <div>
                <FormField
                    id="staffing_mobile_number"
                    label="Phone number"
                    name="mobile_number"
                    type="tel"
                    autoComplete="tel"
                    inputMode="tel"
                    required
                    value={form.data.mobile_number}
                    onChange={(event) => form.setData('mobile_number', event.currentTarget.value)}
                />
                {form.errors.mobile_number ? (
                    <p className="mt-1 text-sm text-danger">{form.errors.mobile_number}</p>
                ) : null}
            </div>
            <div>
                <FormField
                    id="staffing_email"
                    label="Business email"
                    name="email"
                    type="email"
                    autoComplete="email"
                    spellCheck={false}
                    required
                    value={form.data.email}
                    onChange={(event) => form.setData('email', event.currentTarget.value)}
                />
                {form.errors.email ? <p className="mt-1 text-sm text-danger">{form.errors.email}</p> : null}
            </div>
            <div>
                <label htmlFor="staffing_industry" className="mb-1 block text-sm font-medium text-ink">
                    Industry
                </label>
                <select
                    id="staffing_industry"
                    name="industry"
                    className={selectClass}
                    required
                    value={form.data.industry}
                    onChange={(event) => form.setData('industry', event.target.value)}
                >
                    <option value="">Select industry…</option>
                    {industries.map((name) => (
                        <option key={name} value={name}>
                            {name}
                        </option>
                    ))}
                </select>
                {form.errors.industry ? <p className="mt-1 text-sm text-danger">{form.errors.industry}</p> : null}
            </div>
            <div>
                <FormField
                    id="staffing_project_location"
                    label="Work location"
                    name="project_location"
                    required
                    value={form.data.project_location}
                    onChange={(event) => form.setData('project_location', event.currentTarget.value)}
                />
                {form.errors.project_location ? (
                    <p className="mt-1 text-sm text-danger">{form.errors.project_location}</p>
                ) : null}
            </div>
            <div>
                <label htmlFor="staffing_workforce_type" className="mb-1 block text-sm font-medium text-ink">
                    Workforce type
                </label>
                <select
                    id="staffing_workforce_type"
                    name="workforce_type"
                    className={selectClass}
                    required
                    value={form.data.workforce_type}
                    onChange={(event) => form.setData('workforce_type', event.target.value)}
                >
                    <option value="">Select type…</option>
                    {workforceTypes.map((type) => (
                        <option key={type} value={type}>
                            {type}
                        </option>
                    ))}
                </select>
                {form.errors.workforce_type ? (
                    <p className="mt-1 text-sm text-danger">{form.errors.workforce_type}</p>
                ) : null}
            </div>
            <div>
                <FormField
                    id="staffing_number_of_people"
                    label="Number of people required"
                    name="number_of_people"
                    type="number"
                    min={1}
                    required
                    value={String(form.data.number_of_people)}
                    onChange={(event) => form.setData('number_of_people', Number(event.currentTarget.value))}
                />
                {form.errors.number_of_people ? (
                    <p className="mt-1 text-sm text-danger">{form.errors.number_of_people}</p>
                ) : null}
            </div>
            <div className="md:col-span-2">
                <FormField
                    id="staffing_required_skills"
                    label="Workforce skills or role descriptions"
                    name="required_skills"
                    as="textarea"
                    required
                    value={form.data.required_skills}
                    onChange={(event) => form.setData('required_skills', event.currentTarget.value)}
                />
                {form.errors.required_skills ? (
                    <p className="mt-1 text-sm text-danger">{form.errors.required_skills}</p>
                ) : null}
            </div>
            <div>
                <FormField
                    id="staffing_period_from"
                    label="From date"
                    name="period_from"
                    type="date"
                    required
                    value={form.data.period_from}
                    onChange={(event) => form.setData('period_from', event.currentTarget.value)}
                />
                {form.errors.period_from ? (
                    <p className="mt-1 text-sm text-danger">{form.errors.period_from}</p>
                ) : null}
            </div>
            <div>
                <FormField
                    id="staffing_period_to"
                    label="To date"
                    name="period_to"
                    type="date"
                    required
                    value={form.data.period_to}
                    onChange={(event) => form.setData('period_to', event.currentTarget.value)}
                />
                {form.errors.period_to ? (
                    <p className="mt-1 text-sm text-danger">{form.errors.period_to}</p>
                ) : null}
            </div>
            <div>
                <label htmlFor="staffing_training_required" className="mb-1 block text-sm font-medium text-ink">
                    Training required?
                </label>
                <select
                    id="staffing_training_required"
                    name="training_required"
                    className={selectClass}
                    value={form.data.training_required}
                    onChange={(event) => form.setData('training_required', event.target.value)}
                >
                    <option value="0">No</option>
                    <option value="1">Yes</option>
                </select>
                {form.errors.training_required ? (
                    <p className="mt-1 text-sm text-danger">{form.errors.training_required}</p>
                ) : null}
            </div>
            <div className="md:col-span-2">
                <FormField
                    id="staffing_additional_requirements"
                    label="Additional notes or workforce requirements"
                    name="additional_requirements"
                    as="textarea"
                    value={form.data.additional_requirements}
                    onChange={(event) => form.setData('additional_requirements', event.currentTarget.value)}
                />
                {form.errors.additional_requirements ? (
                    <p className="mt-1 text-sm text-danger">{form.errors.additional_requirements}</p>
                ) : null}
            </div>
            <div className="md:col-span-2">
                <HoneypotField value={form.data.fax} onChange={(value) => form.setData('fax', value)} />
                <TurnstileField onToken={(token) => form.setData('cf-turnstile-response', token)} />
                {form.errors['cf-turnstile-response'] ? (
                    <p className="text-sm text-danger">{form.errors['cf-turnstile-response']}</p>
                ) : null}
            </div>
            <div className="md:col-span-2">
                <Button surface="public" type="submit" variant="primary" loading={form.processing}>
                    {form.processing ? 'Submitting…' : 'Submit manpower request'}
                </Button>
            </div>
        </form>
    );
}
