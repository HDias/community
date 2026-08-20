import type { ChangeEvent, ComponentProps, Ref } from 'react';
import { useState } from 'react';
import { Input } from '@/components/ui/input';

/**
 * Formats a raw value as a CPF (000.000.000-00), discarding non-digits.
 */
export function formatCpf(value: string): string {
    const digits = value.replace(/\D/g, '').slice(0, 11);

    return digits
        .replace(/^(\d{3})(\d)/, '$1.$2')
        .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
        .replace(/^(\d{3})\.(\d{3})\.(\d{3})(\d)/, '$1.$2.$3-$4');
}

type Props = Omit<
    ComponentProps<'input'>,
    'type' | 'value' | 'defaultValue' | 'onChange'
> & {
    defaultValue?: string;
    ref?: Ref<HTMLInputElement>;
};

export default function CpfInput({ defaultValue = '', ...props }: Props) {
    const [value, setValue] = useState(() => formatCpf(defaultValue));

    return (
        <Input
            type="text"
            inputMode="numeric"
            autoComplete="off"
            maxLength={14}
            placeholder="000.000.000-00"
            value={value}
            onChange={(event: ChangeEvent<HTMLInputElement>) =>
                setValue(formatCpf(event.target.value))
            }
            {...props}
        />
    );
}
