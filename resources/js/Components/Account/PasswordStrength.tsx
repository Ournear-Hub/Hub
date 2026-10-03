type PasswordStrengthProps = {
    password: string;
};

function scorePassword(password: string): number {
    let score = 0;
    if (password.length >= 8) score += 1;
    if (/[A-Z]/.test(password)) score += 1;
    if (/[a-z]/.test(password)) score += 1;
    if (/\d/.test(password)) score += 1;
    if (/[^A-Za-z0-9]/.test(password)) score += 1;
    return score;
}

const labels = ['Weak', 'Weak', 'Fair', 'Good', 'Strong', 'Strong'];

export default function PasswordStrength({ password }: PasswordStrengthProps) {
    const score = scorePassword(password);
    const width = password.length === 0 ? 0 : (score / 5) * 100;
    const label = password.length === 0 ? '' : labels[score] ?? 'Weak';
    const checks = [
        { label: 'Minimum length', ok: password.length >= 8 },
        { label: 'Uppercase letter', ok: /[A-Z]/.test(password) },
        { label: 'Lowercase letter', ok: /[a-z]/.test(password) },
        { label: 'Number', ok: /\d/.test(password) },
        { label: 'Special character', ok: /[^A-Za-z0-9]/.test(password) },
    ];

    return (
        <div className="space-y-2">
            <div className="h-2 overflow-hidden rounded-full bg-surface" aria-hidden="true">
                <div
                    className={`h-full rounded-full ${score >= 4 ? 'bg-success' : score >= 3 ? 'bg-brand' : 'bg-danger'}`}
                    style={{ width: `${width}%` }}
                />
            </div>
            {label ? <p className="text-xs font-medium text-ink">{label}</p> : null}
            <ul className="space-y-1 text-xs text-muted">
                {checks.map((check) => (
                    <li key={check.label}>
                        {check.ok ? '✓' : '○'} {check.label}
                    </li>
                ))}
            </ul>
        </div>
    );
}
