import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { UserEvent } from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import DonationsCreate from '@/pages/contributions/donations/create';
import { resetInertia, setLayoutProps, submissions } from '../support/inertia';

vi.mock('@inertiajs/react', async () => {
    const { createInertiaMock } = await import('../support/inertia');

    return createInertiaMock();
});

const community = { id: 7, name: 'Quilombo São Roque', slug: 'sao-roque' };

type Props = Parameters<typeof DonationsCreate>[0];

function renderPage(overrides: Partial<Props> = {}) {
    const props: Props = {
        community,
        members: [{ id: 2, name: 'Bruno Costa' }],
        ...overrides,
    };

    return render(<DonationsCreate {...props} />);
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
    it('links the breadcrumbs back to the contributions index', () => {
        renderPage();

        expect(setLayoutProps).toHaveBeenCalledWith({
            breadcrumbs: [
                {
                    title: 'Contributions',
                    href: '/contributions?community=7',
                },
                {
                    title: 'Register Donation',
                    href: '/contributions/donations/create?community=7',
                },
            ],
        });
    });
});

describe('the donor selector', () => {
    it('defaults to a member donor', () => {
        renderPage();

        expect(screen.getByLabelText('Member')).toBeChecked();
        expect(screen.getByLabelText('Member *')).toBeInTheDocument();
    });

    it('shows a name and CPF for an individual donor', async () => {
        renderPage();

        await user.click(screen.getByLabelText('Individual'));

        expect(screen.getByLabelText('Name *')).toBeInTheDocument();
        expect(screen.getByLabelText('CPF *')).toBeInTheDocument();
    });

    it('shows a corporate name and CNPJ for a legal entity donor', async () => {
        renderPage();

        await user.click(screen.getByLabelText('Legal entity'));

        expect(screen.getByLabelText('Corporate name *')).toBeInTheDocument();
        expect(screen.getByLabelText('CNPJ *')).toBeInTheDocument();
    });
});

describe('the donation form', () => {
    it('submits an individual donation with notes as a textarea', async () => {
        renderPage();

        await user.click(screen.getByLabelText('Individual'));
        await user.type(screen.getByLabelText('Name *'), 'Maria Souza');
        await user.type(screen.getByLabelText('CPF *'), '52998224725');
        await user.type(screen.getByLabelText('Amount *'), '200');
        await user.type(screen.getByLabelText('Notes'), 'Coletado na reunião');

        expect(screen.getByLabelText('Notes').tagName).toBe('TEXTAREA');

        await user.click(
            screen.getByRole('button', { name: 'Register donation' }),
        );

        expect(submissions).toHaveLength(1);
        expect(submissions[0]).toMatchObject({
            action: '/contributions/donations?community=7',
            method: 'post',
            data: {
                donor_type: 'individual',
                donor_name: 'Maria Souza',
                donor_document: '529.982.247-25',
                amount: '200',
                notes: 'Coletado na reunião',
            },
        });
    });

    it('formats a CNPJ for a legal entity donor', async () => {
        renderPage();

        await user.click(screen.getByLabelText('Legal entity'));
        await user.type(screen.getByLabelText('CNPJ *'), '11222333000181');

        expect(screen.getByLabelText('CNPJ *')).toHaveValue(
            '11.222.333/0001-81',
        );
    });

    it('requires an amount', () => {
        renderPage();

        expect(screen.getByLabelText('Amount *')).toBeRequired();
    });
});
