import type { AccountProfile } from '@/types/account';

type Props = {
    profile: AccountProfile;
};

export default function WhatsAppOptionalPanel({ profile }: Props) {
    return (
        <div className="space-y-2 text-sm">
            {profile.whatsapp_number ? (
                <p className="text-muted">
                    Number on file: <span className="font-medium text-ink">{profile.whatsapp_number}</span>
                </p>
            ) : (
                <p className="text-muted">No WhatsApp number saved. You can add one under Account settings.</p>
            )}
            <p className="text-muted">
                WhatsApp is optional. It is not required for Hiring activation, and account WhatsApp verification is not
                part of your required verification checklist.
            </p>
        </div>
    );
}
