import Button from '@/Components/atoms/Button';
import { BackNavButton } from '@/Components/molecules/BackNavLink';
import Alert from '@/Components/UI/Alert';
import type { ProfileModuleCard } from '@/Components/Profile/profileCompletionModules';

type Props = {
    message: string;
    nextModule: ProfileModuleCard | null;
    onContinueNext: () => void;
    onBackToHub: () => void;
};

export default function ProfileModuleContinueBar({ message, nextModule, onContinueNext, onBackToHub }: Props) {
    return (
        <Alert tone="success" title="Saved" className="mt-4">
            {message}
            <div className="mt-3 flex flex-wrap gap-2">
                {nextModule ? (
                    <Button surface="public" type="button" variant="primary" className="min-h-11" onClick={onContinueNext}>
                        Continue to {nextModule.title}
                    </Button>
                ) : null}
                <BackNavButton label="Back to profile" onClick={onBackToHub} />
            </div>
        </Alert>
    );
}
