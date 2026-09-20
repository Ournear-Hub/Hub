import ApplicationWorkspacePage from '@/Components/Admin/ApplicationWorkspacePage';
import type { ApplicationWorkspaceProps } from '@/types/applications';

export default function ScreeningIndex(props: ApplicationWorkspaceProps) {
    return (
        <ApplicationWorkspacePage
            {...props}
            title="Screening"
            description="Screen applications for a job, then shortlist or mark not suitable."
            basePath="/admin/screening"
            variant="screening"
            defaultTab="screening"
        />
    );
}
