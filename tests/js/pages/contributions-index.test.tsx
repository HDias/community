import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { UserEvent } from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import ContributionsIndex from '@/pages/contributions/index';
import {
    resetInertia,
    router,
    setLayoutProps,
    submissions,
} from '../support/inertia';

vi.mock('@inertiajs/react', async () => {
    const { createInertiaMock } = await import('../support/inertia');

    return createInertiaMock();
});

const community = { id: 7, name: 'Quilombo São Roque', slug: 'sao-roque' };

type Props = Parameters<typeof ContributionsIndex>[0];
type PaginatedMembers = Extract<Props['members'], { data: unknown }>;
type Member = PaginatedMembers['data'][number];

function member(overrides: Partial<Member> = {}): Member {
    return {
        id: 1,
        name: 'Ana Silva',
        cpf: null,
        paid: false,
        ...overrides,
    };
}

function paginated(
    data: Member[],
    overrides: Partial<PaginatedMembers> = {},
): PaginatedMembers {
    return {
        data,
        current_page: 1,
        last_page: 1,
        total: data.length,
        prev_page_url: null,
        next_page_url: null,
        ...overrides,
    };
}

function renderPage(overrides: Partial<Props> = {}) {
    const props: Props = {
        communities: [{ id: 7, name: 'Quilombo São Roque' }],
        community,
        members: paginated([]),
        monthlyAmount: '50.00',
        referenceMonth: '2026-08',
        search: null,
        ...overrides,
    };

    return render(<ContributionsIndex {...props} />);
}

let user: UserEvent;

async function waitForDebounce() {
    await new Promise((resolve) => setTimeout(resolve, 350));
}

beforeEach(() => {
    user = userEvent.setup();
    resetInertia();
});

afterEach(() => {
    resetInertia();
});

describe('page chrome', () => {
    it('scopes the breadcrumbs to the community', () => {
        renderPage();

        expect(setLayoutProps).toHaveBeenCalledWith({
            breadcrumbs: [
                { title: 'Contributions', href: '/contributions' },
                {
                    title: 'Quilombo São Roque',
                    href: '/contributions?community=7',
                },
            ],
        });
    });

    it('shows a hint when no community is selected', () => {
        renderPage({
            communities: [
                { id: 7, name: 'Quilombo São Roque' },
                { id: 8, name: 'Outra Comunidade' },
            ],
            community: null,
            monthlyAmount: null,
        });

        expect(
            screen.getByText('Select a community to manage its contributions.'),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Register payment' }),
        ).not.toBeInTheDocument();
    });
});

describe('the members status table', () => {
    it('marks each member as paid or pending', () => {
        renderPage({
            members: paginated([
                member({ id: 1, name: 'Ana Silva', paid: true }),
                member({ id: 2, name: 'Bruno Costa', paid: false }),
            ]),
        });

        const rows = screen.getAllByRole('row');

        expect(within(rows[1]).getByText('Paid')).toBeInTheDocument();
        expect(within(rows[2]).getByText('Pending')).toBeInTheDocument();
    });

    it('formats the member cpf', () => {
        renderPage({
            members: paginated([member({ cpf: '52998224725' })]),
        });

        expect(screen.getByText('529.982.247-25')).toBeInTheDocument();
    });

    it('only offers a payment action for pending members', () => {
        renderPage({
            members: paginated([
                member({ id: 1, name: 'Ana Silva', paid: true }),
                member({ id: 2, name: 'Bruno Costa', paid: false }),
            ]),
        });

        expect(
            screen.getAllByRole('button', { name: 'Register payment' }),
        ).toHaveLength(1);
    });

    it('asks for confirmation before registering a payment', async () => {
        renderPage({
            members: paginated([member({ id: 2, name: 'Bruno Costa' })]),
        });

        await user.click(
            screen.getByRole('button', { name: 'Register payment' }),
        );

        expect(
            screen.getByText('Register monthly payment?'),
        ).toBeInTheDocument();
        expect(submissions).toHaveLength(0);

        await user.click(
            screen.getByRole('button', { name: 'Confirm payment' }),
        );

        expect(submissions).toHaveLength(1);
        expect(submissions[0]).toMatchObject({
            action: '/contributions?community=7',
            method: 'post',
            data: {
                user_id: '2',
                reference_month: '2026-08-01',
            },
        });
    });

    it('does not submit when the confirmation is cancelled', async () => {
        renderPage({
            members: paginated([member({ id: 2, name: 'Bruno Costa' })]),
        });

        await user.click(
            screen.getByRole('button', { name: 'Register payment' }),
        );
        await user.click(screen.getByRole('button', { name: 'Cancel' }));

        expect(submissions).toHaveLength(0);
    });

    it('shows an empty state when the community has no members', () => {
        renderPage({ members: paginated([]) });

        expect(
            screen.getByText('No members in this community yet.'),
        ).toBeInTheDocument();
    });

    it('shows a search-specific empty state', () => {
        renderPage({ members: paginated([]), search: 'nobody' });

        expect(
            screen.getByText('No members match your search.'),
        ).toBeInTheDocument();
    });
});

describe('the member search', () => {
    it('waits for the debounce before navigating', async () => {
        renderPage();

        await user.type(screen.getByLabelText('Search member'), 'Bruno');

        expect(router.get).not.toHaveBeenCalled();

        await waitForDebounce();

        expect(router.get).toHaveBeenCalledWith(
            '/contributions',
            { community: 7, month: '2026-08', search: 'Bruno' },
            expect.objectContaining({ preserveState: true }),
        );
    });

    it('does not send an empty search parameter', async () => {
        renderPage({ search: 'Bruno' });

        await user.clear(screen.getByLabelText('Search member'));
        await waitForDebounce();

        expect(router.get).toHaveBeenCalledWith(
            '/contributions',
            { community: 7, month: '2026-08' },
            expect.objectContaining({ preserveState: true }),
        );
    });
});

describe('the pagination', () => {
    it('shows the total and navigates to the next page', async () => {
        renderPage({
            members: paginated([member()], {
                current_page: 1,
                last_page: 2,
                total: 20,
                next_page_url: '/contributions?community=7&page=2',
            }),
        });

        expect(screen.getByText('20 members')).toBeInTheDocument();
        expect(screen.getByText('Page 1 of 2')).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Next' }));

        expect(router.visit).toHaveBeenCalledWith(
            '/contributions?community=7&page=2',
            { preserveScroll: true },
        );
    });
});

describe('the month selector', () => {
    it('shows the current reference month and the effective amount', () => {
        renderPage({ referenceMonth: '2026-08', monthlyAmount: '50.00' });

        expect(screen.getByLabelText('Month')).toHaveValue('2026-08');
        expect(screen.getByText('50.00')).toBeInTheDocument();
    });

    it('navigates to the chosen month', async () => {
        renderPage({ referenceMonth: '2026-08' });

        await user.clear(screen.getByLabelText('Month'));
        await user.type(screen.getByLabelText('Month'), '2026-06');
        await user.click(screen.getByRole('button', { name: 'View' }));

        expect(router.get).toHaveBeenCalledWith(
            '/contributions',
            { community: 7, month: '2026-06' },
            expect.objectContaining({ preserveState: true }),
        );
    });
});

describe('the register donation action', () => {
    it('links to the donation registration page for this community', () => {
        renderPage();

        expect(
            screen.getByRole('link', { name: '+ Register donation' }),
        ).toHaveAttribute(
            'href',
            '/contributions/donations/create?community=7',
        );
    });
});

describe('the missing monthly amount', () => {
    it('disables payment registration and warns the user', () => {
        renderPage({
            members: paginated([member({ id: 2, name: 'Bruno Costa' })]),
            monthlyAmount: null,
        });

        expect(
            screen.getByRole('button', { name: 'Register payment' }),
        ).toBeDisabled();
        expect(
            screen.getByText(/Monthly amount is not configured for this month/),
        ).toBeInTheDocument();
    });
});
