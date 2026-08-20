import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { UserEvent } from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import MembersCreate from '@/pages/members/create';
import { deferred, jsonResponse } from '../support/fetch';
import {
    resetInertia,
    setFormErrors,
    setFormProcessing,
    setLayoutProps,
    submissions,
} from '../support/inertia';

vi.mock('@inertiajs/react', async () => {
    const { createInertiaMock } = await import('../support/inertia');

    return createInertiaMock();
});

const community = { id: 7, name: 'Quilombo São Roque', slug: 'sao-roque' };

const states = [
    { id: 35, sigla: 'SP', nome: 'São Paulo' },
    { id: 33, sigla: 'RJ', nome: 'Rio de Janeiro' },
];

const citiesByState: Record<string, { nome: string; codigo_ibge: string }[]> = {
    SP: [
        { nome: 'Santos', codigo_ibge: '3548500' },
        { nome: 'Campinas', codigo_ibge: '3509502' },
    ],
    RJ: [{ nome: 'Niterói', codigo_ibge: '3303302' }],
};

/**
 * The names `StoreMemberRequest::rules()` validates. Kept as a literal list so a
 * renamed input fails here -- `tsc` cannot see this contract.
 */
const backendFieldNames = [
    'address_city',
    'address_neighborhood',
    'address_number',
    'address_state',
    'address_street',
    'address_zip',
    'birth_date',
    'cpf',
    'email',
    'name',
    'nickname',
    'phone',
    'profession',
    'social_name',
];

let user: UserEvent;

/** Resolves immediately with the canned state and city lists. */
function stubBrasilApi() {
    const fetchMock = vi.fn((url: string) => {
        if (url === '/api/brasil/states') {
            return Promise.resolve(jsonResponse(states));
        }

        const uf = url.replace('/api/brasil/cities/', '');

        return Promise.resolve(jsonResponse(citiesByState[uf] ?? []));
    });

    vi.stubGlobal('fetch', fetchMock);

    return fetchMock;
}

function renderPage() {
    return render(<MembersCreate community={community} />);
}

/**
 * Picks `sigla` from the state dropdown. Waits on the option rather than the
 * `Select state` placeholder, because the placeholder is gone once a state is
 * already selected -- which this has to survive to test re-selection.
 */
async function chooseState(sigla: string) {
    await user.click(screen.getByRole('combobox', { name: 'State' }));
    await user.click(
        await screen.findByRole('option', {
            name: new RegExp(`^${sigla} - `),
        }),
    );
}

/**
 * Fills every field marked `required`. Without this the browser's own
 * constraint validation refuses to submit, and nothing reaches the server.
 */
async function fillRequiredFields() {
    await user.type(screen.getByLabelText('Name *'), 'Dona Maria');
    await user.type(screen.getByLabelText('Email *'), 'maria@example.org');
    await user.type(screen.getByLabelText('Birth Date *'), '1962-03-04');
    await user.type(screen.getByLabelText('CPF *'), '52998224725');
}

beforeEach(() => {
    user = userEvent.setup();
    resetInertia();
    stubBrasilApi();
});

afterEach(() => {
    resetInertia();
    vi.unstubAllGlobals();
});

describe('page chrome', () => {
    it('titles the page after the community', async () => {
        renderPage();

        await waitFor(() =>
            expect(document.title).toBe('New Member - Quilombo São Roque'),
        );
    });

    it('scopes the breadcrumbs to the community', () => {
        renderPage();

        expect(setLayoutProps).toHaveBeenCalledWith({
            breadcrumbs: [
                { title: 'Members', href: '/members?community=7' },
                { title: 'New Member', href: '/members/create?community=7' },
            ],
        });
    });

    it('heads the form', () => {
        renderPage();

        expect(
            screen.getByRole('heading', { name: 'Register Member' }),
        ).toBeInTheDocument();
    });

    it('offers a way back to the community members list', () => {
        renderPage();

        expect(screen.getByRole('link', { name: 'Cancel' })).toHaveAttribute(
            'href',
            '/members?community=7',
        );
    });
});

describe('the form envelope', () => {
    it('posts to the members endpoint for this community', () => {
        const { container } = renderPage();
        const form = container.querySelector('form');

        expect(form).toHaveAttribute('action', '/members?community=7');
        expect(form).toHaveAttribute('method', 'post');
    });

    it('submits exactly the names the backend validates', async () => {
        const { container } = renderPage();

        await screen.findByText('Select state');

        const names = Array.from(
            container.querySelectorAll('[name]'),
            (field) => field.getAttribute('name'),
        );

        expect([...new Set(names)].sort()).toEqual(backendFieldNames);
    });

    it.each(['Name *', 'Email *', 'CPF *', 'Birth Date *'])(
        'requires %s',
        (label) => {
            renderPage();

            expect(screen.getByLabelText(label)).toBeRequired();
        },
    );

    it.each([
        'Social Name',
        'Nickname',
        'Phone',
        'Profession',
        'Street',
        'Number',
        'Neighborhood',
        'ZIP Code',
    ])('leaves %s optional', (label) => {
        renderPage();

        expect(screen.getByLabelText(label)).not.toBeRequired();
    });
});

describe('the state and city pair', () => {
    it('waits for the state list before offering a choice', () => {
        const pending = deferred<Response>();

        vi.stubGlobal(
            'fetch',
            vi.fn(() => pending.promise),
        );

        renderPage();

        expect(
            within(screen.getByRole('combobox', { name: 'State' })).getByText(
                'Loading...',
            ),
        ).toBeInTheDocument();
    });

    it('offers the states once they arrive', async () => {
        renderPage();

        expect(await screen.findByText('Select state')).toBeInTheDocument();

        await user.click(screen.getByRole('combobox', { name: 'State' }));

        expect(
            screen.getByRole('option', { name: 'SP - São Paulo' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('option', { name: 'RJ - Rio de Janeiro' }),
        ).toBeInTheDocument();
    });

    it('locks the city field until a state is picked', async () => {
        renderPage();

        await screen.findByText('Select state');

        const city = screen.getByLabelText('City');

        expect(city).toBeDisabled();
        expect(city).toHaveAttribute('placeholder', 'Select a state first');
    });

    it('does not ask for cities before a state is picked', async () => {
        const fetchMock = stubBrasilApi();

        renderPage();

        await screen.findByText('Select state');

        expect(fetchMock).toHaveBeenCalledTimes(1);
        expect(fetchMock).toHaveBeenCalledWith('/api/brasil/states');
    });

    it('loads the cities of the chosen state', async () => {
        const fetchMock = stubBrasilApi();

        renderPage();
        await chooseState('SP');

        await waitFor(() =>
            expect(fetchMock).toHaveBeenCalledWith('/api/brasil/cities/SP'),
        );
    });

    it('turns the city field into a dropdown of that state', async () => {
        renderPage();
        await chooseState('SP');

        const city = await screen.findByRole('combobox', { name: 'City' });

        await user.click(city);

        expect(
            screen.getByRole('option', { name: 'Santos' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('option', { name: 'Campinas' }),
        ).toBeInTheDocument();
    });

    it('reports progress while the cities load', async () => {
        const pending = deferred<Response>();
        const fetchMock = vi.fn((url: string) =>
            url === '/api/brasil/states'
                ? Promise.resolve(jsonResponse(states))
                : pending.promise,
        );

        vi.stubGlobal('fetch', fetchMock);

        renderPage();
        await chooseState('SP');

        const city = await screen.findByRole('combobox', { name: 'City' });

        expect(within(city).getByText('Loading...')).toBeInTheDocument();

        pending.resolve(jsonResponse(citiesByState.SP));

        await waitFor(() =>
            expect(within(city).getByText('Select city')).toBeInTheDocument(),
        );
    });

    it('replaces the city list when the state changes', async () => {
        renderPage();
        await chooseState('SP');

        await user.click(await screen.findByRole('combobox', { name: 'City' }));
        expect(
            await screen.findByRole('option', { name: 'Santos' }),
        ).toBeInTheDocument();

        await user.keyboard('{Escape}');
        await chooseState('RJ');

        await user.click(screen.getByRole('combobox', { name: 'City' }));

        expect(
            await screen.findByRole('option', { name: 'Niterói' }),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('option', { name: 'Santos' }),
        ).not.toBeInTheDocument();
    });
});

describe('submitting', () => {
    it('sends what the user filled in', async () => {
        renderPage();

        await fillRequiredFields();
        await user.type(screen.getByLabelText('Profession'), 'Artesã');
        await user.type(screen.getByLabelText('Street'), 'Rua das Flores');

        await chooseState('SP');

        await user.click(await screen.findByRole('combobox', { name: 'City' }));
        await user.click(await screen.findByRole('option', { name: 'Santos' }));

        await user.click(
            screen.getByRole('button', { name: 'Register Member' }),
        );

        expect(submissions).toHaveLength(1);
        expect(submissions[0]).toMatchObject({
            action: '/members?community=7',
            method: 'post',
            data: {
                name: 'Dona Maria',
                email: 'maria@example.org',
                birth_date: '1962-03-04',
                profession: 'Artesã',
                address_street: 'Rua das Flores',
                address_state: 'SP',
                address_city: 'Santos',
            },
        });
    });

    /**
     * `CpfInput` and `PhoneInput` mask as the user types, so the browser posts
     * punctuation. `StoreMemberRequest::prepareForValidation()` is what strips it
     * back to digits.
     */
    it('posts the CPF and phone masked', async () => {
        renderPage();

        await fillRequiredFields();
        await user.type(screen.getByLabelText('Phone'), '11987654321');

        await user.click(
            screen.getByRole('button', { name: 'Register Member' }),
        );

        expect(submissions[0].data).toMatchObject({
            cpf: '529.982.247-25',
            phone: '(11) 98765-4321',
        });
    });

    it('blocks submission while a request is in flight', () => {
        setFormProcessing(true);

        renderPage();

        expect(
            screen.getByRole('button', { name: 'Register Member' }),
        ).toBeDisabled();
    });

    it('allows submission when idle', () => {
        renderPage();

        expect(
            screen.getByRole('button', { name: 'Register Member' }),
        ).toBeEnabled();
    });
});

describe('server-side validation', () => {
    it('renders no errors when the server reports none', () => {
        const { container } = renderPage();

        expect(container.querySelectorAll('p.text-red-600')).toHaveLength(0);
    });

    it.each([
        ['name', 'Name *'],
        ['email', 'Email *'],
        ['cpf', 'CPF *'],
        ['birth_date', 'Birth Date *'],
        ['social_name', 'Social Name'],
        ['nickname', 'Nickname'],
        ['phone', 'Phone'],
        ['profession', 'Profession'],
        ['address_street', 'Street'],
        ['address_number', 'Number'],
        ['address_neighborhood', 'Neighborhood'],
        ['address_city', 'City'],
        ['address_state', 'State'],
        ['address_zip', 'ZIP Code'],
    ])('shows the %s error beside its field', (field, label) => {
        setFormErrors({ [field]: `Bad ${field}.` });

        renderPage();

        const group = screen.getByText(label).closest('div');

        expect(
            within(group as HTMLElement).getByText(`Bad ${field}.`),
        ).toBeInTheDocument();
    });
});
