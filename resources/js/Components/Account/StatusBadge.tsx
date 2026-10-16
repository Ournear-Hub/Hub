import StatusBadge from '@/Components/atoms/StatusBadge';
import { accountStatusToneClass } from '@/Components/Account/accountStatusPresentation';

export type AccountStatusBadgeProps = {
    status: string;
    className?: string;
};

/** Account domain status strings → canonical StatusBadge (atoms) presentation. */
export default function AccountStatusBadge({ status, className }: AccountStatusBadgeProps) {
    return (
        <StatusBadge toneClassName={accountStatusToneClass(status)} className={className}>
            {status}
        </StatusBadge>
    );
}
