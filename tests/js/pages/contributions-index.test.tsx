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
type Member = Props['members'][number];

function member(overrides: Partial<Member> = {}): Member {
    return {
        id: 1,
        name: 'Ana Silva',
        paid: false,
        ...overrides,
    };
}

function renderPage(overrides: Partial<Props> = {}) {
    const props: Props = {
        communities: [{ id: 7, name: 'Quilombo São Roque' }],
        community,
        members: [],
        monthlyAmount: '50.00',
        referenceMonth: '2026-08',
        ...overrides,
    };

    return render(<ContributionsIndex {...props} />);
}

let user: UserEvent;

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
            members: [
                member({ id: 1, name: 'Ana Silva', paid: true }),
                member({ id: 2, name: 'Bruno Costa', paid: false }),
            ],
        });

        const rows = screen.getAllByRole('row');

        expect(within(rows[1]).getByText('Paid')).toBeInTheDocument();
        expect(within(rows[2]).getByText('Pending')).toBeInTheDocument();
    });

    it('only offers a payment action for pending members', () => {
        renderPage({
            members: [
                member({ id: 1, name: 'Ana Silva', paid: true }),
                member({ id: 2, name: 'Bruno Costa', paid: false }),
            ],
        });

        expect(
            screen.getAllByRole('button', { name: 'Register payment' }),
        ).toHaveLength(1);
    });

    it('posts the member and reference month when registering a payment', async () => {
        const { container } = renderPage({
            members: [member({ id: 2, name: 'Bruno Costa' })],
        });

        const form = container.querySelector(
            'form[action="/contributions?community=7"]',
        ) as HTMLFormElement;

        await user.click(
            within(form).getByRole('button', { name: 'Register payment' }),
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

    it('shows an empty state when the community has no members', () => {
        renderPage({ members: [] });

        expect(
            screen.getByText('No members in this community yet.'),
        ).toBeInTheDocument();
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

        expect(router.visit).toHaveBeenCalledWith(
            '/contributions?community=7&month=2026-06',
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
