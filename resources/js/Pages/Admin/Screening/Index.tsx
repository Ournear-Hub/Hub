import ApplicationWorkspacePage from '@/Components/Admin/ApplicationWorkspacePage';
import type { ApplicationWorkspaceProps } from '@/types/applications';

export default function Index(props: ApplicationWorkspaceProps) {
    return (
        <ApplicationWorkspacePage
            {...props}
            title="Screening"
            description="Match scores come from ScreeningMatchService using structured profile fields and Hire Resume data. Shortlist stays a human decision."
            basePath="/admin/screening"
            variant="screening"
        />
    );
}
