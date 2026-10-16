import type { SiteContent } from '@/types/public';

type Props = {
    contact: SiteContent['contact'];
    className?: string;
};

function isConfigured(value: string | null | undefined): boolean {
    if (!value?.trim()) {
        return false;
    }
    const trimmed = value.trim();
    return trimmed !== '—' && !/to be published/i.test(trimmed);
}

function phoneHref(phone: string): string {
    const digits = phone.replace(/[^\d+]/g, '');
    return digits.length >= 8 ? `tel:${digits}` : phone;
}

export default function ContactDetailsSimple({ contact, className = '' }: Props) {
    const email = contact.support_email || contact.email;
    const phone = contact.phone;
    const whatsapp = contact.whatsapp;
    const address = contact.address;

    const rows: Array<{ label: string; value: string; href?: string }> = [];

    if (isConfigured(address)) {
        rows.push({ label: 'Office address', value: address!.trim() });
    }
    if (isConfigured(email)) {
        rows.push({ label: 'Enquiry email', value: email!.trim(), href: `mailto:${email}` });
    }
    if (isConfigured(phone)) {
        rows.push({ label: 'Contact phone number', value: phone!.trim(), href: phoneHref(phone!) });
    }
    if (isConfigured(whatsapp)) {
        rows.push({
            label: 'WhatsApp number',
            value: whatsapp!.trim(),
            href: contact.whatsapp_url ?? undefined,
        });
    }

    if (rows.length === 0) {
        return (
            <p className={`text-sm text-muted ${className}`}>
                Official contact details are not published yet. Please check back soon.
            </p>
        );
    }

    return (
        <dl className={`space-y-5 ${className}`}>
            {rows.map((row) => (
                <div key={row.label}>
                    <dt className="text-sm font-semibold text-ink">{row.label}</dt>
                    <dd className="mt-1 text-sm text-muted whitespace-pre-line">
                        {row.href ? (
                            <a href={row.href} className="font-medium text-brand hover:underline">
                                {row.value}
                            </a>
                        ) : (
                            row.value
                        )}
                    </dd>
                </div>
            ))}
        </dl>
    );
}
