import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { UserEvent } from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import ContributionsReport from '@/pages/contributions/report';
import { resetInertia, router } from '../support/inertia';

vi.mock('@inertiajs/react', async () => {
    const { createInertiaMock } = await import('../support/inertia');

    return createInertiaMock();
});

const community = { id: 7, name: 'Quilombo São Roque', slug: 'sao-roque' };

type Props = Parameters<typeof ContributionsReport>[0];
type Defaulter = Props['defaulters'][number];

function defaulter(overrides: Partial<Defaulter> = {}): Defaulter {
    return {
        user_id: 1,
        name: 'Ana Silva',
        months_owed: 1,
        last_payment: null,
        overdue_3plus: false,
        ...overrides,
    };
}

function renderPage(overrides: Partial<Props> = {}) {
    const props: Props = {
        community,
        referenceMonth: '2026-08',
        report: null,
        defaulters: [],
        ...overrides,
    };

    return render(<ContributionsReport {...props} />);
}

let user: UserEvent;

beforeEach(() => {
    user = userEvent.setup();
    resetInertia();
});

afterEach(() => {
    resetInertia();
});

describe('the defaulters table', () => {
    it('shows an empty state when nobody is defaulting', () => {
        renderPage({ defaulters: [] });

        expect(
            screen.getByText('No defaulters for this month.'),
        ).toBeInTheDocument();
    });

    it('lists every defaulter with their months owed', () => {
        renderPage({
            defaulters: [
                defaulter({ user_id: 1, name: 'Ana Silva', months_owed: 1 }),
                defaulter({
                    user_id: 2,
                    name: 'Bruno Costa',
                    months_owed: 4,
                    overdue_3plus: true,
                }),
            ],
        });

        const rows = screen.getAllByRole('row');

        expect(within(rows[1]).getByText('Ana Silva')).toBeInTheDocument();
        expect(within(rows[1]).getByText('1')).toBeInTheDocument();
        expect(within(rows[2]).getByText('Bruno Costa')).toBeInTheDocument();
        expect(within(rows[2]).getByText('4')).toBeInTheDocument();
    });

    it('highlights defaulters overdue 3 or more months', () => {
        renderPage({
            defaulters: [
                defaulter({ name: 'Bruno Costa', overdue_3plus: true }),
            ],
        });

        const row = screen.getAllByRole('row')[1];

        expect(row.className).toContain('text-red');
    });
});

describe('the month selector', () => {
    it('shows the current reference month', () => {
        renderPage({ referenceMonth: '2026-08' });

        expect(screen.getByLabelText('Month')).toHaveValue('2026-08');
    });

    it('navigates to the chosen month', async () => {
        renderPage({ referenceMonth: '2026-08' });

        await user.clear(screen.getByLabelText('Month'));
        await user.type(screen.getByLabelText('Month'), '2026-06');
        await user.click(screen.getByRole('button', { name: 'View' }));

        expect(router.visit).toHaveBeenCalledWith(
            '/contributions/report?community=7&month=2026-06',
        );
    });
});
