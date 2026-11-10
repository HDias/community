import { Head, router } from '@inertiajs/react';
import { switchCommunity } from '@/actions/App/Http/Controllers/Communities/CommunityController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

interface AdministrationMember {
    name: string;
    position: string;
}

interface CommunityOption {
    id: number;
    name: string;
}

interface ContributionHistoryEntry {
    id: number;
    type: 'monthly' | 'donation';
    amount: string;
    reference_month: string;
    status: 'active' | 'reversed';
    registered_at: string | null;
    registered_by_name: string | null;
    reversed_at: string | null;
    reversed_by_name: string | null;
    reversal_reason: string | null;
}

interface MyContributions {
    totalPaidThisYear: string;
    currentMonthStatus: 'paid' | 'pending';
    nextDueMonth: string | null;
    history: ContributionHistoryEntry[];
}

interface DashboardProps {
    community: {
        name: string;
        slug: string;
        description: string | null;
    } | null;
    communities: CommunityOption[];
    memberCount: number;
    memberSince: string | null;
    communityRole: string | null;
    administrationMembers: AdministrationMember[];
    canManage: boolean;
    myContributions: MyContributions | null;
}

function formatMonth(value: string): string {
    const [year, month] = value.split('-').map(Number);

    return new Date(year, month - 1, 1).toLocaleDateString('en-US', {
        month: 'long',
        year: 'numeric',
    });
}

export default function Dashboard({
    community,
    communities,
    memberCount,
    memberSince,
    administrationMembers,
    myContributions,
}: DashboardProps) {
    const currentCommunity = communities.find(
        (c) => c.name === community?.name,
    );

    function handleCommunitySwitch(communityId: string) {
        router.post(
            switchCommunity.url(Number(communityId)),
            {},
            {
                preserveScroll: true,
            },
        );
    }

    if (!community) {
        return (
            <>
                <Head title="Dashboard" />
                <div className="flex h-full flex-1 flex-col items-center justify-center gap-4 p-4">
                    <div className="text-center">
                        <h1 className="text-2xl font-semibold">Welcome</h1>
                        <p className="mt-2 text-muted-foreground">
                            You are not part of any community yet. Please
                            contact an administrator.
                        </p>
                    </div>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
                {/* Community switcher */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Welcome back</h1>
                        <p className="text-muted-foreground">
                            {community.name} community portal
                        </p>
                    </div>
                    <div>
                        <select
                            className="rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            value={currentCommunity?.id ?? ''}
                            onChange={(e) =>
                                handleCommunitySwitch(e.target.value)
                            }
                            disabled={communities.length <= 1}
                        >
                            {communities.map((c) => (
                                <option key={c.id} value={c.id}>
                                    {c.name}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>

                {/* Cards grid */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    {/* Next Meeting card */}
                    <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <h3 className="text-sm font-medium text-muted-foreground">
                            Next Meeting
                        </h3>
                        <p className="mt-2 text-sm text-muted-foreground/70">
                            No upcoming meetings
                        </p>
                    </div>

                    {/* Contributions card */}
                    <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <h3 className="text-sm font-medium text-muted-foreground">
                            My Contributions
                        </h3>
                        {myContributions &&
                        myContributions.history.length > 0 ? (
                            <div className="mt-2 space-y-2 text-sm">
                                <div className="flex items-center justify-between gap-2">
                                    <span className="text-muted-foreground">
                                        Total this year
                                    </span>
                                    <span className="font-semibold">
                                        R$ {myContributions.totalPaidThisYear}
                                    </span>
                                </div>
                                <div className="flex items-center justify-between gap-2">
                                    <span className="text-muted-foreground">
                                        This month
                                    </span>
                                    <Badge
                                        variant={
                                            myContributions.currentMonthStatus ===
                                            'paid'
                                                ? 'default'
                                                : 'secondary'
                                        }
                                    >
                                        {myContributions.currentMonthStatus ===
                                        'paid'
                                            ? 'Paid'
                                            : 'Pending'}
                                    </Badge>
                                </div>
                                {myContributions.nextDueMonth && (
                                    <p className="text-muted-foreground">
                                        Next due:{' '}
                                        {formatMonth(
                                            myContributions.nextDueMonth,
                                        )}
                                    </p>
                                )}
                                <Dialog>
                                    <DialogTrigger asChild>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="outline"
                                        >
                                            View history
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <DialogTitle>
                                            Contribution history
                                        </DialogTitle>
                                        <DialogDescription>
                                            Monthly payments and donations
                                            registered for you.
                                        </DialogDescription>
                                        <ol className="space-y-3">
                                            {myContributions.history.map(
                                                (entry) => (
                                                    <li
                                                        key={entry.id}
                                                        className="rounded-lg border p-3 text-sm"
                                                    >
                                                        <div className="flex items-center justify-between gap-2">
                                                            <span className="font-medium">
                                                                {entry.type ===
                                                                'donation'
                                                                    ? 'Donation'
                                                                    : 'Monthly'}{' '}
                                                                ·{' '}
                                                                {
                                                                    entry.reference_month
                                                                }
                                                            </span>
                                                            <Badge
                                                                variant={
                                                                    entry.status ===
                                                                    'active'
                                                                        ? 'default'
                                                                        : 'secondary'
                                                                }
                                                            >
                                                                {entry.status ===
                                                                'active'
                                                                    ? 'Active'
                                                                    : 'Reversed'}
                                                            </Badge>
                                                        </div>
                                                        <p className="text-muted-foreground">
                                                            Amount: R${' '}
                                                            {entry.amount}
                                                        </p>
                                                        {entry.registered_by_name && (
                                                            <p className="text-muted-foreground">
                                                                Registered by{' '}
                                                                {
                                                                    entry.registered_by_name
                                                                }
                                                            </p>
                                                        )}
                                                        {entry.status ===
                                                            'reversed' && (
                                                            <div className="mt-2 space-y-1 border-t pt-2">
                                                                {entry.reversed_by_name && (
                                                                    <p className="text-muted-foreground">
                                                                        Reversed
                                                                        by{' '}
                                                                        {
                                                                            entry.reversed_by_name
                                                                        }
                                                                    </p>
                                                                )}
                                                                {entry.reversal_reason && (
                                                                    <p>
                                                                        Reason:{' '}
                                                                        {
                                                                            entry.reversal_reason
                                                                        }
                                                                    </p>
                                                                )}
                                                            </div>
                                                        )}
                                                    </li>
                                                ),
                                            )}
                                        </ol>
                                    </DialogContent>
                                </Dialog>
                            </div>
                        ) : (
                            <p className="mt-2 text-sm text-muted-foreground/70">
                                No contributions yet
                            </p>
                        )}
                    </div>

                    {/* Current Administration card */}
                    <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <h3 className="text-sm font-medium text-muted-foreground">
                            Current Administration
                        </h3>
                        {administrationMembers.length > 0 ? (
                            <ul className="mt-2 space-y-1">
                                {administrationMembers.map((member, index) => (
                                    <li
                                        key={`${member.position}-${index}`}
                                        className="text-sm"
                                    >
                                        <span className="font-medium">
                                            {member.position}:
                                        </span>{' '}
                                        {member.name}
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="mt-2 text-sm text-muted-foreground/70">
                                No administration set
                            </p>
                        )}
                    </div>

                    {/* Bylaws card */}
                    <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <h3 className="text-sm font-medium text-muted-foreground">
                            Bylaws
                        </h3>
                        <p className="mt-2 text-sm text-muted-foreground/70">
                            No bylaws available
                        </p>
                    </div>
                </div>

                {/* Community info */}
                <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div className="flex items-center justify-between">
                        <div>
                            <h3 className="text-sm font-medium text-muted-foreground">
                                Community
                            </h3>
                            <p className="mt-1 text-lg font-semibold">
                                {community.name}
                            </p>
                            {community.description && (
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {community.description}
                                </p>
                            )}
                        </div>
                        <div className="text-right text-sm text-muted-foreground">
                            <p>{memberCount} members</p>
                            {memberSince && (
                                <p>
                                    Member since{' '}
                                    {new Date(memberSince).toLocaleDateString()}
                                </p>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = () => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: '/dashboard',
        },
    ],
});
