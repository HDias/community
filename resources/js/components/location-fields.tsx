import { useState } from 'react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useCities, useStates } from '@/hooks/use-brasil-api';

type Props = {
    defaultAddress?: string;
    defaultState?: string;
    defaultCity?: string;
};

export function LocationFields({
    defaultAddress,
    defaultState,
    defaultCity,
}: Props) {
    const [selectedState, setSelectedState] = useState(defaultState ?? '');
    const { states, loading: loadingStates } = useStates();
    const { cities, loading: loadingCities } = useCities(selectedState);

    return (
        <div className="space-y-4">
            <div className="grid gap-2">
                <Label htmlFor="address">Address</Label>
                <Input
                    id="address"
                    name="address"
                    defaultValue={defaultAddress ?? ''}
                    placeholder="Estrada do Quilombo, s/n"
                />
            </div>
            <div className="grid grid-cols-[1fr_2fr] gap-4">
                <div className="grid gap-2">
                    <Label htmlFor="state">State</Label>
                    <Select
                        name="state"
                        value={selectedState}
                        onValueChange={setSelectedState}
                    >
                        <SelectTrigger id="state" className="w-full">
                            <SelectValue
                                placeholder={
                                    loadingStates
                                        ? 'Loading...'
                                        : 'Select state'
                                }
                            />
                        </SelectTrigger>
                        <SelectContent>
                            {states.map((s) => (
                                <SelectItem key={s.sigla} value={s.sigla}>
                                    {s.sigla} - {s.nome}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="city">City</Label>
                    {selectedState ? (
                        <Select name="city" defaultValue={defaultCity ?? ''}>
                            <SelectTrigger id="city" className="w-full">
                                <SelectValue
                                    placeholder={
                                        loadingCities
                                            ? 'Loading...'
                                            : 'Select city'
                                    }
                                />
                            </SelectTrigger>
                            <SelectContent>
                                {cities.map((c) => (
                                    <SelectItem
                                        key={c.codigo_ibge}
                                        value={c.nome}
                                    >
                                        {c.nome}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    ) : (
                        <Input
                            id="city"
                            name="city"
                            disabled
                            placeholder="Select a state first"
                        />
                    )}
                </div>
            </div>
        </div>
    );
}
