import type { ChangeEvent, ComponentProps, Ref } from 'react';
import { useState } from 'react';
import { Input } from '@/components/ui/input';

/**
 * Formats a raw value as a CNPJ (00.000.000/0000-00), discarding non-digits.
 */
export function formatCnpj(value: string): string {
    const digits = value.replace(/\D/g, '').slice(0, 14);

    return digits
        .replace(/^(\d{2})(\d)/, '$1.$2')
        .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
        .replace(/^(\d{2})\.(\d{3})\.(\d{3})(\d)/, '$1.$2.$3/$4')
        .replace(/^(\d{2})\.(\d{3})\.(\d{3})\/(\d{4})(\d)/, '$1.$2.$3/$4-$5');
}

type Props = Omit<
    ComponentProps<'input'>,
    'type' | 'value' | 'defaultValue' | 'onChange'
> & {
    defaultValue?: string;
    ref?: Ref<HTMLInputElement>;
};

export default function CnpjInput({ defaultValue = '', ...props }: Props) {
    const [value, setValue] = useState(() => formatCnpj(defaultValue));

    return (
        <Input
            type="text"
            inputMode="numeric"
            autoComplete="off"
            maxLength={18}
            placeholder="00.000.000/0000-00"
            value={value}
            onChange={(event: ChangeEvent<HTMLInputElement>) =>
                setValue(formatCnpj(event.target.value))
            }
            {...props}
        />
    );
}
