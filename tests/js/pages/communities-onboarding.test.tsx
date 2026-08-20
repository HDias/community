import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { UserEvent } from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import Onboarding from '@/pages/communities/onboarding';
import { jsonResponse } from '../support/fetch';
import {
    resetInertia,
    setFormErrors,
    setFormProcessing,
    submissions,
} from '../support/inertia';

vi.mock('@inertiajs/react', async () => {
    const { createInertiaMock } = await import('../support/inertia');

    return createInertiaMock();
});

const states = [
    { id: 35, sigla: 'SP', nome: 'São Paulo' },
    { id: 33, sigla: 'RJ', nome: 'Rio de Janeiro' },
];

const spCities = [{ nome: 'Santos', codigo_ibge: '3548500' }];

let user: UserEvent;
let fetchMock: ReturnType<typeof vi.fn>;

/** Opens the create form, which starts hidden behind the intro screen. */
async function openForm() {
    const result = render(<Onboarding canCreate />);

    await user.click(
        screen.getByRole('button', { name: '+ Create community' }),
    );

    return result;
}

beforeEach(() => {
    user = userEvent.setup();
    resetInertia();
    fetchMock = vi
        .fn()
        .mockImplementation((url: string) =>
            Promise.resolve(
                jsonResponse(url === '/api/brasil/states' ? states : spCities),
            ),
        );
    vi.stubGlobal('fetch', fetchMock);
});

afterEach(() => {
    resetInertia();
    vi.unstubAllGlobals();
});

describe('intro', () => {
    it('always explains that the user has no community', () => {
        render(<Onboarding canCreate={false} />);

        expect(
            screen.getByText("You don't belong to any community yet."),
        ).toBeInTheDocument();
    });

    it('tells a user who cannot create to wait for an administrator', () => {
        render(<Onboarding canCreate={false} />);

        expect(
            screen.getByText(
                'Please wait for an administrator to add you to a community.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: '+ Create community' }),
        ).not.toBeInTheDocument();
    });

    it('invites a user who can create to make one', () => {
        render(<Onboarding canCreate />);

        expect(
            screen.getByText('Create a new community to get started.'),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: '+ Create community' }),
        ).toBeInTheDocument();
        expect(
            screen.queryByText(
                'Please wait for an administrator to add you to a community.',
            ),
        ).not.toBeInTheDocument();
    });

    it('titles the page', () => {
        render(<Onboarding canCreate />);

        expect(document.title).toBe('Welcome');
    });
});

describe('the create form', () => {
    it('stays hidden until asked for', () => {
        render(<Onboarding canCreate />);

        expect(
            screen.queryByLabelText('Community name *'),
        ).not.toBeInTheDocument();
    });

    it('replaces the intro once opened', async () => {
        await openForm();

        expect(screen.getByLabelText('Community name *')).toBeInTheDocument();
        expect(
            screen.queryByText("You don't belong to any community yet."),
        ).not.toBeInTheDocument();
    });

    it('posts to the communities endpoint', async () => {
        const { container } = await openForm();

        expect(container.querySelector('form')).toHaveAttribute(
            'action',
            '/communities',
        );
        expect(container.querySelector('form')).toHaveAttribute(
            'method',
            'post',
        );
    });

    it('requires a community name', async () => {
        await openForm();

        expect(screen.getByLabelText('Community name *')).toBeRequired();
    });

    it('does not require a description', async () => {
        await openForm();

        expect(screen.getByLabelText('Description')).not.toBeRequired();
    });

    /**
     * These names are the contract with `SaveCommunityRequest`. The address
     * trio comes from `LocationFields`, which uses `address`/`state`/`city` --
     * deliberately different from the `address_*` names the member pages use.
     */
    it('submits under the names the backend validates', async () => {
        const { container } = await openForm();

        await screen.findByText('Select state');

        const names = Array.from(
            container.querySelectorAll('[name]'),
            (field) => field.getAttribute('name'),
        );

        expect(names).toEqual(
            expect.arrayContaining([
                'name',
                'description',
                'address',
                'state',
                'city',
            ]),
        );
    });

    it('sends what the user typed', async () => {
        await openForm();

        await user.type(
            screen.getByLabelText('Community name *'),
            'Quilombo São Roque',
        );
        await user.type(
            screen.getByLabelText('Description'),
            'A riverside community',
        );
        await user.click(
            screen.getByRole('button', { name: 'Create community' }),
        );

        expect(submissions).toHaveLength(1);
        expect(submissions[0]).toMatchObject({
            action: '/communities',
            method: 'post',
            data: {
                name: 'Quilombo São Roque',
                description: 'A riverside community',
            },
        });
    });

    it('returns to the intro on cancel', async () => {
        await openForm();

        await user.click(screen.getByRole('button', { name: 'Cancel' }));

        expect(
            screen.getByText("You don't belong to any community yet."),
        ).toBeInTheDocument();
        expect(
            screen.queryByLabelText('Community name *'),
        ).not.toBeInTheDocument();
    });
});

describe('server feedback', () => {
    it('shows the name error next to the name field', async () => {
        setFormErrors({ name: 'That name is taken.' });

        await openForm();

        expect(screen.getByText('That name is taken.')).toBeInTheDocument();
    });

    it('shows the description error', async () => {
        setFormErrors({ description: 'Keep it shorter.' });

        await openForm();

        expect(screen.getByText('Keep it shorter.')).toBeInTheDocument();
    });

    it('renders no errors when the server reports none', async () => {
        const { container } = await openForm();

        expect(container.querySelectorAll('p.text-red-600')).toHaveLength(0);
    });

    it('blocks a second submission while the first is in flight', async () => {
        setFormProcessing(true);

        await openForm();

        // The spinner carries `aria-label="Loading"`, which joins the button's
        // accessible name, so the name is matched loosely here.
        expect(
            screen.getByRole('button', { name: /Create community/ }),
        ).toBeDisabled();
        expect(screen.getByRole('status')).toBeInTheDocument();
    });

    it('lets the form be submitted when idle', async () => {
        await openForm();

        expect(
            screen.getByRole('button', { name: 'Create community' }),
        ).toBeEnabled();
        expect(screen.queryByRole('status')).not.toBeInTheDocument();
    });
});
