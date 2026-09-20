import ApplicationWorkspacePage from '@/Components/Admin/ApplicationWorkspacePage';
import type { ApplicationWorkspaceProps } from '@/types/applications';

export default function ApplicationsIndex(props: ApplicationWorkspaceProps) {
    return (
        <ApplicationWorkspacePage
            {...props}
            title="Applications"
            description="Candidate applications for current jobs. Guest applications remain included."
            basePath="/admin/applications"
            variant="applications"
        />
    );
}
