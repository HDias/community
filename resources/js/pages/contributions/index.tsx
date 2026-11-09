import { Form, Head, Link, router, setLayoutProps } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
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

type CommunityOption = {
    id: number;
    name: string;
};

type Member = {
    id: number;
    name: string;
    paid: boolean;
};

type Props = {
    communities: CommunityOption[];
    community: { id: number; name: string; slug: string } | null;
    members: Member[];
    monthlyAmount: string | null;
    referenceMonth: string;
};

export default function ContributionsIndex({
    communities,
    community,
    members,
    monthlyAmount,
    referenceMonth,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Contributions', href: '/contributions' },
            ...(community
                ? [
                      {
                          title: community.name,
                          href: `/contributions?community=${community.id}`,
                      },
                  ]
                : []),
        ],
    });

    const [month, setMonth] = useState(referenceMonth);

    function viewMonth() {
        if (!community) {
            return;
        }

        router.visit(`/contributions?community=${community.id}&month=${month}`);
    }

    function handleCommunityChange(value: string) {
        router.visit(`/contributions?community=${value}`);
    }

    return (
        <>
            <Head
                title={
                    community
                        ? `Contributions - ${community.name}`
                        : 'Contributions'
                }
            />
            <div className="flex h-full flex-1 flex-col overflow-hidden">
                <div className="flex items-center justify-between border-b px-6 py-3">
                    <div className="flex items-center gap-3">
                        <h1 className="text-lg font-semibold">Contributions</h1>
                        <Select
                            value={community ? String(community.id) : undefined}
                            onValueChange={handleCommunityChange}
                            disabled={communities.length <= 1}
                        >
                            <SelectTrigger size="sm" className="w-48">
                                <SelectValue placeholder="Select community" />
                            </SelectTrigger>
                            <SelectContent>
                                {communities.map((option) => (
                                    <SelectItem
                                        key={option.id}
                                        value={String(option.id)}
                                    >
                                        {option.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    {community && (
                        <div className="flex items-center gap-2">
                            <Button size="sm" asChild>
                                <Link
                                    href={`/contributions/donations/create?community=${community.id}`}
                                >
                                    + Register donation
                                </Link>
                            </Button>
                            <Button size="sm" variant="outline" asChild>
                                <Link
                                    href={`/contributions/settings?community=${community.id}`}
                                >
                                    Settings
                                </Link>
                            </Button>
                            <Button size="sm" variant="outline" asChild>
                                <Link
                                    href={`/contributions/report?community=${community.id}`}
                                >
                                    Defaulters report
                                </Link>
                            </Button>
                        </div>
                    )}
                </div>

                {!community ? (
                    <div className="flex flex-1 items-center justify-center p-6">
                        <p className="text-sm text-muted-foreground">
                            Select a community to manage its contributions.
                        </p>
                    </div>
                ) : (
                    <div className="flex-1 space-y-8 overflow-y-auto p-6">
                        <div className="flex flex-wrap items-end gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="reference-month">Month</Label>
                                <Input
                                    id="reference-month"
                                    type="month"
                                    value={month}
                                    onChange={(event) =>
                                        setMonth(event.target.value)
                                    }
                                />
                            </div>
                            <Button type="button" onClick={viewMonth}>
                                View
                            </Button>
                            <p className="text-sm text-muted-foreground">
                                Monthly amount:{' '}
                                <span className="font-medium text-foreground">
                                    {monthlyAmount ?? 'not configured'}
                                </span>
                            </p>
                        </div>

                        <table className="w-full text-sm">
                            <thead className="border-b bg-muted/50">
                                <tr>
                                    <th className="px-4 py-2 text-left font-medium">
                                        Member
                                    </th>
                                    <th className="px-4 py-2 text-left font-medium">
                                        Status
                                    </th>
                                    <th className="px-4 py-2 text-right font-medium">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {members.map((member) => (
                                    <tr
                                        key={member.id}
                                        className="hover:bg-muted/50"
                                    >
                                        <td className="px-4 py-2">
                                            {member.name}
                                        </td>
                                        <td className="px-4 py-2">
                                            <Badge
                                                variant={
                                                    member.paid
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {member.paid
                                                    ? 'Paid'
                                                    : 'Pending'}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-2 text-right">
                                            {!member.paid && (
                                                <Form
                                                    action={`/contributions?community=${community.id}`}
                                                    method="post"
                                                >
                                                    {({
                                                        errors,
                                                        processing,
                                                    }) => (
                                                        <>
                                                            <input
                                                                type="hidden"
                                                                name="user_id"
                                                                value={
                                                                    member.id
                                                                }
                                                            />
                                                            <input
                                                                type="hidden"
                                                                name="reference_month"
                                                                value={`${month}-01`}
                                                            />
                                                            <Button
                                                                type="submit"
                                                                size="sm"
                                                                disabled={
                                                                    processing
                                                                }
                                                            >
                                                                Register payment
                                                            </Button>
                                                            <InputError
                                                                message={
                                                                    errors.reference_month
                                                                }
                                                            />
                                                        </>
                                                    )}
                                                </Form>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                                {members.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={3}
                                            className="px-4 py-8 text-center text-muted-foreground"
                                        >
                                            No members in this community yet.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </>
    );
}
