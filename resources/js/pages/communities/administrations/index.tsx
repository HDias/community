import { Head, router, setLayoutProps, useForm } from '@inertiajs/react';
import { useState } from 'react';
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

type Administration = {
    id: number;
    started_at: string;
    ended_at: string | null;
    is_current: boolean;
    members: Member[];
};

type CommunityOption = {
    id: number;
    name: string;
};

type Props = {
    communities: CommunityOption[];
    community: { id: number; name: string; slug: string } | null;
    administrations: Administration[];
};

type PanelState =
    | { mode: 'empty' }
    | { mode: 'create' }
    | { mode: 'detail'; administration: Administration };

export default function AdministrationsIndex({
    communities,
    community,
    administrations,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Administrations', href: '/administrations' },
            ...(community
                ? [
                      {
                          title: community.name,
                          href: `/administrations?community=${community.id}`,
                      },
                  ]
                : []),
        ],
    });

    const [panel, setPanel] = useState<PanelState>({ mode: 'empty' });
    const selectedId = panel.mode === 'detail' ? panel.administration.id : null;

    function handleCommunityChange(value: string) {
        router.visit(`/administrations?community=${value}`);
    }

    return (
        <>
            <Head
                title={
                    community
                        ? `Administrations - ${community.name}`
                        : 'Administrations'
                }
            />
            <div className="flex h-full flex-1 flex-col overflow-hidden">
                {/* Top bar */}
                <div className="flex items-center justify-between border-b px-6 py-3">
                    <div className="flex items-center gap-3">
                        <h1 className="text-lg font-semibold">
                            Administrations
                        </h1>
                        <Select
                            value={community ? String(community.id) : undefined}
                            onValueChange={handleCommunityChange}
                        >
                            <SelectTrigger size="sm" className="w-48">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {communities.map((c) => (
                                    <SelectItem key={c.id} value={String(c.id)}>
                                        {c.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    {community && (
                        <Button
                            size="sm"
                            onClick={() => setPanel({ mode: 'create' })}
                        >
                            + New administration
                        </Button>
                    )}
                </div>

                {/* Split layout */}
                <div className="flex flex-1 overflow-hidden">
                    {/* Left: List */}
                    <div className="flex w-full flex-col overflow-y-auto border-r md:w-1/2 lg:w-2/5">
                        <div className="border-b px-4 py-2">
                            <span className="text-sm text-muted-foreground">
                                {administrations.length}{' '}
                                {administrations.length === 1
                                    ? 'administration'
                                    : 'administrations'}
                            </span>
                        </div>
                        <ul className="divide-y">
                            {administrations.map((admin) => (
                                <li
                                    key={admin.id}
                                    className={`flex cursor-pointer items-center gap-3 px-4 py-3 hover:bg-muted/50 ${
                                        selectedId === admin.id
                                            ? 'bg-muted'
                                            : ''
                                    }`}
                                    onClick={() =>
                                        setPanel({
                                            mode: 'detail',
                                            administration: admin,
                                        })
                                    }
                                >
                                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-muted text-xs font-semibold">
                                        {admin.started_at.slice(0, 4)}
                                    </div>
                                    <div className="flex-1 truncate">
                                        <div className="flex items-center gap-2">
                                            <span className="truncate font-medium">
                                                {admin.started_at} —{' '}
                                                {admin.ended_at ?? 'Present'}
                                            </span>
                                            {admin.is_current && (
                                                <span className="shrink-0 rounded bg-green-100 px-1.5 py-0.5 text-[10px] font-medium text-green-800 dark:bg-green-900 dark:text-green-200">
                                                    Current
                                                </span>
                                            )}
                                        </div>
                                        <div className="text-xs text-muted-foreground">
                                            {admin.members.length} members
                                        </div>
                                    </div>
                                </li>
                            ))}
                            {administrations.length === 0 && (
                                <li className="px-4 py-8 text-center text-sm text-muted-foreground">
                                    No administrations yet.
                                </li>
                            )}
                        </ul>
                        <span className="border-t px-4 py-2 text-xs text-muted-foreground">
                            Select an administration to view details.
                        </span>
                    </div>

                    {/* Right: Create / Detail panel */}
                    <div className="hidden flex-1 flex-col overflow-y-auto md:flex">
                        {panel.mode === 'create' && community && (
                            <CreateAdministrationPanel
                                community={community}
                                onCancel={() => setPanel({ mode: 'empty' })}
                            />
                        )}

                        {panel.mode === 'detail' && community && (
                            <AdministrationDetail
                                community={community}
                                administration={panel.administration}
                                onDeleted={() => setPanel({ mode: 'empty' })}
                            />
                        )}

                        {panel.mode === 'empty' && (
                            <div className="flex flex-1 items-center justify-center text-sm text-muted-foreground">
                                Select an administration to view details.
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

function CreateAdministrationPanel({
    community,
    onCancel,
}: {
    community: { id: number };
    onCancel: () => void;
}) {
    const form = useForm({
        started_at: new Date().toISOString().split('T')[0],
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.post(`/administrations?community=${community.id}`);
    }

    return (
        <div className="flex flex-1 flex-col">
            <div className="flex items-center justify-between border-b px-6 py-3">
                <h2 className="font-semibold">New administration</h2>
            </div>
            <form onSubmit={handleSubmit} className="flex flex-1 flex-col">
                <div className="flex-1 space-y-6 overflow-y-auto p-6">
                    <div className="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200">
                        Creating a new administration will end the current one.
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="started_at">Start date *</Label>
                        <Input
                            id="started_at"
                            type="date"
                            value={form.data.started_at}
                            onChange={(e) =>
                                form.setData('started_at', e.target.value)
                            }
                            required
                        />
                    </div>
                </div>
                <div className="flex items-center justify-between border-t px-6 py-3">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={onCancel}
                    >
                        Cancel
                    </Button>
                    <Button type="submit" size="sm" disabled={form.processing}>
                        Create
                    </Button>
                </div>
            </form>
        </div>
    );
}

function AdministrationDetail({
    administration,
    onDeleted,
}: {
    community: { id: number };
    administration: Administration;
    onDeleted: () => void;
}) {
    const datesForm = useForm({
        started_at: administration.started_at,
        ended_at: administration.ended_at ?? '',
    });

    function handleDatesUpdate(e: React.FormEvent) {
        e.preventDefault();
        datesForm.put(`/administrations/${administration.id}`);
    }

    function handleDelete() {
        if (
            confirm(
                'Delete this administration? All member assignments will be lost.',
            )
        ) {
            router.delete(`/administrations/${administration.id}`, {
                onSuccess: onDeleted,
            });
        }
    }

    return (
        <div className="flex flex-1 flex-col">
            <div className="flex items-center justify-between border-b px-6 py-3">
                <h2 className="font-semibold">
                    {administration.started_at} —{' '}
                    {administration.ended_at ?? 'Present'}
                </h2>
                {administration.is_current && (
                    <span className="rounded bg-green-100 px-1.5 py-0.5 text-[10px] font-medium text-green-800 dark:bg-green-900 dark:text-green-200">
                        Current
                    </span>
                )}
            </div>
            <div className="flex-1 overflow-y-auto">
                {/* Dates section */}
                <div className="border-b px-6 py-4">
                    <h3 className="mb-3 text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                        Period
                    </h3>
                    <form onSubmit={handleDatesUpdate} className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="detail_started_at">
                                    Start date
                                </Label>
                                <Input
                                    id="detail_started_at"
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
                                <Label htmlFor="detail_ended_at">
                                    End date
                                </Label>
                                <Input
                                    id="detail_ended_at"
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
                    </form>
                </div>

                {/* Members section */}
                <div className="px-6 py-4">
                    <h3 className="mb-3 text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                        Members ({administration.members.length})
                    </h3>
                    {administration.members.length > 0 ? (
                        <ul className="divide-y rounded-md border">
                            {administration.members.map((member) => (
                                <li
                                    key={member.id}
                                    className="flex items-center justify-between px-4 py-3"
                                >
                                    <span className="text-sm font-medium">
                                        {member.user.name}
                                    </span>
                                    <span className="rounded bg-muted px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground">
                                        {member.position.name}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            No members assigned yet.
                        </p>
                    )}

                    <div className="mt-4">
                        <Button
                            size="sm"
                            variant="outline"
                            onClick={() =>
                                router.visit(
                                    `/administrations/${administration.id}`,
                                )
                            }
                        >
                            Manage members →
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    );
}
