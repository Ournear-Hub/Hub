type HoneypotFieldProps = {
    value: string;
    onChange: (value: string) => void;
};

export default function HoneypotField({ value, onChange }: HoneypotFieldProps) {
    return (
        <div className="absolute -left-[10000px] top-auto h-px w-px overflow-hidden" aria-hidden="true">
            <label htmlFor="fax">Fax</label>
            <input
                id="fax"
                name="fax"
                type="text"
                tabIndex={-1}
                autoComplete="off"
                value={value}
                onChange={(event) => onChange(event.currentTarget.value)}
            />
        </div>
    );
}
