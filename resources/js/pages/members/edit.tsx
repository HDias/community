import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import { useState } from 'react';
import CpfInput from '@/components/cpf-input';
import InputError from '@/components/input-error';
import PhoneInput from '@/components/phone-input';
import { Button } from '@/components/ui/button';
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
import type { Profile } from '@/types/auth';

type Member = {
    id: number;
    name: string;
    email: string;
    profile: Profile | null;
};

type Props = {
    member: Member;
    community: { id: number; name: string; slug: string };
};

export default function MembersEdit({ member, community }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Members', href: `/members?community=${community.id}` },
            {
                title: member.name,
                href: `/members/${member.id}/edit?community=${community.id}`,
            },
        ],
    });

    const [selectedState, setSelectedState] = useState(
        member.profile?.address_state ?? '',
    );
    const { states, loading: loadingStates } = useStates();
    const { cities, loading: loadingCities } = useCities(selectedState);

    return (
        <>
            <Head title={`Edit ${member.name} - ${community.name}`} />
            <div className="mx-auto w-full max-w-2xl px-6 py-8">
                <h1 className="mb-6 text-lg font-semibold">Edit Member</h1>

                <Form
                    action={`/members/${member.id}?community=${community.id}`}
                    method="put"
                    className="space-y-6"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name *</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        required
                                        defaultValue={member.name}
                                    />
                                    <InputError message={errors.name} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email *</Label>
                                    <Input
                                        id="email"
                                        name="email"
                                        type="email"
                                        required
                                        defaultValue={member.email}
                                    />
                                    <InputError message={errors.email} />
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="cpf">CPF *</Label>
                                    <CpfInput
                                        id="cpf"
                                        name="cpf"
                                        required
                                        defaultValue={member.profile?.cpf ?? ''}
                                    />
                                    <InputError message={errors.cpf} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="birth_date">
                                        Birth Date *
                                    </Label>
                                    <Input
                                        id="birth_date"
                                        name="birth_date"
                                        type="date"
                                        required
                                        defaultValue={
                                            member.profile?.birth_date ?? ''
                                        }
                                    />
                                    <InputError message={errors.birth_date} />
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="social_name">
                                        Social Name
                                    </Label>
                                    <Input
                                        id="social_name"
                                        name="social_name"
                                        defaultValue={
                                            member.profile?.social_name ?? ''
                                        }
                                    />
                                    <InputError message={errors.social_name} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="nickname">Nickname</Label>
                                    <Input
                                        id="nickname"
                                        name="nickname"
                                        defaultValue={
                                            member.profile?.nickname ?? ''
                                        }
                                    />
                                    <InputError message={errors.nickname} />
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="phone">Phone</Label>
                                    <PhoneInput
                                        id="phone"
                                        name="phone"
                                        defaultValue={
                                            member.profile?.phone ?? ''
                                        }
                                    />
                                    <InputError message={errors.phone} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="profession">
                                        Profession
                                    </Label>
                                    <Input
                                        id="profession"
                                        name="profession"
                                        defaultValue={
                                            member.profile?.profession ?? ''
                                        }
                                    />
                                    <InputError message={errors.profession} />
                                </div>
                            </div>

                            <h2 className="text-md pt-4 font-medium">
                                Address
                            </h2>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="address_street">
                                        Street
                                    </Label>
                                    <Input
                                        id="address_street"
                                        name="address_street"
                                        defaultValue={
                                            member.profile?.address_street ?? ''
                                        }
                                    />
                                    <InputError
                                        message={errors.address_street}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="address_number">
                                        Number
                                    </Label>
                                    <Input
                                        id="address_number"
                                        name="address_number"
                                        defaultValue={
                                            member.profile?.address_number ?? ''
                                        }
                                    />
                                    <InputError
                                        message={errors.address_number}
                                    />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="address_neighborhood">
                                    Neighborhood
                                </Label>
                                <Input
                                    id="address_neighborhood"
                                    name="address_neighborhood"
                                    defaultValue={
                                        member.profile?.address_neighborhood ??
                                        ''
                                    }
                                />
                                <InputError
                                    message={errors.address_neighborhood}
                                />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-[1fr_2fr]">
                                <div className="grid gap-2">
                                    <Label htmlFor="address_state">State</Label>
                                    <Select
                                        name="address_state"
                                        value={selectedState}
                                        onValueChange={setSelectedState}
                                    >
                                        <SelectTrigger
                                            id="address_state"
                                            className="w-full"
                                        >
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
                                                <SelectItem
                                                    key={s.sigla}
                                                    value={s.sigla}
                                                >
                                                    {s.sigla} - {s.nome}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={errors.address_state}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="address_city">City</Label>
                                    {selectedState ? (
                                        <Select
                                            name="address_city"
                                            defaultValue={
                                                member.profile?.address_city ??
                                                ''
                                            }
                                        >
                                            <SelectTrigger
                                                id="address_city"
                                                className="w-full"
                                            >
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
                                            id="address_city"
                                            name="address_city"
                                            disabled
                                            placeholder="Select a state first"
                                        />
                                    )}
                                    <InputError message={errors.address_city} />
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="address_zip">
                                        ZIP Code
                                    </Label>
                                    <Input
                                        id="address_zip"
                                        name="address_zip"
                                        defaultValue={
                                            member.profile?.address_zip ?? ''
                                        }
                                    />
                                    <InputError message={errors.address_zip} />
                                </div>
                            </div>

                            <div className="flex items-center justify-between pt-4">
                                <Button type="button" variant="ghost" asChild>
                                    <Link
                                        href={`/members?community=${community.id}`}
                                    >
                                        Cancel
                                    </Link>
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    Update Member
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
