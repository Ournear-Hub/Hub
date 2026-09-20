import ApplicationWorkspacePage from '@/Components/Admin/ApplicationWorkspacePage';
import type { ApplicationWorkspaceProps } from '@/types/applications';

export default function ShortlistedIndex(props: ApplicationWorkspaceProps) {
    return (
        <ApplicationWorkspacePage
            {...props}
            title="Shortlisted"
            description="Candidates shortlisted for a specific job. This is not employer selection."
            basePath="/admin/shortlisted"
            variant="shortlisted"
            defaultTab="screening"
        />
    );
}
