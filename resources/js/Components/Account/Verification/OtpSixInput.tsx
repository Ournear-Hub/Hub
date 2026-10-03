import { useRef, type ClipboardEvent, type KeyboardEvent } from 'react';

type Props = {
    idPrefix: string;
    value: string;
    onChange: (value: string) => void;
    disabled?: boolean;
    error?: string | null;
};

const DIGITS = 6;

function normalizeDigits(input: string): string {
    return input.replace(/\D/g, '').slice(0, DIGITS);
}

export default function OtpSixInput({ idPrefix, value, onChange, disabled = false, error }: Props) {
    const refs = useRef<Array<HTMLInputElement | null>>([]);
    const digits = normalizeDigits(value).padEnd(DIGITS, ' ').split('').map((char) => (char === ' ' ? '' : char));

    const commit = (next: string) => {
        onChange(normalizeDigits(next));
    };

    const focusIndex = (index: number) => {
        refs.current[index]?.focus();
        refs.current[index]?.select();
    };

    const handleChange = (index: number, digit: string) => {
        const cleaned = normalizeDigits(digit);
        if (cleaned.length === 0) {
            const chars = [...digits];
            chars[index] = '';
            commit(chars.join(''));
            return;
        }

        if (cleaned.length >= DIGITS) {
            commit(cleaned);
            focusIndex(DIGITS - 1);
            return;
        }

        const chars = [...digits];
        chars[index] = cleaned.slice(-1);
        const combined = chars.join('');
        commit(combined);
        if (index < DIGITS - 1) {
            focusIndex(index + 1);
        }
    };

    const handleKeyDown = (index: number, event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key === 'Backspace' && !digits[index] && index > 0) {
            focusIndex(index - 1);
        }
    };

    const handlePaste = (event: ClipboardEvent<HTMLInputElement>) => {
        event.preventDefault();
        const pasted = event.clipboardData.getData('text');
        commit(pasted);
        const length = normalizeDigits(pasted).length;
        focusIndex(Math.min(DIGITS - 1, Math.max(0, length - 1)));
    };

    return (
        <div>
            <div className="flex flex-wrap gap-2" role="group" aria-label="Six digit verification code">
                {digits.map((digit, index) => (
                    <input
                        key={`${idPrefix}-${index}`}
                        ref={(element) => {
                            refs.current[index] = element;
                        }}
                        id={`${idPrefix}-${index}`}
                        type="text"
                        inputMode="numeric"
                        autoComplete={index === 0 ? 'one-time-code' : 'off'}
                        pattern="[0-9]*"
                        maxLength={1}
                        disabled={disabled}
                        value={digit}
                        aria-invalid={error ? true : undefined}
                        className="h-12 w-11 rounded-md border border-line text-center text-lg font-semibold text-ink outline-none focus:border-brand disabled:bg-surface sm:h-11 sm:w-10"
                        onChange={(event) => handleChange(index, event.target.value)}
                        onKeyDown={(event) => handleKeyDown(index, event)}
                        onPaste={handlePaste}
                    />
                ))}
            </div>
            {error ? (
                <p className="mt-2 text-sm text-danger" role="alert">
                    {error}
                </p>
            ) : null}
        </div>
    );
}
