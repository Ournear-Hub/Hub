/**
 * @vitest-environment jsdom
 */
import Input from '@/Components/atoms/Input';
import FormField from '@/Components/molecules/FormField';
import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it } from 'vitest';

describe('FormField', () => {
    afterEach(() => {
        cleanup();
    });

    it('associates label with the nested control', () => {
        render(
            <FormField id="email" label="Work email">
                <Input name="email" />
            </FormField>,
        );

        const control = screen.getByRole('textbox', { name: 'Work email' });
        expect(control.getAttribute('id')).toBe('email');
    });

    it('exposes hint text via aria-describedby on the control', () => {
        render(
            <FormField id="ref" label="Reference" hint="Optional internal reference code.">
                <Input name="ref" />
            </FormField>,
        );

        const control = screen.getByRole('textbox', { name: 'Reference' });
        const describedBy = control.getAttribute('aria-describedby') ?? '';
        expect(describedBy).toContain('ref-hint');
        expect(screen.getByText('Optional internal reference code.')).toBeTruthy();
    });

    it('shows required indicator on the label when required', () => {
        render(
            <FormField id="code" label="Employer code" required>
                <Input name="code" />
            </FormField>,
        );

        expect(screen.getByText('(required)')).toBeTruthy();
    });

    it('renders error alert and wires aria-invalid on supported controls', () => {
        render(
            <FormField id="phone" label="Phone" error="Enter a valid phone number.">
                <Input name="phone" />
            </FormField>,
        );

        const control = screen.getByRole('textbox', { name: 'Phone' });
        expect(control.getAttribute('aria-invalid')).toBe('true');
        expect(control.getAttribute('aria-describedby')).toContain('phone-error');

        const alert = screen.getByRole('alert');
        expect(alert.textContent).toBe('Enter a valid phone number.');
    });

    it('prefers error over hint when both would apply', () => {
        render(
            <FormField id="city" label="City" hint="City or town name." error="City is required.">
                <Input name="city" />
            </FormField>,
        );

        expect(screen.queryByText('City or town name.')).toBeNull();
        expect(screen.getByRole('alert')).toBeTruthy();
        const control = screen.getByRole('textbox', { name: 'City' });
        expect(control.getAttribute('aria-describedby')).toContain('city-error');
        expect(control.getAttribute('aria-describedby')).not.toContain('city-hint');
    });
});
