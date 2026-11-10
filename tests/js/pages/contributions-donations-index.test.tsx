import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { UserEvent } from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import DonationsIndex from '@/pages/contributions/donations/index';
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

type Props = Parameters<typeof DonationsIndex>[0];
type PaginatedDonations = Extract<Props['donations'], { data: unknown }>;
type Donation = PaginatedDonations['data'][number];

function donation(overrides: Partial<Donation> = {}): Donation {
    return {
        id: 42,
        donor_type: 'individual',
        donor_name: 'Maria Souza',
        donor_document: '52998224725',
        member_name: null,
        amount: '100.00',
        reference_month: '2026-08',
        notes: null,
        status: 'active',
        registered_at: '2026-08-10T12:00:00+00:00',
        registered_by_name: 'Treasurer',
        reversed_at: null,
        reversed_by_name: null,
        reversal_reason: null,
        ...overrides,
    };
}

function paginated(
    data: Donation[],
    overrides: Partial<PaginatedDonations> = {},
): PaginatedDonations {
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
        donations: paginated([]),
        referenceMonth: '2026-08',
        search: null,
        ...overrides,
    };

    return render(<DonationsIndex {...props} />);
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
    it('scopes the breadcrumbs to the community and donations page', () => {
        renderPage();

        expect(setLayoutProps).toHaveBeenCalledWith({
            breadcrumbs: [
                { title: 'Contributions', href: '/contributions' },
                {
                    title: 'Quilombo São Roque',
                    href: '/contributions?community=7',
                },
                {
                    title: 'Donations',
                    href: '/contributions/donations?community=7',
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
        });

        expect(
            screen.getByText('Select a community to manage its donations.'),
        ).toBeInTheDocument();
    });

    it('links to the donation registration page', () => {
        renderPage();

        expect(
            screen.getByRole('link', { name: '+ Register donation' }),
        ).toHaveAttribute(
            'href',
            '/contributions/donations/create?community=7',
        );
    });
});

describe('the donations table', () => {
    it('renders the donor, document, amount, month and status', () => {
        renderPage({ donations: paginated([donation()]) });

        expect(screen.getByText('Maria Souza')).toBeInTheDocument();
        expect(screen.getByText('529.982.247-25')).toBeInTheDocument();
        expect(screen.getByText('R$ 100.00')).toBeInTheDocument();
        expect(screen.getByText('2026-08')).toBeInTheDocument();
        expect(screen.getByText('Active')).toBeInTheDocument();
    });

    it('formats a legal entity cnpj', () => {
        renderPage({
            donations: paginated([
                donation({
                    id: 1,
                    donor_type: 'organization',
                    donor_name: 'Padaria Central LTDA',
                    donor_document: '11222333000181',
                }),
            ]),
        });

        expect(screen.getByText('11.222.333/0001-81')).toBeInTheDocument();
    });

    it('shows the member name for a member donor', () => {
        renderPage({
            donations: paginated([
                donation({
                    id: 1,
                    donor_type: 'member',
                    donor_name: null,
                    donor_document: null,
                    member_name: 'Bruno Costa',
                }),
            ]),
        });

        expect(screen.getByText('Bruno Costa')).toBeInTheDocument();
    });

    it('shows a reversed badge and hides the revert action', () => {
        renderPage({
            donations: paginated([
                donation({
                    status: 'reversed',
                    reversed_at: '2026-08-12T12:00:00+00:00',
                    reversed_by_name: 'Auditor',
                    reversal_reason: 'Refunded.',
                }),
            ]),
        });

        expect(screen.getByText('Reversed')).toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Revert' }),
        ).not.toBeInTheDocument();
    });

    it('shows an empty state', () => {
        renderPage({ donations: paginated([]) });

        expect(
            screen.getByText('No donations registered yet.'),
        ).toBeInTheDocument();
    });

    it('shows a search-specific empty state', () => {
        renderPage({ donations: paginated([]), search: 'nobody' });

        expect(
            screen.getByText('No donations match your search.'),
        ).toBeInTheDocument();
    });
});

describe('the history column', () => {
    it('opens the modal with the registration and reversal events', async () => {
        renderPage({
            donations: paginated([
                donation({
                    status: 'reversed',
                    reversed_at: '2026-08-12T12:00:00+00:00',
                    reversed_by_name: 'Auditor',
                    reversal_reason: 'Refunded.',
                }),
            ]),
        });

        await user.click(screen.getByRole('button', { name: 'History' }));

        expect(screen.getByText('Payment history')).toBeInTheDocument();
        expect(screen.getByText(/^Registered /)).toBeInTheDocument();
        expect(screen.getByText('By Treasurer')).toBeInTheDocument();
        expect(screen.getByText('Amount: R$ 100.00')).toBeInTheDocument();
        expect(screen.getByText(/^Reversed /)).toBeInTheDocument();
        expect(screen.getByText('By Auditor')).toBeInTheDocument();
        expect(screen.getByText('Reason: Refunded.')).toBeInTheDocument();
    });
});

describe('the donation search', () => {
    it('waits for the debounce before navigating', async () => {
        renderPage();

        await user.type(screen.getByLabelText('Search donor'), 'Maria');

        expect(router.get).not.toHaveBeenCalled();

        await waitForDebounce();

        expect(router.get).toHaveBeenCalledWith(
            '/contributions/donations',
            { community: 7, month: '2026-08', search: 'Maria' },
            expect.objectContaining({ preserveState: true }),
        );
    });

    it('does not send an empty search parameter', async () => {
        renderPage({ search: 'Maria' });

        await user.clear(screen.getByLabelText('Search donor'));
        await waitForDebounce();

        expect(router.get).toHaveBeenCalledWith(
            '/contributions/donations',
            { community: 7, month: '2026-08' },
            expect.objectContaining({ preserveState: true }),
        );
    });
});

describe('the month filter', () => {
    it('navigates to the chosen month', async () => {
        renderPage({ referenceMonth: '2026-08' });

        await user.clear(screen.getByLabelText('Month'));
        await user.type(screen.getByLabelText('Month'), '2026-06');
        await user.click(screen.getByRole('button', { name: 'View' }));

        expect(router.get).toHaveBeenCalledWith(
            '/contributions/donations',
            { community: 7, month: '2026-06' },
            expect.objectContaining({ preserveState: true }),
        );
    });

    it('omits the month when viewing all months', async () => {
        renderPage({ referenceMonth: '2026-08' });

        await user.click(screen.getByRole('button', { name: 'All months' }));

        expect(router.get).toHaveBeenCalledWith(
            '/contributions/donations',
            { community: 7 },
            expect.objectContaining({ preserveState: true }),
        );
    });
});

describe('the donation revert dialog', () => {
    it('requires a reason and posts it to the reversal endpoint', async () => {
        renderPage({ donations: paginated([donation()]) });

        await user.click(screen.getByRole('button', { name: 'Revert' }));

        expect(screen.getByText('Revert donation?')).toBeInTheDocument();
        expect(screen.getByLabelText('Reason *')).toBeRequired();
        expect(submissions).toHaveLength(0);

        await user.type(screen.getByLabelText('Reason *'), 'Refunded.');
        await user.click(
            screen.getByRole('button', { name: 'Revert donation' }),
        );

        expect(submissions).toHaveLength(1);
        expect(submissions[0]).toMatchObject({
            action: '/contributions/42/reverse?community=7',
            method: 'post',
            data: { reversal_reason: 'Refunded.' },
        });
    });

    it('does not submit when the revert is cancelled', async () => {
        renderPage({ donations: paginated([donation()]) });

        await user.click(screen.getByRole('button', { name: 'Revert' }));
        await user.click(screen.getByRole('button', { name: 'Cancel' }));

        expect(submissions).toHaveLength(0);
    });
});

describe('the pagination', () => {
    it('shows the total and navigates to the next page', async () => {
        renderPage({
            donations: paginated([donation()], {
                current_page: 1,
                last_page: 2,
                total: 20,
                next_page_url: '/contributions/donations?community=7&page=2',
            }),
        });

        expect(screen.getByText('20 donations')).toBeInTheDocument();
        expect(screen.getByText('Page 1 of 2')).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Next' }));

        expect(router.visit).toHaveBeenCalledWith(
            '/contributions/donations?community=7&page=2',
            { preserveScroll: true },
        );
    });
});
