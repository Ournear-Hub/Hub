import ApplicationWorkspacePage from '@/Components/Admin/ApplicationWorkspacePage';
import type { ApplicationWorkspaceProps } from '@/types/applications';

export default function Index(props: ApplicationWorkspaceProps) {
    return (
        <ApplicationWorkspacePage
            {...props}
            title="Shortlisted"
            description="Candidates a recruiter has shortlisted. Employer submission uses the existing submission records."
            basePath="/admin/shortlisted"
            variant="shortlisted"
        />
    );
}
