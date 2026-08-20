import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { UserEvent } from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import MembersEdit from '@/pages/members/edit';
import type { Profile } from '@/types/auth';
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
 * A saved profile as the controller shares it: CPF and phone are digits only,
 * because the request object strips the mask before storing them.
 */
const profile: Profile = {
    id: 12,
    user_id: 2,
    social_name: 'Maria Aparecida',
    nickname: 'Dona Cida',
    cpf: '52998224725',
    birth_date: '1962-03-04',
    phone: '11987654321',
    profession: 'Artesã',
    address_street: 'Rua das Flores',
    address_number: '42',
    address_neighborhood: 'Centro',
    address_city: 'Santos',
    address_state: 'SP',
    address_zip: '11010-000',
};

type MemberProp = {
    id: number;
    name: string;
    email: string;
    profile: Profile | null;
};

const member: MemberProp = {
    id: 2,
    name: 'Dona Maria',
    email: 'maria@example.org',
    profile,
};

/**
 * The names `UpdateMemberRequest::rules()` validates. Kept as a literal list so
 * a renamed input fails here -- `tsc` cannot see this contract.
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

function renderPage(overrides: Partial<MemberProp> = {}) {
    return render(
        <MembersEdit
            member={{ ...member, ...overrides }}
            community={community}
        />,
    );
}

/** Renders the page for a member who has no profile row yet. */
function renderWithoutProfile() {
    return renderPage({ profile: null });
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
    it('titles the page after the member and the community', async () => {
        renderPage();

        await waitFor(() =>
            expect(document.title).toBe('Edit Dona Maria - Quilombo São Roque'),
        );
    });

    it('names the member in the breadcrumbs and scopes both to the community', () => {
        renderPage();

        expect(setLayoutProps).toHaveBeenCalledWith({
            breadcrumbs: [
                { title: 'Members', href: '/members?community=7' },
                {
                    title: 'Dona Maria',
                    href: '/members/2/edit?community=7',
                },
            ],
        });
    });

    it('heads the form', () => {
        renderPage();

        expect(
            screen.getByRole('heading', { name: 'Edit Member' }),
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
    it('puts to this member within this community', () => {
        const { container } = renderPage();
        const form = container.querySelector('form');

        expect(form).toHaveAttribute('action', '/members/2?community=7');
        expect(form).toHaveAttribute('method', 'put');
    });

    it('submits exactly the names the backend validates', async () => {
        const { container } = renderPage();

        await screen.findByRole('combobox', { name: 'City' });

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

describe('pre-populating from the saved member', () => {
    it.each([
        ['Name *', 'Dona Maria'],
        ['Email *', 'maria@example.org'],
        ['Birth Date *', '1962-03-04'],
        ['Social Name', 'Maria Aparecida'],
        ['Nickname', 'Dona Cida'],
        ['Profession', 'Artesã'],
        ['Street', 'Rua das Flores'],
        ['Number', '42'],
        ['Neighborhood', 'Centro'],
        ['ZIP Code', '11010-000'],
    ])('shows the saved %s', (label, value) => {
        renderPage();

        expect(screen.getByLabelText(label)).toHaveValue(value);
    });

    /**
     * The server stores digits only. The mask is applied here, on render, which
     * is why a raw prop has to come out formatted without the user typing.
     */
    it('masks the CPF the server sent unformatted', () => {
        renderPage();

        expect(screen.getByLabelText('CPF *')).toHaveValue('529.982.247-25');
    });

    it('masks the phone the server sent unformatted', () => {
        renderPage();

        expect(screen.getByLabelText('Phone')).toHaveValue('(11) 98765-4321');
    });

    it('keeps the masked CPF and phone on submit', async () => {
        renderPage();

        await user.click(screen.getByRole('button', { name: 'Update Member' }));

        expect(submissions[0].data).toMatchObject({
            cpf: '529.982.247-25',
            phone: '(11) 98765-4321',
        });
    });
});

describe('a member with no profile row', () => {
    it.each([
        'CPF *',
        'Birth Date *',
        'Social Name',
        'Nickname',
        'Phone',
        'Profession',
        'Street',
        'Number',
        'Neighborhood',
        'ZIP Code',
    ])('leaves %s empty rather than undefined', (label) => {
        renderWithoutProfile();

        expect(screen.getByLabelText(label)).toHaveValue('');
    });

    it.each([
        ['Name *', 'Dona Maria'],
        ['Email *', 'maria@example.org'],
    ])('still shows the %s from the user record', (label, value) => {
        renderWithoutProfile();

        expect(screen.getByLabelText(label)).toHaveValue(value);
    });

    it('leaves the state unchosen', async () => {
        renderWithoutProfile();

        expect(await screen.findByText('Select state')).toBeInTheDocument();
    });

    it('reports progress while the state list loads', () => {
        const pending = deferred<Response>();

        vi.stubGlobal(
            'fetch',
            vi.fn(() => pending.promise),
        );

        renderWithoutProfile();

        expect(
            within(screen.getByRole('combobox', { name: 'State' })).getByText(
                'Loading...',
            ),
        ).toBeInTheDocument();
    });

    it('locks the city field until a state is picked', async () => {
        renderWithoutProfile();

        await screen.findByText('Select state');

        const city = screen.getByLabelText('City');

        expect(city).toBeDisabled();
        expect(city).toHaveAttribute('placeholder', 'Select a state first');
    });

    it('does not ask for cities at all', async () => {
        const fetchMock = stubBrasilApi();

        renderWithoutProfile();

        await screen.findByText('Select state');

        expect(fetchMock).toHaveBeenCalledTimes(1);
        expect(fetchMock).toHaveBeenCalledWith('/api/brasil/states');
    });
});

describe('the saved state seeds the city list', () => {
    it('loads the cities of the saved state on mount', async () => {
        const fetchMock = stubBrasilApi();

        renderPage();

        await waitFor(() =>
            expect(fetchMock).toHaveBeenCalledWith('/api/brasil/cities/SP'),
        );
    });

    it('offers the city as a dropdown straight away', async () => {
        renderPage();

        const city = await screen.findByRole('combobox', { name: 'City' });

        await user.click(city);

        expect(
            await screen.findByRole('option', { name: 'Santos' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('option', { name: 'Campinas' }),
        ).toBeInTheDocument();
    });

    /**
     * Only observable for a member whose city is still unset -- once a city is
     * selected Radix drops the placeholder, so there is nowhere to report from.
     */
    it('reports progress while those cities load', async () => {
        const pending = deferred<Response>();

        vi.stubGlobal(
            'fetch',
            vi.fn((url: string) =>
                url === '/api/brasil/states'
                    ? Promise.resolve(jsonResponse(states))
                    : pending.promise,
            ),
        );

        renderPage({ profile: { ...profile, address_city: null } });

        const city = await screen.findByRole('combobox', { name: 'City' });

        expect(within(city).getByText('Loading...')).toBeInTheDocument();

        pending.resolve(jsonResponse(citiesByState.SP));

        await waitFor(() =>
            expect(within(city).getByText('Select city')).toBeInTheDocument(),
        );
    });

    it('submits the saved state and city untouched', async () => {
        renderPage();

        await screen.findByRole('combobox', { name: 'City' });

        await user.click(screen.getByRole('button', { name: 'Update Member' }));

        expect(submissions[0].data).toMatchObject({
            address_state: 'SP',
            address_city: 'Santos',
        });
    });

    it('replaces the city list when the state changes', async () => {
        renderPage();

        await user.click(screen.getByRole('combobox', { name: 'State' }));
        await user.click(
            await screen.findByRole('option', { name: 'RJ - Rio de Janeiro' }),
        );

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
    it('sends the edits the user made', async () => {
        renderPage();

        const profession = screen.getByLabelText('Profession');

        await user.clear(profession);
        await user.type(profession, 'Parteira');

        await user.click(screen.getByRole('button', { name: 'Update Member' }));

        expect(submissions).toHaveLength(1);
        expect(submissions[0]).toMatchObject({
            action: '/members/2?community=7',
            method: 'put',
            data: {
                name: 'Dona Maria',
                email: 'maria@example.org',
                profession: 'Parteira',
            },
        });
    });

    it('blocks submission while a request is in flight', () => {
        setFormProcessing(true);

        renderPage();

        expect(
            screen.getByRole('button', { name: 'Update Member' }),
        ).toBeDisabled();
    });

    it('allows submission when idle', () => {
        renderPage();

        expect(
            screen.getByRole('button', { name: 'Update Member' }),
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
