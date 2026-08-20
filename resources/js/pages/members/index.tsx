import { Head, Link, router, setLayoutProps } from '@inertiajs/react';
import { formatCpf } from '@/components/cpf-input';
import { formatPhone } from '@/components/phone-input';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Profile } from '@/types/auth';

type Member = {
    id: number;
    name: string;
    email: string;
    position: string | null;
    profile: Profile | null;
    pivot: {
        role: string;
        joined_at: string;
    };
};

type CommunityOption = {
    id: number;
    name: string;
};

type PaginatedMembers = {
    data: Member[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};

type Props = {
    communities: CommunityOption[];
    community: { id: number; name: string; slug: string } | null;
    members: PaginatedMembers | [];
};

export default function MembersIndex({
    communities,
    community,
    members,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Members', href: '/members' },
            ...(community
                ? [
                      {
                          title: community.name,
                          href: `/members?community=${community.id}`,
                      },
                  ]
                : []),
        ],
    });

    function handleCommunityChange(value: string) {
        router.visit(`/members?community=${value}`);
    }

    const memberList = Array.isArray(members) ? [] : members.data;

    return (
        <>
            <Head
                title={community ? `Members - ${community.name}` : 'Members'}
            />
            <div className="flex h-full flex-1 flex-col overflow-hidden">
                <div className="flex items-center justify-between border-b px-6 py-3">
                    <div className="flex items-center gap-3">
                        <h1 className="text-lg font-semibold">Members</h1>
                        <Select
                            value={community ? String(community.id) : undefined}
                            onValueChange={handleCommunityChange}
                            disabled={communities.length <= 1}
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
                        <Button size="sm" asChild>
                            <Link
                                href={`/members/create?community=${community.id}`}
                            >
                                + New member
                            </Link>
                        </Button>
                    )}
                </div>

                <div className="flex-1 overflow-y-auto">
                    <table className="w-full text-sm">
                        <thead className="border-b bg-muted/50">
                            <tr>
                                <th className="px-4 py-2 text-left font-medium">
                                    Name
                                </th>
                                <th className="px-4 py-2 text-left font-medium">
                                    Email
                                </th>
                                <th className="px-4 py-2 text-left font-medium">
                                    Position
                                </th>
                                <th className="px-4 py-2 text-left font-medium">
                                    CPF
                                </th>
                                <th className="px-4 py-2 text-left font-medium">
                                    Phone
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
                                    <td className="px-4 py-2">{member.name}</td>
                                    <td className="px-4 py-2">
                                        {member.email}
                                    </td>
                                    <td className="px-4 py-2">
                                        {member.position ?? 'Member'}
                                    </td>
                                    <td className="px-4 py-2">
                                        {member.profile
                                            ? formatCpf(member.profile.cpf)
                                            : '-'}
                                    </td>
                                    <td className="px-4 py-2">
                                        {member.profile?.phone
                                            ? formatPhone(member.profile.phone)
                                            : '-'}
                                    </td>
                                    <td className="px-4 py-2 text-right">
                                        <Link
                                            href={`/members/${member.id}/edit?community=${community?.id}`}
                                            className="text-sm text-blue-600 hover:underline"
                                        >
                                            Edit
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                            {memberList.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        No members yet.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}
