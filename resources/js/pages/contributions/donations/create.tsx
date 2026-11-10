import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import { useState } from 'react';
import CnpjInput from '@/components/cnpj-input';
import CpfInput from '@/components/cpf-input';
import InputError from '@/components/input-error';
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
import { Textarea } from '@/components/ui/textarea';

type Community = {
    id: number;
    name: string;
    slug: string;
};

type Member = {
    id: number;
    name: string;
};

type DonorType = 'member' | 'individual' | 'organization';

type Props = {
    community: Community;
    members: Member[];
    referenceMonth: string;
};

const donorTypes: { value: DonorType; label: string }[] = [
    { value: 'member', label: 'Member' },
    { value: 'individual', label: 'Individual' },
    { value: 'organization', label: 'Legal entity' },
];

export default function DonationsCreate({
    community,
    members,
    referenceMonth,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Contributions',
                href: `/contributions?community=${community.id}`,
            },
            {
                title: 'Donations',
                href: `/contributions/donations?community=${community.id}&month=${referenceMonth}`,
            },
            {
                title: 'Register Donation',
                href: `/contributions/donations/create?community=${community.id}`,
            },
        ],
    });

    const [donorType, setDonorType] = useState<DonorType>('member');

    return (
        <>
            <Head title={`Register Donation - ${community.name}`} />
            <div className="mx-auto w-full max-w-2xl px-6 py-8">
                <div className="mb-6 flex items-center justify-between gap-3">
                    <h1 className="text-lg font-semibold">Register Donation</h1>
                    <Button size="sm" variant="outline" asChild>
                        <Link
                            href={`/contributions/donations?community=${community.id}&month=${referenceMonth}`}
                        >
                            Back to donations
                        </Link>
                    </Button>
                </div>

                <Form
                    action={`/contributions/donations?community=${community.id}`}
                    method="post"
                    className="space-y-6"
                >
                    {({ errors, processing }) => (
                        <>
                            <fieldset className="grid gap-3">
                                <legend className="text-sm font-medium">
                                    Donor
                                </legend>
                                <div className="flex flex-wrap gap-4">
                                    {donorTypes.map((type) => (
                                        <label
                                            key={type.value}
                                            className="flex items-center gap-2 text-sm"
                                        >
                                            <input
                                                type="radio"
                                                name="donor_type"
                                                value={type.value}
                                                checked={
                                                    donorType === type.value
                                                }
                                                onChange={() =>
                                                    setDonorType(type.value)
                                                }
                                            />
                                            {type.label}
                                        </label>
                                    ))}
                                </div>
                                <InputError message={errors.donor_type} />
                            </fieldset>

                            {donorType === 'member' ? (
                                <div className="grid gap-2">
                                    <Label htmlFor="donor-member">
                                        Member *
                                    </Label>
                                    <Select name="user_id" required>
                                        <SelectTrigger
                                            id="donor-member"
                                            className="w-full"
                                        >
                                            <SelectValue placeholder="Select a member" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {members.map((member) => (
                                                <SelectItem
                                                    key={member.id}
                                                    value={String(member.id)}
                                                >
                                                    {member.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.user_id} />
                                </div>
                            ) : (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="donor-name">
                                            {donorType === 'individual'
                                                ? 'Name *'
                                                : 'Corporate name *'}
                                        </Label>
                                        <Input
                                            id="donor-name"
                                            name="donor_name"
                                            required
                                        />
                                        <InputError
                                            message={errors.donor_name}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="donor-document">
                                            {donorType === 'individual'
                                                ? 'CPF *'
                                                : 'CNPJ *'}
                                        </Label>
                                        {donorType === 'individual' ? (
                                            <CpfInput
                                                id="donor-document"
                                                name="donor_document"
                                                required
                                            />
                                        ) : (
                                            <CnpjInput
                                                id="donor-document"
                                                name="donor_document"
                                                required
                                            />
                                        )}
                                        <InputError
                                            message={errors.donor_document}
                                        />
                                    </div>
                                </>
                            )}

                            <div className="grid gap-2">
                                <Label htmlFor="donation-amount">
                                    Amount *
                                </Label>
                                <Input
                                    id="donation-amount"
                                    name="amount"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    required
                                />
                                <InputError message={errors.amount} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="donation-notes">Notes</Label>
                                <Textarea
                                    id="donation-notes"
                                    name="notes"
                                    rows={4}
                                />
                                <InputError message={errors.notes} />
                            </div>

                            <div className="flex justify-end">
                                <Button type="submit" disabled={processing}>
                                    Register donation
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
