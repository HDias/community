import { Form, Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { LocationFields } from '@/components/location-fields';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type CommunityCard = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    address: string | null;
    city: string | null;
    state: string | null;
    members_count: number;
    role: string;
    is_current: boolean;
    is_owner: boolean;
};

type Props = {
    communities: CommunityCard[];
    canCreate: boolean;
};

type PanelState =
    | { mode: 'empty' }
    | { mode: 'create' }
    | { mode: 'edit'; community: CommunityCard };

function getInitials(name: string): string {
    return name
        .split(' ')
        .map((w) => w[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();
}

function CommunityForm({
    community,
    onCancel,
}: {
    community?: CommunityCard;
    onCancel: () => void;
}) {
    const isEditing = !!community;

    return (
        <div className="flex flex-1 flex-col">
            <div className="flex items-center justify-between border-b px-6 py-3">
                <h2 className="font-semibold">
                    {isEditing ? community.name : 'New community'}
                </h2>
                <span className="text-xs text-muted-foreground">
                    {isEditing ? community.slug : 'Unsaved'}
                </span>
            </div>
            <Form
                key={community?.id ?? 'new'}
                action={
                    isEditing ? `/communities/${community.id}` : '/communities'
                }
                method={isEditing ? 'put' : 'post'}
                resetOnSuccess={!isEditing}
                className="flex flex-1 flex-col"
            >
                {({ errors, processing }) => (
                    <>
                        <div className="flex-1 space-y-6 overflow-y-auto p-6">
                            <div>
                                <h3 className="mb-3 text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                    Identity
                                </h3>
                                <div className="space-y-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">
                                            Community name *
                                        </Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            required
                                            defaultValue={community?.name ?? ''}
                                            placeholder="Quilombo São Roque"
                                        />
                                        <InputError message={errors.name} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="description">
                                            Description
                                        </Label>
                                        <Input
                                            id="description"
                                            name="description"
                                            defaultValue={
                                                community?.description ?? ''
                                            }
                                            placeholder="Brief description"
                                        />
                                        <InputError
                                            message={errors.description}
                                        />
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h3 className="mb-3 text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                    Location
                                </h3>
                                <LocationFields
                                    defaultAddress={community?.address ?? ''}
                                    defaultState={community?.state ?? ''}
                                    defaultCity={community?.city ?? ''}
                                />
                            </div>

                            {!isEditing && (
                                <div className="rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-200">
                                    On creation you become a{' '}
                                    <strong>member</strong> and receive the{' '}
                                    <strong>President</strong> role in this
                                    community.
                                </div>
                            )}
                        </div>

                        <div className="flex items-center justify-between border-t px-6 py-3">
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={onCancel}
                            >
                                Cancel
                            </Button>
                            <div className="flex items-center gap-2">
                                {isEditing && !community.is_current && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() =>
                                            router.post(
                                                `/communities/${community.id}/switch`,
                                            )
                                        }
                                    >
                                        Switch to this
                                    </Button>
                                )}
                                {(!isEditing || community.is_owner) && (
                                    <Button type="submit" disabled={processing}>
                                        {processing && <Spinner />}
                                        {isEditing
                                            ? 'Save changes'
                                            : 'Create community'}
                                    </Button>
                                )}
                            </div>
                        </div>
                    </>
                )}
            </Form>
        </div>
    );
}

export default function CommunitiesIndex({ communities, canCreate }: Props) {
    const [panel, setPanel] = useState<PanelState>({ mode: 'empty' });

    const selectedId = panel.mode === 'edit' ? panel.community.id : null;

    return (
        <>
            <Head title="Communities" />
            <div className="flex h-full flex-1 flex-col overflow-hidden">
                {/* Top bar */}
                <div className="flex items-center justify-between border-b px-6 py-3">
                    <h1 className="text-lg font-semibold">Communities</h1>
                    {canCreate && (
                        <Button
                            size="sm"
                            onClick={() => setPanel({ mode: 'create' })}
                        >
                            + New community
                        </Button>
                    )}
                </div>

                {/* Split layout */}
                <div className="flex flex-1 overflow-hidden">
                    {/* Left: List */}
                    <div className="flex w-full flex-col overflow-y-auto border-r md:w-1/2 lg:w-2/5">
                        <div className="border-b px-4 py-2">
                            <span className="text-sm text-muted-foreground">
                                {communities.length}{' '}
                                {communities.length === 1
                                    ? 'community'
                                    : 'communities'}
                            </span>
                        </div>
                        <ul className="divide-y">
                            {communities.map((community) => (
                                <li
                                    key={community.id}
                                    className={`flex cursor-pointer items-center gap-3 px-4 py-3 hover:bg-muted/50 ${
                                        selectedId === community.id
                                            ? 'bg-muted'
                                            : ''
                                    }`}
                                    onClick={() =>
                                        setPanel({ mode: 'edit', community })
                                    }
                                >
                                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-muted text-xs font-semibold">
                                        {getInitials(community.name)}
                                    </div>
                                    <div className="flex-1 truncate">
                                        <div className="flex items-center gap-2">
                                            <span className="truncate font-medium">
                                                {community.name}
                                            </span>
                                            {community.is_current && (
                                                <span className="shrink-0 rounded bg-green-100 px-1.5 py-0.5 text-[10px] font-medium text-green-800 dark:bg-green-900 dark:text-green-200">
                                                    Current
                                                </span>
                                            )}
                                        </div>
                                        <div className="text-xs text-muted-foreground">
                                            {community.city && community.state
                                                ? `${community.city}, ${community.state}`
                                                : community.slug}
                                        </div>
                                    </div>
                                    <div className="flex flex-col items-end gap-1">
                                        {selectedId === community.id ? (
                                            <span className="rounded bg-blue-100 px-1.5 py-0.5 text-[10px] font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                editing
                                            </span>
                                        ) : (
                                            <span className="rounded bg-muted px-1.5 py-0.5 text-[10px] font-medium capitalize">
                                                {community.role}
                                            </span>
                                        )}
                                        <span className="text-[10px] text-muted-foreground">
                                            {community.members_count} members
                                        </span>
                                    </div>
                                </li>
                            ))}
                            {communities.length === 0 && (
                                <li className="px-4 py-8 text-center text-sm text-muted-foreground">
                                    No communities yet.
                                </li>
                            )}
                        </ul>
                        <span className="border-t px-4 py-2 text-xs text-muted-foreground">
                            Select a community to edit, or add a new one.
                        </span>
                    </div>

                    {/* Right: Create / Edit form */}
                    <div className="hidden flex-1 flex-col overflow-y-auto md:flex">
                        {panel.mode === 'create' && canCreate && (
                            <CommunityForm
                                onCancel={() => setPanel({ mode: 'empty' })}
                            />
                        )}

                        {panel.mode === 'edit' && (
                            <CommunityForm
                                community={panel.community}
                                onCancel={() => setPanel({ mode: 'empty' })}
                            />
                        )}

                        {panel.mode === 'edit' && panel.community.is_owner && (
                            <div className="border-t px-6 py-4">
                                <h3 className="mb-3 text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                    Management
                                </h3>
                                <div className="flex flex-col gap-2">
                                    <Link
                                        href={`/positions?community=${panel.community.id}`}
                                        className="text-sm font-medium text-primary hover:underline"
                                    >
                                        Positions →
                                    </Link>
                                    <Link
                                        href={`/administrations?community=${panel.community.id}`}
                                        className="text-sm font-medium text-primary hover:underline"
                                    >
                                        Administrations →
                                    </Link>
                                </div>
                            </div>
                        )}

                        {panel.mode === 'empty' && (
                            <div className="flex flex-1 items-center justify-center text-sm text-muted-foreground">
                                Select a community to edit, or add a new one.
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
