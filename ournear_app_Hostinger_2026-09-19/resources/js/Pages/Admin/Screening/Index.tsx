import ApplicationWorkspacePage from '@/Components/Admin/ApplicationWorkspacePage';
import type { ApplicationWorkspaceProps } from '@/types/applications';

export default function ScreeningIndex(props: ApplicationWorkspaceProps) {
    return (
        <ApplicationWorkspacePage
            {...props}
            title="Screening"
            description="Job-centric screening workspace. Compare candidates, record evidence, and make a human recruitment decision. Match % does not determine suitability."
            basePath="/admin/screening"
            variant="screening"
            defaultTab="screening"
        />
    );
}
