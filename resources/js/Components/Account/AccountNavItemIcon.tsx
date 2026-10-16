import OurNearIcon from '@/icons/OurNearIcon';

type AccountNavItemIconProps = {
    icon?: string;
    active?: boolean;
    /** Sidebar active uses white-on-brand; mobile sheet uses brand tint */
    variant?: 'sidebar' | 'sheet' | 'employer-sidebar' | 'candidate-sidebar';
};

export default function AccountNavItemIcon({ icon, active = false, variant = 'sidebar' }: AccountNavItemIconProps) {
    if (!icon) {
        return null;
    }

    const tone =
        variant === 'sheet'
            ? active
                ? 'text-brand'
                : 'text-muted'
            : variant === 'employer-sidebar'
              ? active
                  ? 'text-white'
                  : 'text-white/75'
              : variant === 'candidate-sidebar'
                ? active
                    ? 'text-brand'
                    : 'text-muted'
                : active
                  ? 'text-white'
                  : 'text-muted';

    return <OurNearIcon name={icon} size="sm" className={`shrink-0 ${tone}`} decorative />;
}
