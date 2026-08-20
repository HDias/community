import { act, renderHook, waitFor } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { useCities, useStates } from '@/hooks/use-brasil-api';

type Deferred<T> = {
    promise: Promise<T>;
    resolve: (value: T) => void;
    reject: (reason?: unknown) => void;
};

function deferred<T>(): Deferred<T> {
    let resolve!: (value: T) => void;
    let reject!: (reason?: unknown) => void;

    const promise = new Promise<T>((res, rej) => {
        resolve = res;
        reject = rej;
    });

    return { promise, resolve, reject };
}

function jsonResponse(data: unknown): Response {
    return { json: () => Promise.resolve(data) } as unknown as Response;
}

/** Drains the microtask queue so chained `.then()` handlers all run. */
function flush(): Promise<void> {
    return new Promise((resolve) => setTimeout(resolve, 0));
}

const saoPaulo = { id: 35, sigla: 'SP', nome: 'São Paulo' };
const santos = { nome: 'Santos', codigo_ibge: '3548500' };
const niteroi = { nome: 'Niterói', codigo_ibge: '3303302' };

let fetchMock: ReturnType<typeof vi.fn>;

beforeEach(() => {
    fetchMock = vi.fn();
    vi.stubGlobal('fetch', fetchMock);
});

afterEach(() => {
    vi.unstubAllGlobals();
});

describe('useStates', () => {
    it('starts out loading with no states', () => {
        fetchMock.mockReturnValue(deferred<Response>().promise);

        const { result } = renderHook(() => useStates());

        expect(result.current.loading).toBe(true);
        expect(result.current.states).toEqual([]);
    });

    it('requests the states endpoint and exposes the result', async () => {
        fetchMock.mockResolvedValue(jsonResponse([saoPaulo]));

        const { result } = renderHook(() => useStates());

        await waitFor(() => expect(result.current.loading).toBe(false));

        expect(fetchMock).toHaveBeenCalledWith('/api/brasil/states');
        expect(result.current.states).toEqual([saoPaulo]);
    });

    it('falls back to an empty list when the request fails', async () => {
        fetchMock.mockRejectedValue(new Error('network unreachable'));

        const { result } = renderHook(() => useStates());

        await waitFor(() => expect(result.current.loading).toBe(false));

        expect(result.current.states).toEqual([]);
    });

    it('fetches only once across re-renders', async () => {
        fetchMock.mockResolvedValue(jsonResponse([saoPaulo]));

        const { result, rerender } = renderHook(() => useStates());

        await waitFor(() => expect(result.current.loading).toBe(false));
        rerender();

        expect(fetchMock).toHaveBeenCalledTimes(1);
    });
});

describe('useCities', () => {
    it('does not fetch until a state is selected', () => {
        const { result } = renderHook(() => useCities(''));

        expect(fetchMock).not.toHaveBeenCalled();
        expect(result.current.cities).toEqual([]);
        expect(result.current.loading).toBe(false);
    });

    it('requests the cities of the selected state', async () => {
        fetchMock.mockResolvedValue(jsonResponse([santos]));

        const { result } = renderHook(() => useCities('SP'));

        expect(result.current.loading).toBe(true);
        await waitFor(() => expect(result.current.loading).toBe(false));

        expect(fetchMock).toHaveBeenCalledWith('/api/brasil/cities/SP');
        expect(result.current.cities).toEqual([santos]);
    });

    it('falls back to an empty list when the request fails', async () => {
        fetchMock.mockRejectedValue(new Error('network unreachable'));

        const { result } = renderHook(() => useCities('SP'));

        await waitFor(() => expect(result.current.loading).toBe(false));

        expect(result.current.cities).toEqual([]);
    });

    it('reports no cities once the state is cleared', async () => {
        fetchMock.mockResolvedValue(jsonResponse([santos]));

        const { result, rerender } = renderHook(({ uf }) => useCities(uf), {
            initialProps: { uf: 'SP' },
        });

        await waitFor(() => expect(result.current.cities).toEqual([santos]));
        rerender({ uf: '' });

        expect(result.current.cities).toEqual([]);
    });

    it('ignores a resolved response that the state has already outrun', async () => {
        const first = deferred<Response>();
        const second = deferred<Response>();

        fetchMock
            .mockImplementationOnce(() => first.promise)
            .mockImplementationOnce(() => second.promise);

        const { result, rerender } = renderHook(({ uf }) => useCities(uf), {
            initialProps: { uf: 'SP' },
        });

        rerender({ uf: 'RJ' });

        await act(async () => {
            second.resolve(jsonResponse([niteroi]));
            await flush();
        });

        expect(result.current.cities).toEqual([niteroi]);

        await act(async () => {
            first.resolve(jsonResponse([santos]));
            await flush();
        });

        expect(result.current.cities).toEqual([niteroi]);
        expect(result.current.loading).toBe(false);
    });

    it('ignores a failed response that the state has already outrun', async () => {
        const first = deferred<Response>();
        const second = deferred<Response>();

        fetchMock
            .mockImplementationOnce(() => first.promise)
            .mockImplementationOnce(() => second.promise);

        const { result, rerender } = renderHook(({ uf }) => useCities(uf), {
            initialProps: { uf: 'SP' },
        });

        rerender({ uf: 'RJ' });

        await act(async () => {
            second.resolve(jsonResponse([niteroi]));
            await flush();
        });

        await act(async () => {
            first.reject(new Error('network unreachable'));
            await flush();
        });

        expect(result.current.cities).toEqual([niteroi]);
        expect(result.current.loading).toBe(false);
    });
});
