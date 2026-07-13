import { Form, Head, router, setLayoutProps } from '@inertiajs/react';
import { useState } from 'react';
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

type Position = {
    id: number;
    name: string;
    is_default: boolean;
    in_use: boolean;
};

type CommunityOption = {
    id: number;
    name: string;
};

type Props = {
    communities: CommunityOption[];
    community: { id: number; name: string; slug: string } | null;
    positions: Position[];
};

type PanelState =
    | { mode: 'empty' }
    | { mode: 'create' }
    | { mode: 'edit'; position: Position };

function PositionForm({
    community,
    position,
    onCancel,
}: {
    community: { id: number };
    position?: Position;
    onCancel: () => void;
}) {
    const isEditing = !!position;

    return (
        <div className="flex flex-1 flex-col">
            <div className="flex items-center justify-between border-b px-6 py-3">
                <h2 className="font-semibold">
                    {isEditing ? position.name : 'New position'}
                </h2>
                {isEditing && (
                    <span className="text-xs text-muted-foreground">
                        {position.is_default ? 'Default' : 'Custom'}
                    </span>
                )}
            </div>
            <Form
                key={position?.id ?? 'new'}
                action={
                    isEditing
                        ? `/positions/${position.id}`
                        : `/positions?community=${community.id}`
                }
                method={isEditing ? 'put' : 'post'}
                resetOnSuccess={!isEditing}
                className="flex flex-1 flex-col"
            >
                {({ errors, processing }) => (
                    <>
                        <div className="flex-1 space-y-6 overflow-y-auto p-6">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Position name *</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    defaultValue={position?.name ?? ''}
                                    placeholder="e.g. Coordinator"
                                />
                                <InputError message={errors.name} />
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
                            <Button
                                type="submit"
                                size="sm"
                                disabled={processing}
                            >
                                {isEditing ? 'Update' : 'Create'}
                            </Button>
                        </div>
                    </>
                )}
            </Form>
        </div>
    );
}

export default function PositionsIndex({
    communities,
    community,
    positions,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Positions', href: '/positions' },
            ...(community
                ? [
                      {
                          title: community.name,
                          href: `/positions?community=${community.id}`,
                      },
                  ]
                : []),
        ],
    });

    const [panel, setPanel] = useState<PanelState>({ mode: 'empty' });
    const selectedId = panel.mode === 'edit' ? panel.position.id : null;

    function handleCommunityChange(value: string) {
        router.visit(`/positions?community=${value}`);
    }

    function handleDelete(position: Position) {
        if (confirm(`Delete "${position.name}"?`)) {
            router.delete(`/positions/${position.id}`, {
                onSuccess: () => setPanel({ mode: 'empty' }),
            });
        }
    }

    return (
        <>
            <Head
                title={
                    community ? `Positions - ${community.name}` : 'Positions'
                }
            />
            <div className="flex h-full flex-1 flex-col overflow-hidden">
                {/* Top bar */}
                <div className="flex items-center justify-between border-b px-6 py-3">
                    <div className="flex items-center gap-3">
                        <h1 className="text-lg font-semibold">Positions</h1>
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
                            + New position
                        </Button>
                    )}
                </div>

                {/* Split layout */}
                <div className="flex flex-1 overflow-hidden">
                    {/* Left: List */}
                    <div className="flex w-full flex-col overflow-y-auto border-r md:w-1/2 lg:w-2/5">
                        <div className="border-b px-4 py-2">
                            <span className="text-sm text-muted-foreground">
                                {positions.length}{' '}
                                {positions.length === 1
                                    ? 'position'
                                    : 'positions'}
                            </span>
                        </div>
                        <ul className="divide-y">
                            {positions.map((position) => (
                                <li
                                    key={position.id}
                                    className={`flex cursor-pointer items-center gap-3 px-4 py-3 hover:bg-muted/50 ${
                                        selectedId === position.id
                                            ? 'bg-muted'
                                            : ''
                                    }`}
                                    onClick={() =>
                                        setPanel({ mode: 'edit', position })
                                    }
                                >
                                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-muted text-xs font-semibold">
                                        {position.name.charAt(0).toUpperCase()}
                                    </div>
                                    <div className="flex-1 truncate">
                                        <span className="truncate font-medium">
                                            {position.name}
                                        </span>
                                    </div>
                                    <div className="flex flex-col items-end gap-1">
                                        {position.is_default && (
                                            <span className="rounded bg-muted px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground">
                                                default
                                            </span>
                                        )}
                                        {position.in_use && (
                                            <span className="shrink-0 rounded bg-green-100 px-1.5 py-0.5 text-[10px] font-medium text-green-800 dark:bg-green-900 dark:text-green-200">
                                                in use
                                            </span>
                                        )}
                                    </div>
                                </li>
                            ))}
                            {positions.length === 0 && (
                                <li className="px-4 py-8 text-center text-sm text-muted-foreground">
                                    No positions yet.
                                </li>
                            )}
                        </ul>
                        <span className="border-t px-4 py-2 text-xs text-muted-foreground">
                            Select a position to edit, or add a new one.
                        </span>
                    </div>

                    {/* Right: Create / Edit form */}
                    <div className="hidden flex-1 flex-col overflow-y-auto md:flex">
                        {panel.mode === 'create' && community && (
                            <PositionForm
                                community={community}
                                onCancel={() => setPanel({ mode: 'empty' })}
                            />
                        )}

                        {panel.mode === 'edit' && community && (
                            <>
                                <PositionForm
                                    community={community}
                                    position={panel.position}
                                    onCancel={() => setPanel({ mode: 'empty' })}
                                />
                                {!panel.position.in_use && (
                                    <div className="border-t px-6 py-4">
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            onClick={() =>
                                                handleDelete(panel.position)
                                            }
                                        >
                                            Delete position
                                        </Button>
                                    </div>
                                )}
                            </>
                        )}

                        {panel.mode === 'empty' && (
                            <div className="flex flex-1 items-center justify-center text-sm text-muted-foreground">
                                Select a position to edit, or add a new one.
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
