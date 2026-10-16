import type { FormEvent } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import AccountLayout from '@/Layouts/AccountLayout';
import FormField from '@/Components/Public/FormField';
import Button from '@/Components/atoms/Button';
import Alert from '@/Components/molecules/Alert';
import type { SharedPageProps } from '@/types/page';

type Props = {
    defaults: {
        company_name: string;
        contact_person: string;
        mobile_number: string;
        email: string;
        industry: string;
    };
};

const selectClass =
    'w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-brand focus-visible:ring-2 focus-visible:ring-brand/30';

export default function RecruitmentRequirementForm({ defaults }: Props) {
    const flash = usePage<SharedPageProps>().props.flash;
    const submitted = Boolean(flash.success);

    const form = useForm({
        designation: '',
        industry: defaults.industry,
        job_category: '',
        number_of_candidates: 1,
        qualifications: '',
        skills: '',
        experience_range: '',
        work_location: '',
        employment_type: '',
        hiring_timeline: '',
        additional_requirements: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/account/recruitment-support', { preserveScroll: true });
    };

    return (
        <AccountLayout title="Recruitment Requirement Form" hideShellHeader>
            <div className="mx-auto max-w-2xl">
                <h1 className="text-xl font-semibold text-ink sm:text-2xl">Recruitment Requirement Form</h1>
                {submitted ? (
                    <Alert variant="success">{flash.success}</Alert>
                ) : (
                    <form onSubmit={submit} className="space-y-4 rounded-xl border border-line bg-white p-4 sm:p-5">
                        <FormField
                            id="designation"
                            label="Required designation / role title"
                            required
                            value={form.data.designation}
                            onChange={(event) => form.setData('designation', event.currentTarget.value)}
                        />
                        {form.errors.designation ? <p className="text-sm text-danger">{form.errors.designation}</p> : null}
                        <FormField
                            id="industry"
                            label="Industry"
                            required
                            value={form.data.industry}
                            onChange={(event) => form.setData('industry', event.currentTarget.value)}
                        />
                        {form.errors.industry ? <p className="text-sm text-danger">{form.errors.industry}</p> : null}
                        <FormField
                            id="job_category"
                            label="Job category"
                            value={form.data.job_category}
                            onChange={(event) => form.setData('job_category', event.currentTarget.value)}
                        />
                        <FormField
                            id="number_of_candidates"
                            label="Number of candidates"
                            type="number"
                            min={1}
                            required
                            value={String(form.data.number_of_candidates)}
                            onChange={(event) =>
                                form.setData('number_of_candidates', Number(event.currentTarget.value) || 1)
                            }
                        />
                        {form.errors.number_of_candidates ? (
                            <p className="text-sm text-danger">{form.errors.number_of_candidates}</p>
                        ) : null}
                        <FormField
                            id="qualifications"
                            label="Required qualifications"
                            as="textarea"
                            value={form.data.qualifications}
                            onChange={(event) => form.setData('qualifications', event.currentTarget.value)}
                        />
                        <FormField
                            id="skills"
                            label="Required skills"
                            as="textarea"
                            value={form.data.skills}
                            onChange={(event) => form.setData('skills', event.currentTarget.value)}
                        />
                        <FormField
                            id="experience_range"
                            label="Experience range"
                            value={form.data.experience_range}
                            onChange={(event) => form.setData('experience_range', event.currentTarget.value)}
                        />
                        <FormField
                            id="work_location"
                            label="Work location"
                            required
                            value={form.data.work_location}
                            onChange={(event) => form.setData('work_location', event.currentTarget.value)}
                        />
                        <div>
                            <label htmlFor="employment_type" className="mb-1 block text-sm font-medium text-ink">
                                Employment type
                            </label>
                            <select
                                id="employment_type"
                                className={selectClass}
                                value={form.data.employment_type}
                                onChange={(event) => form.setData('employment_type', event.target.value)}
                            >
                                <option value="">Select type</option>
                                <option value="Permanent">Permanent</option>
                                <option value="Contract">Contract</option>
                                <option value="Temporary">Temporary</option>
                                <option value="Internship">Internship</option>
                            </select>
                        </div>
                        <FormField
                            id="hiring_timeline"
                            label="Hiring timeline"
                            value={form.data.hiring_timeline}
                            onChange={(event) => form.setData('hiring_timeline', event.currentTarget.value)}
                        />
                        <FormField
                            id="additional_requirements"
                            label="Additional requirements"
                            as="textarea"
                            value={form.data.additional_requirements}
                            onChange={(event) => form.setData('additional_requirements', event.currentTarget.value)}
                        />
                        <Button surface="public" type="submit" disabled={form.processing} className="min-h-11">
                            {form.processing ? 'Submitting…' : 'Submit requirement'}
                        </Button>
                    </form>
                )}
            </div>
        </AccountLayout>
    );
}
