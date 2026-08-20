import type { ChangeEvent, ComponentProps, Ref } from 'react';
import { useState } from 'react';
import { Input } from '@/components/ui/input';

/**
 * Formats a raw value as a Brazilian phone number, discarding non-digits.
 *
 * Landlines (10 digits) render as (11) 3456-7890 and mobiles (11 digits) as
 * (11) 98765-4321. Partial input is formatted as far as the digits allow.
 */
export function formatPhone(value: string): string {
    const digits = value.replace(/\D/g, '').slice(0, 11);

    if (digits.length <= 2) {
        return digits;
    }

    const area = digits.slice(0, 2);
    const subscriber = digits.slice(2);

    if (subscriber.length <= 4) {
        return `(${area}) ${subscriber}`;
    }

    const splitAt = subscriber.length > 8 ? 5 : 4;

    return `(${area}) ${subscriber.slice(0, splitAt)}-${subscriber.slice(splitAt)}`;
}

type Props = Omit<
    ComponentProps<'input'>,
    'type' | 'value' | 'defaultValue' | 'onChange'
> & {
    defaultValue?: string;
    ref?: Ref<HTMLInputElement>;
};

export default function PhoneInput({ defaultValue = '', ...props }: Props) {
    const [value, setValue] = useState(() => formatPhone(defaultValue));

    return (
        <Input
            type="tel"
            inputMode="numeric"
            autoComplete="off"
            maxLength={15}
            placeholder="(11) 98765-4321"
            value={value}
            onChange={(event: ChangeEvent<HTMLInputElement>) =>
                setValue(formatPhone(event.target.value))
            }
            {...props}
        />
    );
}
