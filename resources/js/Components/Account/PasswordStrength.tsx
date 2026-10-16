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

function strengthLabel(score: number, hasInput: boolean): string {
    if (!hasInput) {
        return '';
    }
    if (score <= 1) {
        return 'Weak';
    }
    if (score === 2) {
        return 'Medium';
    }
    if (score === 3) {
        return 'Strong';
    }

    return 'Very strong';
}

function barColor(score: number): string {
    if (score >= 4) {
        return 'bg-success';
    }
    if (score >= 3) {
        return 'bg-brand';
    }
    if (score >= 2) {
        return 'bg-warning';
    }

    return 'bg-danger';
}

export default function PasswordStrength({ password }: PasswordStrengthProps) {
    const score = scorePassword(password);
    const width = password.length === 0 ? 0 : (score / 5) * 100;
    const label = strengthLabel(score, password.length > 0);
    const checks = [
        { label: 'Minimum length', ok: password.length >= 8 },
        { label: 'Uppercase letter', ok: /[A-Z]/.test(password) },
        { label: 'Lowercase letter', ok: /[a-z]/.test(password) },
        { label: 'Number', ok: /\d/.test(password) },
        { label: 'Special character', ok: /[^A-Za-z0-9]/.test(password) },
    ];

    return (
        <div className="space-y-1.5" aria-live="polite">
            <div className="flex items-center gap-3">
                <div className="h-1.5 min-w-0 flex-1 overflow-hidden rounded-full bg-[#E8EEEE]" aria-hidden="true">
                    <div
                        className={`h-full rounded-full transition-[width] duration-200 ${barColor(score)}`}
                        style={{ width: `${width}%` }}
                    />
                </div>
                {label ? (
                    <span className="shrink-0 text-xs font-semibold text-ink" aria-hidden="true">
                        {label}
                    </span>
                ) : null}
            </div>
            <ul className="flex flex-wrap gap-x-3 gap-y-1 text-[0.6875rem] leading-snug text-muted">
                {checks.map((check) => (
                    <li key={check.label} className="inline-flex items-center gap-1">
                        <span className={check.ok ? 'text-success' : 'text-disabled'} aria-hidden="true">
                            {check.ok ? '✓' : '○'}
                        </span>
                        <span>{check.label}</span>
                    </li>
                ))}
            </ul>
        </div>
    );
}
