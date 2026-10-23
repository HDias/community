import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { UserEvent } from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import ContributionsSettings from '@/pages/contributions/settings';
import { resetInertia, setLayoutProps, submissions } from '../support/inertia';

vi.mock('@inertiajs/react', async () => {
    const { createInertiaMock } = await import('../support/inertia');

    return createInertiaMock();
});

const community = { id: 7, name: 'Quilombo São Roque', slug: 'sao-roque' };

type Props = Parameters<typeof ContributionsSettings>[0];
type Setting = Props['settings'][number];

function setting(overrides: Partial<Setting> = {}): Setting {
    return {
        id: 1,
        monthly_amount: '50.00',
        effective_from: '2026-08-01',
        ...overrides,
    };
}

function renderPage(overrides: Partial<Props> = {}) {
    const props: Props = {
        community,
        settings: [],
        ...overrides,
    };

    return render(<ContributionsSettings {...props} />);
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
                {
                    title: 'Contribution Settings',
                    href: '/contributions/settings?community=7',
                },
            ],
        });
    });
});

describe('the amount form', () => {
    it('posts to the settings endpoint for this community', () => {
        const { container } = renderPage();
        const form = container.querySelector('form');

        expect(form).toHaveAttribute(
            'action',
            '/contributions/settings?community=7',
        );
        expect(form).toHaveAttribute('method', 'post');
    });

    it('requires the amount and effective date', () => {
        renderPage();

        expect(screen.getByLabelText('Monthly amount *')).toBeRequired();
        expect(screen.getByLabelText('Effective from *')).toBeRequired();
    });

    it('submits what the user filled in', async () => {
        renderPage();

        await user.type(screen.getByLabelText('Monthly amount *'), '75');
        await user.type(
            screen.getByLabelText('Effective from *'),
            '2026-09-01',
        );

        await user.click(screen.getByRole('button', { name: 'Update amount' }));

        expect(submissions).toHaveLength(1);
        expect(submissions[0]).toMatchObject({
            action: '/contributions/settings?community=7',
            method: 'post',
            data: {
                monthly_amount: '75',
                effective_from: '2026-09-01',
            },
        });
    });
});

describe('the settings history', () => {
    it('shows an empty state when there is no history yet', () => {
        renderPage({ settings: [] });

        expect(
            screen.getByText('No settings recorded yet.'),
        ).toBeInTheDocument();
    });

    it('lists every prior setting with its effective date', () => {
        renderPage({
            settings: [
                setting({
                    id: 2,
                    monthly_amount: '50.00',
                    effective_from: '2026-08-01',
                }),
                setting({
                    id: 1,
                    monthly_amount: '30.00',
                    effective_from: '2026-06-01',
                }),
            ],
        });

        const rows = screen.getAllByRole('row');

        expect(within(rows[1]).getByText('2026-08-01')).toBeInTheDocument();
        expect(within(rows[2]).getByText('2026-06-01')).toBeInTheDocument();
    });
});
