import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { UserEvent } from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import Dashboard from '@/pages/dashboard';
import { resetInertia } from '../support/inertia';

vi.mock('@inertiajs/react', async () => {
    const { createInertiaMock } = await import('../support/inertia');

    return createInertiaMock();
});

type HistoryEntry = {
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
};

type MyContributions = {
    totalPaidThisYear: string;
    currentMonthStatus: 'paid' | 'pending';
    nextDueMonth: string | null;
    history: HistoryEntry[];
};

type Props = Parameters<typeof Dashboard>[0] & {
    myContributions: MyContributions | null;
};

const community = {
    name: 'Quilombo São Roque',
    slug: 'sao-roque',
    description: null,
};

function contribution(overrides: Partial<HistoryEntry> = {}): HistoryEntry {
    return {
        id: 1,
        type: 'monthly',
        amount: '50.00',
        reference_month: '2026-06',
        status: 'active',
        registered_at: '2026-06-10T12:00:00+00:00',
        registered_by_name: 'Treasurer',
        reversed_at: null,
        reversed_by_name: null,
        reversal_reason: null,
        ...overrides,
    };
}

function myContributions(
    overrides: Partial<MyContributions> = {},
): MyContributions {
    return {
        totalPaidThisYear: '130.00',
        currentMonthStatus: 'paid',
        nextDueMonth: '2026-07',
        history: [contribution()],
        ...overrides,
    };
}

function renderPage(overrides: Partial<Props> = {}) {
    const props: Props = {
        community,
        communities: [{ id: 7, name: community.name }],
        memberCount: 12,
        memberSince: '2025-01-01',
        communityRole: 'member',
        administrationMembers: [],
        canManage: false,
        myContributions: myContributions(),
        ...overrides,
    };

    return render(<Dashboard {...props} />);
}

let user: UserEvent;

beforeEach(() => {
    user = userEvent.setup();
    resetInertia();
});

afterEach(() => {
    resetInertia();
});

describe('the my contributions card', () => {
    it('renders the yearly total, the current month status and the next due month', () => {
        renderPage();

        expect(screen.getByText('My Contributions')).toBeInTheDocument();
        expect(screen.getByText('R$ 130.00')).toBeInTheDocument();
        expect(screen.getByText('Paid')).toBeInTheDocument();
        expect(screen.getByText('Next due: July 2026')).toBeInTheDocument();
    });

    it('renders a pending badge for the current month', () => {
        renderPage({
            myContributions: myContributions({
                currentMonthStatus: 'pending',
                nextDueMonth: '2026-06',
            }),
        });

        expect(screen.getByText('Pending')).toBeInTheDocument();
        expect(screen.getByText('Next due: June 2026')).toBeInTheDocument();
    });

    it('shows an empty state when the member has no contributions', () => {
        renderPage({
            myContributions: myContributions({
                totalPaidThisYear: '0.00',
                currentMonthStatus: 'pending',
                nextDueMonth: null,
                history: [],
            }),
        });

        expect(screen.getByText('No contributions yet')).toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'View history' }),
        ).not.toBeInTheDocument();
    });

    it('shows the empty state when the member has no community', () => {
        renderPage({ community: null, myContributions: null });

        expect(
            screen.queryByText('No contributions yet'),
        ).not.toBeInTheDocument();
    });
});

describe('the history modal', () => {
    it('opens a modal listing every contribution of the member', async () => {
        renderPage({
            myContributions: myContributions({
                history: [
                    contribution({
                        id: 3,
                        type: 'donation',
                        amount: '30.00',
                        reference_month: '2026-06',
                    }),
                    contribution({
                        id: 2,
                        amount: '50.00',
                        reference_month: '2026-06',
                    }),
                    contribution({
                        id: 1,
                        amount: '20.00',
                        reference_month: '2026-05',
                        status: 'reversed',
                        reversed_at: '2026-05-12T12:00:00+00:00',
                        reversed_by_name: 'Auditor',
                        reversal_reason: 'Mistake.',
                    }),
                ],
            }),
        });

        await user.click(screen.getByRole('button', { name: 'View history' }));

        const dialog = await screen.findByRole('dialog');

        expect(
            within(dialog).getByText('Contribution history'),
        ).toBeInTheDocument();

        const items = within(dialog).getAllByRole('listitem');

        expect(items).toHaveLength(3);
        expect(within(items[0]).getByText(/Donation/)).toBeInTheDocument();
        expect(
            within(items[0]).getByText('Amount: R$ 30.00'),
        ).toBeInTheDocument();
        expect(within(items[1]).getByText(/Monthly/)).toBeInTheDocument();
        expect(within(items[2]).getByText('Reversed')).toBeInTheDocument();
        expect(
            within(items[2]).getByText('Amount: R$ 20.00'),
        ).toBeInTheDocument();
    });

    it('closes the modal without leaving the page', async () => {
        renderPage();

        await user.click(screen.getByRole('button', { name: 'View history' }));

        const dialog = await screen.findByRole('dialog');

        await user.click(within(dialog).getByRole('button', { name: 'Close' }));

        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });
});
