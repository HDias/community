import { Head, router, setLayoutProps, useForm } from '@inertiajs/react';
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

type Member = {
    id: number;
    user: { id: number; name: string };
    position: { id: number; name: string };
};

type CommunityOption = {
    id: number;
    name: string;
};

type Props = {
    communities: CommunityOption[];
    community: { id: number; name: string; slug: string };
    administration: {
        id: number;
        started_at: string;
        ended_at: string | null;
        is_current: boolean;
        members: Member[];
    };
    positions: { id: number; name: string }[];
    availableMembers: { id: number; name: string }[];
};

export default function AdministrationShow({
    communities,
    community,
    administration,
    positions,
    availableMembers,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Communities', href: '/communities' },
            {
                title: community.name,
                href: `/positions?community=${community.id}`,
            },
            {
                title: 'Administrations',
                href: `/administrations?community=${community.id}`,
            },
            {
                title: administration.started_at,
                href: `/administrations/${administration.id}`,
            },
        ],
    });

    const assignForm = useForm({ user_id: '', position_id: '' });
    const datesForm = useForm({
        started_at: administration.started_at,
        ended_at: administration.ended_at ?? '',
    });

    function handleAssign(e: React.FormEvent) {
        e.preventDefault();
        assignForm.post(`/administrations/${administration.id}/members`, {
            onSuccess: () => assignForm.reset(),
        });
    }

    function handleDatesUpdate(e: React.FormEvent) {
        e.preventDefault();
        datesForm.put(`/administrations/${administration.id}`);
    }

    function handleEndNow() {
        if (confirm('End this administration now?')) {
            const today = new Date().toISOString().split('T')[0];
            datesForm.setData('ended_at', today);
            router.put(`/administrations/${administration.id}`, {
                started_at: administration.started_at,
                ended_at: today,
            });
        }
    }

    function handleDelete() {
        if (
            confirm(
                'Delete this administration? All member assignments will be lost.',
            )
        ) {
            router.delete(`/administrations/${administration.id}`);
        }
    }

    function handleRemove(userId: number) {
        if (confirm('Remove this member from the administration?')) {
            router.delete(
                `/administrations/${administration.id}/members/${userId}`,
            );
        }
    }

    return (
        <>
            <Head title={`Administration - ${community.name}`} />
            <div className="flex h-full flex-1 flex-col overflow-hidden">
                {/* Top bar */}
                <div className="flex items-center justify-between border-b px-6 py-3">
                    <div className="flex items-center gap-3">
                        <h1 className="text-lg font-semibold">
                            Administration: {administration.started_at} —{' '}
                            {administration.ended_at ?? 'Present'}
                        </h1>
                        {administration.is_current && (
                            <span className="shrink-0 rounded bg-green-100 px-1.5 py-0.5 text-[10px] font-medium text-green-800 dark:bg-green-900 dark:text-green-200">
                                Current
                            </span>
                        )}
                    </div>
                    <Button
                        size="sm"
                        variant="outline"
                        onClick={() =>
                            router.visit(
                                `/administrations?community=${community.id}`,
                            )
                        }
                    >
                        ← Back
                    </Button>
                </div>

                {/* Split layout */}
                <div className="flex flex-1 overflow-hidden">
                    {/* Left: Members list */}
                    <div className="flex w-full flex-col overflow-y-auto border-r md:w-1/2 lg:w-2/5">
                        <div className="border-b px-4 py-2">
                            <span className="text-sm text-muted-foreground">
                                {administration.members.length}{' '}
                                {administration.members.length === 1
                                    ? 'member'
                                    : 'members'}
                            </span>
                        </div>
                        <ul className="divide-y">
                            {administration.members.map((member) => (
                                <li
                                    key={member.id}
                                    className="flex items-center gap-3 px-4 py-3 hover:bg-muted/50"
                                >
                                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-muted text-xs font-semibold">
                                        {member.user.name
                                            .charAt(0)
                                            .toUpperCase()}
                                    </div>
                                    <div className="flex-1 truncate">
                                        <span className="truncate font-medium">
                                            {member.user.name}
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Button
                                            type="submit"
                                            size="sm"
                                            disabled={datesForm.processing}
                                        >
                                            Save dates
                                        </Button>
                                        {administration.is_current && (
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="destructive"
                                                onClick={handleEndNow}
                                            >
                                                End now
                                            </Button>
                                        )}
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="ghost"
                                            className="ml-auto text-red-600 hover:text-red-700"
                                            onClick={handleDelete}
                                        >
                                            Delete
                                        </Button>
                                    </div>
                                </li>
                            ))}
                            {administration.members.length === 0 && (
                                <li className="px-4 py-8 text-center text-sm text-muted-foreground">
                                    No members assigned yet.
                                </li>
                            )}
                        </ul>
                    </div>

                    {/* Right: Dates + Assign */}
                    <div className="hidden flex-1 flex-col overflow-y-auto md:flex">
                        {/* Dates section */}
                        <div className="border-b px-6 py-4">
                            <h3 className="mb-3 text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                Period
                            </h3>
                            <form
                                onSubmit={handleDatesUpdate}
                                className="space-y-4"
                            >
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="started_at">
                                            Start date
                                        </Label>
                                        <Input
                                            id="started_at"
                                            type="date"
                                            value={datesForm.data.started_at}
                                            onChange={(e) =>
                                                datesForm.setData(
                                                    'started_at',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="ended_at">
                                            End date
                                        </Label>
                                        <Input
                                            id="ended_at"
                                            type="date"
                                            value={datesForm.data.ended_at}
                                            onChange={(e) =>
                                                datesForm.setData(
                                                    'ended_at',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Button
                                        type="submit"
                                        size="sm"
                                        disabled={datesForm.processing}
                                    >
                                        Save dates
                                    </Button>
                                    {administration.is_current && (
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="destructive"
                                            onClick={handleEndNow}
                                        >
                                            End now
                                        </Button>
                                    )}
                                </div>
                            </form>
                        </div>

                        {/* Assign section */}
                        {administration.is_current &&
                        availableMembers.length > 0 ? (
                            <div className="flex-1 px-6 py-4">
                                <h3 className="mb-3 text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                    Assign member
                                </h3>
                                <form
                                    onSubmit={handleAssign}
                                    className="space-y-4"
                                >
                                    <div className="grid gap-2">
                                        <Label>Member</Label>
                                        <Select
                                            value={assignForm.data.user_id}
                                            onValueChange={(v) =>
                                                assignForm.setData('user_id', v)
                                            }
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Select member..." />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {availableMembers.map((m) => (
                                                    <SelectItem
                                                        key={m.id}
                                                        value={String(m.id)}
                                                    >
                                                        {m.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Position</Label>
                                        <Select
                                            value={assignForm.data.position_id}
                                            onValueChange={(v) =>
                                                assignForm.setData(
                                                    'position_id',
                                                    v,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Select position..." />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {positions.map((p) => (
                                                    <SelectItem
                                                        key={p.id}
                                                        value={String(p.id)}
                                                    >
                                                        {p.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <Button
                                        type="submit"
                                        size="sm"
                                        disabled={
                                            assignForm.processing ||
                                            !assignForm.data.user_id ||
                                            !assignForm.data.position_id
                                        }
                                    >
                                        Assign
                                    </Button>
                                </form>
                            </div>
                        ) : (
                            <div className="flex flex-1 items-center justify-center text-sm text-muted-foreground">
                                {administration.is_current
                                    ? 'All community members have been assigned.'
                                    : 'This administration has ended.'}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
