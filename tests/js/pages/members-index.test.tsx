import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { UserEvent } from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import MembersIndex from '@/pages/members/index';
import type { Profile } from '@/types/auth';
import { resetInertia, router, setLayoutProps } from '../support/inertia';

vi.mock('@inertiajs/react', async () => {
    const { createInertiaMock } = await import('../support/inertia');

    return createInertiaMock();
});

type Props = Parameters<typeof MembersIndex>[0];
type Member = Extract<Props['members'], { data: unknown[] }>['data'][number];

const quilombo = { id: 7, name: 'Quilombo Kalunga', slug: 'quilombo-kalunga' };
const otherQuilombo = { id: 9, name: 'Quilombo Mesquita' };

function profile(overrides: Partial<Profile> = {}): Profile {
    return {
        id: 1,
        user_id: 1,
        social_name: null,
        nickname: null,
        cpf: '12345678901',
        birth_date: '1990-01-01',
        phone: '11987654321',
        profession: null,
        address_street: null,
        address_number: null,
        address_neighborhood: null,
        address_city: null,
        address_state: null,
        address_zip: null,
        ...overrides,
    };
}

function member(overrides: Partial<Member> = {}): Member {
    return {
        id: 1,
        name: 'Ana Silva',
        email: 'ana@example.test',
        position: 'President',
        profile: profile(),
        pivot: { role: 'member', joined_at: '2024-01-01' },
        ...overrides,
    };
}

function paginate(data: Member[]) {
    return {
        data,
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: data.length,
    };
}

function renderIndex(overrides: Partial<Props> = {}) {
    const props: Props = {
        communities: [quilombo, otherQuilombo],
        community: quilombo,
        members: paginate([member()]),
        ...overrides,
    };

    return render(<MembersIndex {...props} />);
}

/** The cells of the single member row, in column order. */
function rowCells(): string[] {
    const row = screen.getAllByRole('row')[1];

    return Array.from(row.querySelectorAll('td')).map(
        (cell) => cell.textContent ?? '',
    );
}

let user: UserEvent;

beforeEach(() => {
    user = userEvent.setup();
    resetInertia();
});

afterEach(() => {
    resetInertia();
});

describe('member rows', () => {
    it('renders a row per member', () => {
        renderIndex({
            members: paginate([
                member(),
                member({ id: 2, name: 'Bruno Costa' }),
            ]),
        });

        expect(screen.getByText('Ana Silva')).toBeInTheDocument();
        expect(screen.getByText('Bruno Costa')).toBeInTheDocument();
        expect(screen.getAllByRole('row')).toHaveLength(3);
    });

    it('formats the CPF and phone the server sends unformatted', () => {
        renderIndex();

        expect(rowCells()).toEqual([
            'Ana Silva',
            'ana@example.test',
            'President',
            '123.456.789-01',
            '(11) 98765-4321',
            'Edit',
        ]);
    });

    it('falls back to Member when the member holds no position', () => {
        renderIndex({ members: paginate([member({ position: null })]) });

        expect(rowCells()[2]).toBe('Member');
    });

    it('dashes out the CPF and phone of a member with no profile', () => {
        renderIndex({ members: paginate([member({ profile: null })]) });

        const cells = rowCells();

        expect(cells[3]).toBe('-');
        expect(cells[4]).toBe('-');
    });

    it('dashes out only the phone when the profile has none', () => {
        renderIndex({
            members: paginate([member({ profile: profile({ phone: null }) })]),
        });

        const cells = rowCells();

        expect(cells[3]).toBe('123.456.789-01');
        expect(cells[4]).toBe('-');
    });

    it('links each row to its edit page within the current community', () => {
        renderIndex({ members: paginate([member({ id: 42 })]) });

        expect(screen.getByRole('link', { name: 'Edit' })).toHaveAttribute(
            'href',
            '/members/42/edit?community=7',
        );
    });
});

describe('empty state', () => {
    /**
     * The `members` prop is `PaginatedMembers | []`, so the page has to cope
     * with a bare array as well as an empty page of results.
     */
    it('reports no members when the server sends a bare array', () => {
        renderIndex({ members: [] });

        expect(screen.getByText('No members yet.')).toBeInTheDocument();
    });

    it('reports no members when the paginator is empty', () => {
        renderIndex({ members: paginate([]) });

        expect(screen.getByText('No members yet.')).toBeInTheDocument();
    });

    it('does not report an empty list when there are members', () => {
        renderIndex();

        expect(screen.queryByText('No members yet.')).not.toBeInTheDocument();
    });
});

describe('community switcher', () => {
    it('is disabled when the member belongs to a single community', () => {
        renderIndex({ communities: [quilombo] });

        expect(screen.getByRole('combobox')).toBeDisabled();
    });

    it('is disabled when there are no communities to switch to', () => {
        renderIndex({ communities: [], community: null });

        expect(screen.getByRole('combobox')).toBeDisabled();
    });

    it('is enabled once there is somewhere to switch to', () => {
        renderIndex();

        expect(screen.getByRole('combobox')).toBeEnabled();
    });

    it('shows the current community', () => {
        renderIndex();

        expect(screen.getByRole('combobox')).toHaveTextContent(
            'Quilombo Kalunga',
        );
    });

    it('navigates to the chosen community', async () => {
        renderIndex();

        await user.click(screen.getByRole('combobox'));
        await user.click(
            screen.getByRole('option', { name: 'Quilombo Mesquita' }),
        );

        expect(router.visit).toHaveBeenCalledExactlyOnceWith(
            '/members?community=9',
        );
    });
});

describe('creating a member', () => {
    it('offers the link within a community', () => {
        renderIndex();

        expect(
            screen.getByRole('link', { name: '+ New member' }),
        ).toHaveAttribute('href', '/members/create?community=7');
    });

    it('hides the link when no community is selected', () => {
        renderIndex({ community: null });

        expect(
            screen.queryByRole('link', { name: '+ New member' }),
        ).not.toBeInTheDocument();
    });
});

describe('layout', () => {
    it('breadcrumbs the current community', () => {
        renderIndex();

        expect(setLayoutProps).toHaveBeenLastCalledWith({
            breadcrumbs: [
                { title: 'Members', href: '/members' },
                { title: 'Quilombo Kalunga', href: '/members?community=7' },
            ],
        });
    });

    it('breadcrumbs only the index when no community is selected', () => {
        renderIndex({ community: null });

        expect(setLayoutProps).toHaveBeenLastCalledWith({
            breadcrumbs: [{ title: 'Members', href: '/members' }],
        });
    });

    it('titles the page with the current community', () => {
        renderIndex();

        expect(document.title).toBe('Members - Quilombo Kalunga');
    });

    it('titles the page plainly when no community is selected', () => {
        renderIndex({ community: null });

        expect(document.title).toBe('Members');
    });
});
