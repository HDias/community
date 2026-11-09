import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';
import {
    ContributionHistoryDialog

} from '@/components/contribution-history-dialog';
import type {ContributionHistoryEntry} from '@/components/contribution-history-dialog';

const reversed: ContributionHistoryEntry = {
    id: 1,
    amount: '50.00',
    status: 'reversed',
    registered_at: '2026-08-10T12:00:00+00:00',
    registered_by_name: 'Treasurer',
    reversed_at: '2026-08-12T12:00:00+00:00',
    reversed_by_name: 'Auditor',
    reversal_reason: 'Wrong amount',
};

const active: ContributionHistoryEntry = {
    id: 2,
    amount: '50.00',
    status: 'active',
    registered_at: '2026-08-15T12:00:00+00:00',
    registered_by_name: 'Treasurer',
    reversed_at: null,
    reversed_by_name: null,
    reversal_reason: null,
};

describe('the contribution history dialog', () => {
    it('disables the button when there is no history', () => {
        render(<ContributionHistoryDialog history={[]} />);

        expect(screen.getByRole('button', { name: 'History' })).toBeDisabled();
    });

    it('enables the button when there is history', () => {
        render(<ContributionHistoryDialog history={[active]} />);

        expect(screen.getByRole('button', { name: 'History' })).toBeEnabled();
    });

    it('renders every event in the given order', async () => {
        const user = userEvent.setup();

        render(<ContributionHistoryDialog history={[reversed, active]} />);

        await user.click(screen.getByRole('button', { name: 'History' }));

        const items = screen.getAllByRole('listitem');

        expect(items).toHaveLength(2);
        expect(within(items[0]).getByText('Reversed')).toBeInTheDocument();
        expect(within(items[0]).getByText('By Treasurer')).toBeInTheDocument();
        expect(
            within(items[0]).getByText('Amount: R$ 50.00'),
        ).toBeInTheDocument();
        expect(within(items[0]).getByText('By Auditor')).toBeInTheDocument();
        expect(
            within(items[0]).getByText('Reason: Wrong amount'),
        ).toBeInTheDocument();
        expect(within(items[1]).getByText('Active')).toBeInTheDocument();
        expect(within(items[1]).queryByText(/Reason:/)).not.toBeInTheDocument();
    });
});
