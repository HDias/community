import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { UserEvent } from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { SearchSelect } from '@/components/ui/search-select';
import { deferred, jsonResponse } from '../support/fetch';

const ana = { id: 1, name: 'Ana Silva', email: 'ana@example.test' };
const bruno = { id: 2, name: 'Bruno Costa' };

const DEBOUNCE_MS = 300;

let fetchMock: ReturnType<typeof vi.fn>;
let user: UserEvent;

/**
 * These tests run on real timers. Testing Library's fake-timer support only
 * detects Jest, so its async helpers hang under `vi.useFakeTimers()`; `waitFor`
 * on real timers covers the 300ms debounce instead.
 */
function renderSearchSelect(
    props: Partial<Parameters<typeof SearchSelect>[0]>,
) {
    const onChange = vi.fn();

    const view = render(
        <SearchSelect
            endpoint="/api/members/search"
            value=""
            onChange={onChange}
            {...props}
        />,
    );

    return { ...view, onChange };
}

function waitForSearchCount(times: number): Promise<void> {
    return waitFor(() => expect(fetchMock).toHaveBeenCalledTimes(times));
}

/** Waits out the debounce window to prove no request was scheduled. */
function settleDebounce(): Promise<void> {
    return new Promise((resolve) => setTimeout(resolve, DEBOUNCE_MS + 50));
}

beforeEach(() => {
    user = userEvent.setup();
    fetchMock = vi.fn().mockResolvedValue(jsonResponse([]));
    vi.stubGlobal('fetch', fetchMock);
});

afterEach(() => {
    vi.unstubAllGlobals();
});

describe('SearchSelect', () => {
    it('renders a closed combobox with the given placeholder', () => {
        renderSearchSelect({ placeholder: 'Find a member' });

        expect(screen.getByPlaceholderText('Find a member')).toHaveValue('');
        expect(screen.queryByText('No results found.')).not.toBeInTheDocument();
    });

    it('does not search on the keystroke itself', async () => {
        renderSearchSelect({});

        await user.type(screen.getByRole('textbox'), 'ana');

        expect(fetchMock).not.toHaveBeenCalled();
    });

    it('coalesces rapid keystrokes into a single request', async () => {
        renderSearchSelect({});

        await user.type(screen.getByRole('textbox'), 'ana');
        await waitForSearchCount(1);

        expect(fetchMock).toHaveBeenCalledWith(
            '/api/members/search?search=ana',
            { headers: { Accept: 'application/json' } },
        );
    });

    it('url-encodes the search term', async () => {
        renderSearchSelect({});

        await user.type(screen.getByRole('textbox'), 'ana s');
        await waitForSearchCount(1);

        expect(fetchMock).toHaveBeenCalledWith(
            '/api/members/search?search=ana%20s',
            expect.anything(),
        );
    });

    it('shows a loading row while the request is in flight', async () => {
        fetchMock.mockReturnValue(new Promise(() => {}));
        renderSearchSelect({});

        await user.type(screen.getByRole('textbox'), 'ana');

        expect(await screen.findByText('Searching...')).toBeInTheDocument();
    });

    /**
     * "No results found." is also what an untouched dropdown shows, so both of
     * these wait for the loading row first. Asserting the message directly would
     * pass before the debounce had even fired a request.
     */
    it('reports when the search returns nothing', async () => {
        const request = deferred<Response>();

        fetchMock.mockReturnValue(request.promise);
        renderSearchSelect({});

        await user.type(screen.getByRole('textbox'), 'zzz');
        expect(await screen.findByText('Searching...')).toBeInTheDocument();

        request.resolve(jsonResponse([]));

        await waitFor(() =>
            expect(screen.queryByText('Searching...')).not.toBeInTheDocument(),
        );
        expect(screen.getByText('No results found.')).toBeInTheDocument();
    });

    it('stops loading when the request fails', async () => {
        const request = deferred<Response>();

        fetchMock.mockReturnValue(request.promise);
        renderSearchSelect({});

        await user.type(screen.getByRole('textbox'), 'zzz');
        expect(await screen.findByText('Searching...')).toBeInTheDocument();

        request.reject(new Error('Network down'));

        await waitFor(() =>
            expect(screen.queryByText('Searching...')).not.toBeInTheDocument(),
        );
        expect(screen.getByText('No results found.')).toBeInTheDocument();
    });

    it('lists each result, showing the email only when present', async () => {
        fetchMock.mockResolvedValue(jsonResponse([ana, bruno]));
        renderSearchSelect({});

        await user.type(screen.getByRole('textbox'), 'a');

        expect(await screen.findByText('Ana Silva')).toBeInTheDocument();
        expect(screen.getByText('ana@example.test')).toBeInTheDocument();
        expect(screen.getByText('Bruno Costa')).toBeInTheDocument();
        expect(screen.getAllByRole('button')).toHaveLength(2);
    });

    it('reports the chosen id and closes the list on selection', async () => {
        fetchMock.mockResolvedValue(jsonResponse([ana]));
        const { onChange } = renderSearchSelect({});

        await user.type(screen.getByRole('textbox'), 'ana');
        await user.click(await screen.findByText('Ana Silva'));

        expect(onChange).toHaveBeenCalledWith('1');
        expect(screen.getByRole('textbox')).toHaveValue('Ana Silva');
        expect(screen.queryByText('ana@example.test')).not.toBeInTheDocument();
    });

    it('clears the current selection as soon as the user retypes', async () => {
        const { onChange } = renderSearchSelect({ value: '1' });

        await user.type(screen.getByRole('textbox'), 'b');

        expect(onChange).toHaveBeenCalledWith('');
    });

    it('does not report a cleared selection when nothing was selected', async () => {
        const { onChange } = renderSearchSelect({ value: '' });

        await user.type(screen.getByRole('textbox'), 'b');

        expect(onChange).not.toHaveBeenCalled();
    });

    it('empties the input when the parent clears the value', async () => {
        const { rerender, onChange } = renderSearchSelect({ value: '1' });

        await user.type(screen.getByRole('textbox'), 'ana');

        expect(screen.getByRole('textbox')).toHaveValue('ana');

        rerender(
            <SearchSelect
                endpoint="/api/members/search"
                value=""
                onChange={onChange}
            />,
        );

        expect(screen.getByRole('textbox')).toHaveValue('');
    });

    it('skips the request once the query is deleted', async () => {
        fetchMock.mockResolvedValue(jsonResponse([ana]));
        renderSearchSelect({});

        const input = screen.getByRole('textbox');

        await user.type(input, 'ana');
        expect(await screen.findByText('Ana Silva')).toBeInTheDocument();

        await user.clear(input);
        await settleDebounce();

        expect(fetchMock).toHaveBeenCalledTimes(1);
        expect(screen.queryByText('Ana Silva')).not.toBeInTheDocument();
    });

    it('abandons a queued search when unmounted mid-typing', async () => {
        const { unmount } = renderSearchSelect({});

        await user.type(screen.getByRole('textbox'), 'ana');
        unmount();
        await settleDebounce();

        expect(fetchMock).not.toHaveBeenCalled();
    });

    it('closes the list on an outside click and reopens it on focus', async () => {
        fetchMock.mockResolvedValue(jsonResponse([ana]));
        renderSearchSelect({});

        await user.type(screen.getByRole('textbox'), 'ana');
        expect(await screen.findByText('Ana Silva')).toBeInTheDocument();

        await user.click(document.body);

        expect(screen.queryByText('Ana Silva')).not.toBeInTheDocument();

        await user.click(screen.getByRole('textbox'));

        expect(screen.getByText('Ana Silva')).toBeInTheDocument();
    });
});
