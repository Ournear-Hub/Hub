import { router, useForm } from '@inertiajs/react';
import axios from 'axios';
import { useState, type FormEvent, type ReactNode } from 'react';

export type MailSettings = {
    mail_mailer: string;
    mail_enabled: boolean;
    smtp_host: string;
    smtp_port: number | string;
    smtp_encryption: string;
    smtp_username: string;
    smtp_password_configured: boolean;
    smtp_timeout: number | string;
    mail_use_queue: boolean;
    last_diagnostic: {
        operation?: string;
        outcome?: string;
        at?: string;
        details?: Record<string, unknown>;
    } | null;
};

type MailEffective = {
    mail_enabled: boolean;
    mail_mailer: string;
    smtp_configured: boolean;
    smtp_password_configured: boolean;
    application_url: string;
    from_address: string;
    from_name: string;
    uses_queue: boolean;
};

type Prerequisite = {
    label: string;
    status: string;
    instruction: string;
};

type Props = {
    mailSettings: MailSettings;
    mailEffective: MailEffective;
    mailPrerequisites: Record<string, Prerequisite>;
};

const inputClass =
    'w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-brand';

export default function SettingsEmailPanel({ mailSettings, mailEffective, mailPrerequisites }: Props) {
    const form = useForm({
        mail_mailer: mailSettings.mail_mailer || 'smtp',
        mail_enabled: mailSettings.mail_enabled,
        smtp_host: mailSettings.smtp_host || '',
        smtp_port: mailSettings.smtp_port || '',
        smtp_encryption: mailSettings.smtp_encryption || 'tls',
        smtp_username: mailSettings.smtp_username || '',
        smtp_password: '',
        clear_smtp_password: false,
        smtp_timeout: mailSettings.smtp_timeout || '',
        mail_use_queue: mailSettings.mail_use_queue,
    });

    const [testRecipient, setTestRecipient] = useState('');
    const [diagnosticMessage, setDiagnosticMessage] = useState<string | null>(null);
    const [previewHtml, setPreviewHtml] = useState<string | null>(null);
    const [previewText, setPreviewText] = useState<string | null>(null);
    const [linkCheck, setLinkCheck] = useState<boolean | null>(null);
    const [busy, setBusy] = useState<string | null>(null);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put('/admin/settings/email');
    };

    const runDiagnostic = async (action: string, url: string, payload?: Record<string, string>) => {
        setBusy(action);
        setDiagnosticMessage(null);
        try {
            const response = await axios.post(url, payload ?? {});
            const data = response.data as {
                message?: string;
                valid?: boolean;
                errors?: string[];
                outcome?: string;
                html?: string;
                text?: string;
                link_present?: boolean;
            };
            if (action === 'validate') {
                setDiagnosticMessage(
                    data.valid
                        ? 'Configuration valid for the current saved values.'
                        : (data.errors ?? []).join(' '),
                );
            } else if (action === 'preview') {
                setPreviewHtml(String(data.html ?? ''));
                setPreviewText(String(data.text ?? ''));
                setLinkCheck(Boolean(data.link_present));
                setDiagnosticMessage(
                    data.link_present
                        ? 'Verification anchor and URL detected in rendered HTML.'
                        : 'Verification link was not detected in rendered HTML.',
                );
            } else {
                setDiagnosticMessage(data.message ?? data.outcome ?? 'Done.');
            }
            router.reload({ only: ['mailSettings', 'mailEffective'] });
        } catch (error: unknown) {
            if (axios.isAxiosError(error)) {
                const message =
                    (error.response?.data as { message?: string; recipient?: string[] })?.message ??
                    error.response?.data?.recipient?.[0] ??
                    error.message;
                setDiagnosticMessage(message);
            } else {
                setDiagnosticMessage('Diagnostic request failed.');
            }
        } finally {
            setBusy(null);
        }
    };

    return (
        <section className="space-y-6">
            <div className="rounded-xl border border-line bg-white p-6">
                <h2 className="text-lg font-semibold text-ink">Email delivery</h2>
                <p className="mt-1 text-sm text-muted">
                    Configure SMTP transport and diagnostics. Sender name and From/Reply-To addresses come from Website
                    settings (company communication). Environment variables remain the bootstrap fallback when admin
                    delivery is disabled or incomplete.
                </p>

                <form onSubmit={submit} className="mt-4 max-w-2xl space-y-4">
                    <label className="flex items-center gap-2 text-sm text-ink">
                        <input
                            type="checkbox"
                            checked={form.data.mail_enabled}
                            onChange={(e) => form.setData('mail_enabled', e.target.checked)}
                        />
                        Use admin-managed mail delivery (override environment SMTP when complete)
                    </label>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field label="Mail transport" error={form.errors.mail_mailer}>
                            <select
                                className={inputClass}
                                value={form.data.mail_mailer}
                                onChange={(e) => form.setData('mail_mailer', e.target.value)}
                            >
                                <option value="smtp">SMTP</option>
                                <option value="log">Log (development)</option>
                                <option value="array">Array (tests)</option>
                                <option value="sendmail">Sendmail</option>
                            </select>
                        </Field>
                        <Field label="Encryption" error={form.errors.smtp_encryption}>
                            <select
                                className={inputClass}
                                value={form.data.smtp_encryption}
                                onChange={(e) => form.setData('smtp_encryption', e.target.value)}
                            >
                                <option value="tls">TLS / STARTTLS (587)</option>
                                <option value="ssl">SSL (465)</option>
                                <option value="none">None</option>
                            </select>
                        </Field>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field label="SMTP host" error={form.errors.smtp_host}>
                            <input
                                className={inputClass}
                                value={form.data.smtp_host}
                                onChange={(e) => form.setData('smtp_host', e.target.value)}
                                placeholder="smtp-relay.brevo.com"
                            />
                        </Field>
                        <Field label="SMTP port" error={form.errors.smtp_port}>
                            <input
                                className={inputClass}
                                value={form.data.smtp_port}
                                onChange={(e) => form.setData('smtp_port', e.target.value)}
                                placeholder="587"
                            />
                        </Field>
                    </div>

                    <Field label="SMTP username" error={form.errors.smtp_username}>
                        <input
                            className={inputClass}
                            value={form.data.smtp_username}
                            onChange={(e) => form.setData('smtp_username', e.target.value)}
                            autoComplete="off"
                        />
                    </Field>

                    <Field
                        label="SMTP password"
                        error={form.errors.smtp_password}
                        hint={
                            mailSettings.smtp_password_configured
                                ? 'A password is saved. Leave blank to keep it, or enter a new value to replace.'
                                : 'Not configured yet.'
                        }
                    >
                        <input
                            type="password"
                            className={inputClass}
                            value={form.data.smtp_password}
                            onChange={(e) => form.setData('smtp_password', e.target.value)}
                            autoComplete="new-password"
                            placeholder={mailSettings.smtp_password_configured ? '••••••••' : ''}
                        />
                    </Field>

                    {mailSettings.smtp_password_configured ? (
                        <label className="flex items-center gap-2 text-sm text-ink">
                            <input
                                type="checkbox"
                                checked={form.data.clear_smtp_password}
                                onChange={(e) => form.setData('clear_smtp_password', e.target.checked)}
                            />
                            Clear saved SMTP password
                        </label>
                    ) : null}

                    <Field label="Connection timeout (seconds)" error={form.errors.smtp_timeout}>
                        <input
                            className={inputClass}
                            value={form.data.smtp_timeout}
                            onChange={(e) => form.setData('smtp_timeout', e.target.value)}
                            placeholder="15"
                        />
                    </Field>

                    <label className="flex items-center gap-2 text-sm text-ink">
                        <input
                            type="checkbox"
                            checked={form.data.mail_use_queue}
                            onChange={(e) => form.setData('mail_use_queue', e.target.checked)}
                        />
                        Queue outbound mail when supported (recommended for production)
                    </label>

                    <button
                        type="submit"
                        disabled={form.processing}
                        className="rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
                    >
                        Save email settings
                    </button>
                </form>
            </div>

            <div className="rounded-xl border border-line bg-white p-6">
                <h3 className="text-base font-semibold text-ink">Effective configuration</h3>
                <dl className="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                    <div>
                        <dt className="text-muted">Mailer</dt>
                        <dd className="font-medium text-ink">{mailEffective.mail_mailer}</dd>
                    </div>
                    <div>
                        <dt className="text-muted">From</dt>
                        <dd className="font-medium text-ink">
                            {mailEffective.from_name} &lt;{mailEffective.from_address}&gt;
                        </dd>
                    </div>
                    <div>
                        <dt className="text-muted">Application URL for links</dt>
                        <dd className="font-medium text-ink">{mailEffective.application_url}</dd>
                    </div>
                    <div>
                        <dt className="text-muted">SMTP ready</dt>
                        <dd className="font-medium text-ink">
                            {mailEffective.smtp_configured ? 'Yes' : 'No — incomplete or disabled'}
                        </dd>
                    </div>
                </dl>
            </div>

            <div className="rounded-xl border border-line bg-white p-6">
                <h3 className="text-base font-semibold text-ink">Diagnostics</h3>
                <p className="mt-1 text-sm text-muted">
                    Connection tests do not prove inbox delivery. Use a test recipient you control.
                </p>
                <div className="mt-4 flex flex-wrap gap-2">
                    <DiagButton
                        label="Validate configuration"
                        busy={busy === 'validate'}
                        onClick={() => runDiagnostic('validate', '/admin/settings/email/validate')}
                    />
                    <DiagButton
                        label="Test SMTP connection"
                        busy={busy === 'connection'}
                        onClick={() => runDiagnostic('connection', '/admin/settings/email/test-connection')}
                    />
                    <DiagButton
                        label="Preview verification email"
                        busy={busy === 'preview'}
                        onClick={() => runDiagnostic('preview', '/admin/settings/email/preview-verification')}
                    />
                </div>
                <div className="mt-4 flex max-w-md flex-col gap-2 sm:flex-row">
                    <input
                        className={inputClass}
                        type="email"
                        value={testRecipient}
                        onChange={(e) => setTestRecipient(e.target.value)}
                        placeholder="Test recipient email"
                    />
                    <DiagButton
                        label="Send test email"
                        busy={busy === 'send'}
                        onClick={() =>
                            runDiagnostic('send', '/admin/settings/email/send-test', { recipient: testRecipient })
                        }
                    />
                </div>
                {diagnosticMessage ? (
                    <p className="mt-3 rounded-md bg-surface px-3 py-2 text-sm text-ink">{diagnosticMessage}</p>
                ) : null}
                {linkCheck !== null ? (
                    <p className="mt-2 text-sm text-muted">
                        Link rendering check: {linkCheck ? 'PASS' : 'FAIL'}
                    </p>
                ) : null}
                {mailSettings.last_diagnostic ? (
                    <p className="mt-3 text-xs text-muted">
                        Last diagnostic: {mailSettings.last_diagnostic.outcome} ({mailSettings.last_diagnostic.at})
                    </p>
                ) : null}
                {previewText ? (
                    <details className="mt-4">
                        <summary className="cursor-pointer text-sm font-semibold text-ink">Plain-text preview</summary>
                        <pre className="mt-2 max-h-48 overflow-auto rounded-md bg-surface p-3 text-xs">{previewText}</pre>
                    </details>
                ) : null}
                {previewHtml ? (
                    <details className="mt-4">
                        <summary className="cursor-pointer text-sm font-semibold text-ink">HTML preview</summary>
                        <iframe
                            title="Verification email preview"
                            className="mt-2 h-64 w-full rounded-md border border-line bg-white"
                            srcDoc={previewHtml}
                        />
                    </details>
                ) : null}
            </div>

            <div className="rounded-xl border border-line bg-white p-6">
                <h3 className="text-base font-semibold text-ink">External prerequisites</h3>
                <ul className="mt-3 space-y-3 text-sm">
                    {Object.entries(mailPrerequisites).map(([key, item]) => (
                        <li key={key} className="rounded-md border border-line px-3 py-2">
                            <p className="font-semibold text-ink">{item.label}</p>
                            <p className="text-muted">Status: {item.status}</p>
                            <p className="mt-1 text-ink">{item.instruction}</p>
                        </li>
                    ))}
                </ul>
            </div>
        </section>
    );
}

function Field({
    label,
    hint,
    error,
    children,
}: {
    label: string;
    hint?: string;
    error?: string;
    children: ReactNode;
}) {
    return (
        <label className="block space-y-1 text-sm">
            <span className="font-medium text-ink">{label}</span>
            {children}
            {hint ? <span className="block text-xs text-muted">{hint}</span> : null}
            {error ? <span className="block text-xs text-red-600">{error}</span> : null}
        </label>
    );
}

function DiagButton({ label, onClick, busy }: { label: string; onClick: () => void; busy: boolean }) {
    return (
        <button
            type="button"
            disabled={busy}
            onClick={onClick}
            className="rounded-md border border-line bg-white px-3 py-2 text-sm font-semibold text-ink hover:bg-surface disabled:opacity-60"
        >
            {busy ? 'Working…' : label}
        </button>
    );
}
