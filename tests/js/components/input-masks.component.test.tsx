import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { UserEvent } from '@testing-library/user-event';
import { beforeEach, describe, expect, it } from 'vitest';
import CpfInput from '@/components/cpf-input';
import PhoneInput from '@/components/phone-input';

let user: UserEvent;

beforeEach(() => {
    user = userEvent.setup();
});

/**
 * Both masks wrap `Input` the same way, so the shared contract is asserted
 * once per component. The raw-in/masked-out case is the one the edit member
 * page depends on: it hands these components unformatted database values.
 */
const cases = [
    {
        label: 'CpfInput',
        Component: CpfInput,
        rawValue: '12345678901',
        maskedValue: '123.456.789-01',
        type: 'text',
        maxLength: '14',
        placeholder: '000.000.000-00',
        partial: '1234',
        partialMasked: '123.4',
        afterBackspace: '123',
    },
    {
        label: 'PhoneInput',
        Component: PhoneInput,
        rawValue: '11987654321',
        maskedValue: '(11) 98765-4321',
        type: 'tel',
        maxLength: '15',
        placeholder: '(11) 98765-4321',
        partial: '113',
        partialMasked: '(11) 3',
        afterBackspace: '11',
    },
];

describe.each(cases)(
    '$label',
    ({
        Component,
        rawValue,
        maskedValue,
        type,
        maxLength,
        placeholder,
        partial,
        partialMasked,
        afterBackspace,
    }) => {
        it('masks a raw default value on first render', () => {
            render(<Component defaultValue={rawValue} />);

            expect(screen.getByRole('textbox')).toHaveValue(maskedValue);
        });

        it('renders empty when given no default value', () => {
            render(<Component />);

            expect(screen.getByRole('textbox')).toHaveValue('');
        });

        it('renders empty when given an empty default value', () => {
            render(<Component defaultValue="" />);

            expect(screen.getByRole('textbox')).toHaveValue('');
        });

        it('masks the value as the user types', async () => {
            render(<Component />);

            const input = screen.getByRole('textbox');
            await user.type(input, partial);

            expect(input).toHaveValue(partialMasked);
        });

        it('discards typed characters that are not digits', async () => {
            render(<Component />);

            const input = screen.getByRole('textbox');
            await user.type(input, 'abc-!');

            expect(input).toHaveValue('');
        });

        it('reformats when the user deletes a character', async () => {
            render(<Component defaultValue={partial} />);

            const input = screen.getByRole('textbox');
            await user.type(input, '{backspace}');

            expect(input).toHaveValue(afterBackspace);
        });

        it('caps the input at the masked length', () => {
            render(<Component />);

            expect(screen.getByRole('textbox')).toHaveAttribute(
                'maxlength',
                maxLength,
            );
        });

        it('opts out of autocomplete and asks for a numeric keypad', () => {
            render(<Component />);

            const input = screen.getByRole('textbox');

            expect(input).toHaveAttribute('type', type);
            expect(input).toHaveAttribute('inputmode', 'numeric');
            expect(input).toHaveAttribute('autocomplete', 'off');
            expect(input).toHaveAttribute('placeholder', placeholder);
        });

        it('forwards the attributes the form needs', () => {
            render(<Component id="field" name="field" required />);

            const input = screen.getByRole('textbox');

            expect(input).toHaveAttribute('id', 'field');
            expect(input).toHaveAttribute('name', 'field');
            expect(input).toBeRequired();
        });

        it('keeps the value it started with when the default changes later', () => {
            const { rerender } = render(<Component defaultValue={partial} />);

            rerender(<Component defaultValue={rawValue} />);

            expect(screen.getByRole('textbox')).toHaveValue(partialMasked);
        });
    },
);
