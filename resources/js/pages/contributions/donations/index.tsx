import { Form, Head, Link, router, setLayoutProps } from '@inertiajs/react';
import type { ChangeEvent } from 'react';
import { useRef, useState } from 'react';
import { formatCnpj } from '@/components/cnpj-input';
import { ContributionHistoryDialog } from '@/components/contribution-history-dialog';
import type { ContributionHistoryEntry } from '@/components/contribution-history-dialog';
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
import { Textarea } from '@/components/ui/textarea';

type CommunityOption = {
    id: number;
    name: string;
};

type DonorType = 'member' | 'individual' | 'organization';

type Donation = {
    id: number;
    donor_type: DonorType | null;
    donor_name: string | null;
    donor_document: string | null;
    member_name: string | null;
    amount: string;
    reference_month: string;
    notes: string | null;
    status: 'active' | 'reversed';
    registered_at: string | null;
    registered_by_name: string | null;
    reversed_at: string | null;
    reversed_by_name: string | null;
    reversal_reason: string | null;
};

type PaginatedDonations = {
    data: Donation[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

type Props = {
    communities: CommunityOption[];
    community: { id: number; name: string; slug: string } | null;
    donations: PaginatedDonations | [];
    referenceMonth: string | null;
    search: string | null;
};

function donorLabel(donation: Donation): string {
    if (donation.donor_type === 'member') {
        return donation.member_name ?? '-';
    }

    return donation.donor_name ?? '-';
}

function donorDocument(donation: Donation): string {
    if (!donation.donor_document) {
        return '-';
    }

    return donation.donor_type === 'organization'
        ? formatCnpj(donation.donor_document)
        : formatCpf(donation.donor_document);
}

function historyEntry(donation: Donation): ContributionHistoryEntry {
    return {
        id: donation.id,
        amount: donation.amount,
        status: donation.status,
        registered_at: donation.registered_at,
        registered_by_name: donation.registered_by_name,
        reversed_at: donation.reversed_at,
        reversed_by_name: donation.reversed_by_name,
        reversal_reason: donation.reversal_reason,
    };
}

export default function DonationsIndex({
    communities,
    community,
    donations,
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
                      {
                          title: 'Donations',
                          href: `/contributions/donations?community=${community.id}`,
                      },
                  ]
                : []),
        ],
    });

    const [month, setMonth] = useState(referenceMonth ?? '');
    const [searchTerm, setSearchTerm] = useState(search ?? '');
    const debounceRef = useRef<ReturnType<typeof setTimeout>>(undefined);

    const pagination = Array.isArray(donations) ? null : donations;
    const donationList = pagination?.data ?? [];

    function visit(nextMonth: string, nextSearch: string) {
        if (!community) {
            return;
        }

        const params: Record<string, string | number> = {
            community: community.id,
        };

        if (nextMonth) {
            params.month = nextMonth;
        }

        if (nextSearch) {
            params.search = nextSearch;
        }

        router.get('/contributions/donations', params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    function viewMonth() {
        visit(month, searchTerm);
    }

    function viewAllMonths() {
        setMonth('');
        visit('', searchTerm);
    }

    function handleSearchChange(event: ChangeEvent<HTMLInputElement>) {
        const value = event.target.value;
        setSearchTerm(value);

        if (debounceRef.current) {
            clearTimeout(debounceRef.current);
        }

        debounceRef.current = setTimeout(() => {
            visit(referenceMonth ?? '', value);
        }, 300);
    }

    function handleCommunityChange(value: string) {
        router.visit(`/contributions/donations?community=${value}`);
    }

    return (
        <>
            <Head
                title={
                    community ? `Donations - ${community.name}` : 'Donations'
                }
            />
            <div className="flex h-full flex-1 flex-col overflow-hidden">
                <div className="flex items-center justify-between border-b px-6 py-3">
                    <div className="flex items-center gap-3">
                        <h1 className="text-lg font-semibold">Donations</h1>
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
                                    href={`/contributions?community=${community.id}`}
                                >
                                    Back to contributions
                                </Link>
                            </Button>
                        </div>
                    )}
                </div>

                {!community ? (
                    <div className="flex flex-1 items-center justify-center p-6">
                        <p className="text-sm text-muted-foreground">
                            Select a community to manage its donations.
                        </p>
                    </div>
                ) : (
                    <div className="flex-1 space-y-6 overflow-y-auto p-6">
                        <div className="flex flex-wrap items-end gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="donation-month">Month</Label>
                                <Input
                                    id="donation-month"
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
                            <Button
                                type="button"
                                variant="outline"
                                onClick={viewAllMonths}
                            >
                                All months
                            </Button>
                            <div className="grid gap-2">
                                <Label htmlFor="donation-search">
                                    Search donor
                                </Label>
                                <Input
                                    id="donation-search"
                                    type="search"
                                    placeholder="Member, donor or document"
                                    value={searchTerm}
                                    onChange={handleSearchChange}
                                    className="w-64"
                                />
                            </div>
                        </div>

                        <table className="w-full text-sm">
                            <thead className="border-b bg-muted/50">
                                <tr>
                                    <th className="px-4 py-2 text-left font-medium">
                                        Donor
                                    </th>
                                    <th className="px-4 py-2 text-left font-medium">
                                        Document
                                    </th>
                                    <th className="px-4 py-2 text-left font-medium">
                                        Amount
                                    </th>
                                    <th className="px-4 py-2 text-left font-medium">
                                        Month
                                    </th>
                                    <th className="px-4 py-2 text-left font-medium">
                                        Status
                                    </th>
                                    <th className="px-4 py-2 text-left font-medium">
                                        History
                                    </th>
                                    <th className="px-4 py-2 text-right font-medium">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {donationList.map((donation) => (
                                    <tr
                                        key={donation.id}
                                        className="hover:bg-muted/50"
                                    >
                                        <td className="px-4 py-2">
                                            {donorLabel(donation)}
                                        </td>
                                        <td className="px-4 py-2">
                                            {donorDocument(donation)}
                                        </td>
                                        <td className="px-4 py-2">
                                            R$ {donation.amount}
                                        </td>
                                        <td className="px-4 py-2">
                                            {donation.reference_month}
                                        </td>
                                        <td className="px-4 py-2">
                                            <Badge
                                                variant={
                                                    donation.status === 'active'
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {donation.status === 'active'
                                                    ? 'Active'
                                                    : 'Reversed'}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-2">
                                            <ContributionHistoryDialog
                                                history={[
                                                    historyEntry(donation),
                                                ]}
                                            />
                                        </td>
                                        <td className="px-4 py-2 text-right">
                                            {donation.status === 'active' && (
                                                <Dialog>
                                                    <DialogTrigger asChild>
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                        >
                                                            Revert
                                                        </Button>
                                                    </DialogTrigger>
                                                    <DialogContent>
                                                        <DialogTitle>
                                                            Revert donation?
                                                        </DialogTitle>
                                                        <DialogDescription>
                                                            Revert the donation
                                                            of{' '}
                                                            {donorLabel(
                                                                donation,
                                                            )}{' '}
                                                            (
                                                            {
                                                                donation.reference_month
                                                            }
                                                            )? The record stays
                                                            for audit.
                                                        </DialogDescription>

                                                        <Form
                                                            action={`/contributions/${donation.id}/reverse?community=${community.id}`}
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
                                                                    <div className="grid gap-2">
                                                                        <Label
                                                                            htmlFor={`reversal-reason-${donation.id}`}
                                                                        >
                                                                            Reason
                                                                            *
                                                                        </Label>
                                                                        <Textarea
                                                                            id={`reversal-reason-${donation.id}`}
                                                                            name="reversal_reason"
                                                                            rows={
                                                                                3
                                                                            }
                                                                            required
                                                                        />
                                                                        <InputError
                                                                            message={
                                                                                errors.reversal_reason
                                                                            }
                                                                        />
                                                                    </div>

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
                                                                            variant="destructive"
                                                                            disabled={
                                                                                processing
                                                                            }
                                                                        >
                                                                            Revert
                                                                            donation
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
                                {donationList.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={7}
                                            className="px-4 py-8 text-center text-muted-foreground"
                                        >
                                            {searchTerm
                                                ? 'No donations match your search.'
                                                : 'No donations registered yet.'}
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>

                        {pagination && (
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <p className="text-sm text-muted-foreground">
                                    {pagination.total} donation
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
