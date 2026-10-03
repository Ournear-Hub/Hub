import ApplicationWorkspacePage from '@/Components/Admin/ApplicationWorkspacePage';
import type { ApplicationWorkspaceProps } from '@/types/applications';

export default function Index(props: ApplicationWorkspaceProps) {
    return (
        <ApplicationWorkspacePage
            {...props}
            title="Applications"
            description="Applications already recorded for jobs. Status changes stay in the existing pipeline."
            basePath="/admin/applications"
            variant="applications"
        />
    );
}
