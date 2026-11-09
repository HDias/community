import { Form, Head, Link, router, setLayoutProps } from '@inertiajs/react';
import type { ChangeEvent } from 'react';
import { useRef, useState } from 'react';
import { formatCpf } from '@/components/cpf-input';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
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
    cpf: string | null;
    paid: boolean;
};

type PaginatedMembers = {
    data: Member[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

type Props = {
    communities: CommunityOption[];
    community: { id: number; name: string; slug: string } | null;
    members: PaginatedMembers | [];
    monthlyAmount: string | null;
    referenceMonth: string;
    search: string | null;
};

export default function ContributionsIndex({
    communities,
    community,
    members,
    monthlyAmount,
    referenceMonth,
    search,
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
    const [searchTerm, setSearchTerm] = useState(search ?? '');
    const debounceRef = useRef<ReturnType<typeof setTimeout>>(undefined);

    const pagination = Array.isArray(members) ? null : members;
    const memberList = pagination?.data ?? [];

    function visit(nextMonth: string, nextSearch: string) {
        if (!community) {
            return;
        }

        const params: Record<string, string | number> = {
            community: community.id,
            month: nextMonth,
        };

        if (nextSearch) {
            params.search = nextSearch;
        }

        router.get('/contributions', params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    function viewMonth() {
        visit(month, searchTerm);
    }

    function handleSearchChange(event: ChangeEvent<HTMLInputElement>) {
        const value = event.target.value;
        setSearchTerm(value);

        if (debounceRef.current) {
            clearTimeout(debounceRef.current);
        }

        debounceRef.current = setTimeout(() => {
            visit(referenceMonth, value);
        }, 300);
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
                    <div className="flex-1 space-y-6 overflow-y-auto p-6">
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
                            <div className="grid gap-2">
                                <Label htmlFor="member-search">
                                    Search member
                                </Label>
                                <Input
                                    id="member-search"
                                    type="search"
                                    placeholder="Name or CPF"
                                    value={searchTerm}
                                    onChange={handleSearchChange}
                                    className="w-64"
                                />
                            </div>
                            <p className="pb-2 text-sm text-muted-foreground">
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
                                        CPF
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
                                {memberList.map((member) => (
                                    <tr
                                        key={member.id}
                                        className="hover:bg-muted/50"
                                    >
                                        <td className="px-4 py-2">
                                            {member.name}
                                        </td>
                                        <td className="px-4 py-2">
                                            {member.cpf
                                                ? formatCpf(member.cpf)
                                                : '-'}
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
                                                <Dialog>
                                                    <DialogTrigger asChild>
                                                        <Button size="sm">
                                                            Register payment
                                                        </Button>
                                                    </DialogTrigger>
                                                    <DialogContent>
                                                        <DialogTitle>
                                                            Register monthly
                                                            payment?
                                                        </DialogTitle>
                                                        <DialogDescription>
                                                            Register the{' '}
                                                            {referenceMonth}{' '}
                                                            payment for{' '}
                                                            {member.name}? This
                                                            will mark the member
                                                            as paid for that
                                                            month.
                                                        </DialogDescription>

                                                        <Form
                                                            action={`/contributions?community=${community.id}`}
                                                            method="post"
                                                            options={{
                                                                preserveScroll: true,
                                                            }}
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
                                                                        value={`${referenceMonth}-01`}
                                                                    />
                                                                    <InputError
                                                                        message={
                                                                            errors.reference_month
                                                                        }
                                                                    />

                                                                    <DialogFooter className="gap-2">
                                                                        <DialogClose
                                                                            asChild
                                                                        >
                                                                            <Button
                                                                                type="button"
                                                                                variant="secondary"
                                                                            >
                                                                                Cancel
                                                                            </Button>
                                                                        </DialogClose>
                                                                        <Button
                                                                            type="submit"
                                                                            disabled={
                                                                                processing
                                                                            }
                                                                        >
                                                                            Confirm
                                                                            payment
                                                                        </Button>
                                                                    </DialogFooter>
                                                                </>
                                                            )}
                                                        </Form>
                                                    </DialogContent>
                                                </Dialog>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                                {memberList.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={4}
                                            className="px-4 py-8 text-center text-muted-foreground"
                                        >
                                            {searchTerm
                                                ? 'No members match your search.'
                                                : 'No members in this community yet.'}
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>

                        {pagination && (
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <p className="text-sm text-muted-foreground">
                                    {pagination.total} member
                                    {pagination.total === 1 ? '' : 's'}
                                </p>
                                <div className="flex items-center gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        disabled={!pagination.prev_page_url}
                                        onClick={() => {
                                            if (pagination.prev_page_url) {
                                                router.visit(
                                                    pagination.prev_page_url,
                                                    { preserveScroll: true },
                                                );
                                            }
                                        }}
                                    >
                                        Previous
                                    </Button>
                                    <span className="text-sm text-muted-foreground">
                                        Page {pagination.current_page} of{' '}
                                        {pagination.last_page}
                                    </span>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        disabled={!pagination.next_page_url}
                                        onClick={() => {
                                            if (pagination.next_page_url) {
                                                router.visit(
                                                    pagination.next_page_url,
                                                    { preserveScroll: true },
                                                );
                                            }
                                        }}
                                    >
                                        Next
                                    </Button>
                                </div>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </>
    );
}
