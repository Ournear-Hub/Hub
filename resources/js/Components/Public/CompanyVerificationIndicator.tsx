import OurNearIcon from '@/icons/OurNearIcon';

type Props = {
    verified: boolean;
    className?: string;
};

/** Public jobs surfaces: show verification cue only when verified (no unverified disclosure). */
export default function CompanyVerificationIndicator({ verified, className = '' }: Props) {
    if (!verified) {
        return null;
    }

    return (
        <span
            className={`inline-flex shrink-0 items-center ${className}`}
            title="OurNear verified employer"
        >
            <OurNearIcon
                name="verified-badge"
                size="sm"
                aria-label="OurNear verified employer"
                className="text-accent"
            />
        </span>
    );
}
