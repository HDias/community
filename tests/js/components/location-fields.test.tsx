import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { UserEvent } from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { LocationFields } from '@/components/location-fields';
import { deferred, jsonResponse } from '../support/fetch';

const states = [
    { id: 35, sigla: 'SP', nome: 'São Paulo' },
    { id: 33, sigla: 'RJ', nome: 'Rio de Janeiro' },
];

const spCities = [
    { nome: 'Santos', codigo_ibge: '3548500' },
    { nome: 'Campinas', codigo_ibge: '3509502' },
];

let fetchMock: ReturnType<typeof vi.fn>;
let user: UserEvent;

/** Routes each endpoint the component's hooks call to its own payload. */
function stubEndpoints(cities: unknown = spCities) {
    fetchMock.mockImplementation((url: string) => {
        if (url === '/api/brasil/states') {
            return Promise.resolve(jsonResponse(states));
        }

        return Promise.resolve(jsonResponse(cities));
    });
}

beforeEach(() => {
    user = userEvent.setup();
    fetchMock = vi.fn().mockResolvedValue(jsonResponse([]));
    vi.stubGlobal('fetch', fetchMock);
});

afterEach(() => {
    vi.unstubAllGlobals();
});

describe('LocationFields', () => {
    it('submits under the address, state and city names the backend expects', async () => {
        stubEndpoints();
        // Radix only renders its hidden named control inside a form, which is
        // how the component is really used (within Inertia's `Form`).
        render(
            <form>
                <LocationFields defaultState="SP" />
            </form>,
        );

        await screen.findByText('Select city');

        expect(document.querySelector('[name="address"]')).toBeInTheDocument();
        expect(document.querySelector('[name="state"]')).toBeInTheDocument();
        expect(document.querySelector('[name="city"]')).toBeInTheDocument();
    });

    it('prefills the address', () => {
        stubEndpoints();
        render(<LocationFields defaultAddress="Estrada do Quilombo, 42" />);

        expect(screen.getByLabelText('Address')).toHaveValue(
            'Estrada do Quilombo, 42',
        );
    });

    it('defaults the address to empty rather than undefined', () => {
        stubEndpoints();
        render(<LocationFields />);

        expect(screen.getByLabelText('Address')).toHaveValue('');
    });

    it('disables the city field until a state is chosen', () => {
        stubEndpoints();
        render(<LocationFields />);

        const city = screen.getByPlaceholderText('Select a state first');

        expect(city).toBeDisabled();
    });

    it('does not look up cities before a state is chosen', () => {
        stubEndpoints();
        render(<LocationFields />);

        expect(fetchMock).toHaveBeenCalledWith('/api/brasil/states');
        expect(fetchMock).toHaveBeenCalledTimes(1);
    });

    it('announces that states are loading', () => {
        fetchMock.mockReturnValue(deferred<Response>().promise);
        render(<LocationFields />);

        expect(screen.getByText('Loading...')).toBeInTheDocument();
    });

    it('prompts for a state once the list has arrived', async () => {
        stubEndpoints();
        render(<LocationFields />);

        expect(await screen.findByText('Select state')).toBeInTheDocument();
    });

    it('swaps in a real city picker when a state is preselected', async () => {
        stubEndpoints();
        render(<LocationFields defaultState="SP" />);

        expect(
            screen.queryByPlaceholderText('Select a state first'),
        ).not.toBeInTheDocument();
        expect(await screen.findByText('Select city')).toBeInTheDocument();
        expect(fetchMock).toHaveBeenCalledWith('/api/brasil/cities/SP');
    });

    it('offers every state returned by the lookup', async () => {
        stubEndpoints();
        render(<LocationFields />);

        await screen.findByText('Select state');
        await user.click(screen.getByLabelText('State'));

        expect(
            await screen.findByRole('option', { name: 'SP - São Paulo' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('option', { name: 'RJ - Rio de Janeiro' }),
        ).toBeInTheDocument();
    });

    it('looks up cities for the state the user picks', async () => {
        stubEndpoints();
        render(<LocationFields />);

        await screen.findByText('Select state');
        await user.click(screen.getByLabelText('State'));
        await user.click(
            await screen.findByRole('option', { name: 'RJ - Rio de Janeiro' }),
        );

        await waitFor(() =>
            expect(fetchMock).toHaveBeenCalledWith('/api/brasil/cities/RJ'),
        );
        expect(
            screen.queryByPlaceholderText('Select a state first'),
        ).not.toBeInTheDocument();
    });

    it('offers every city returned for the chosen state', async () => {
        stubEndpoints();
        render(<LocationFields defaultState="SP" />);

        await screen.findByText('Select city');
        await user.click(screen.getByLabelText('City'));

        expect(
            await screen.findByRole('option', { name: 'Santos' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('option', { name: 'Campinas' }),
        ).toBeInTheDocument();
    });
});
