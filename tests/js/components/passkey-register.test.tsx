import { act, fireEvent, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { UserEvent } from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import PasskeyRegistration from '@/components/passkey-register';

/**
 * The component reads everything it needs about passkey support from the
 * `usePasskeyRegister` hook, so the double exposes a mutable state object that
 * each test sets up before rendering, plus the `onSuccess` callback the
 * component hands over so tests can fire it the way a real registration would.
 */
const passkeys = vi.hoisted(() => ({
    state: {
        register: vi.fn(),
        isLoading: false,
        error: null as string | null,
        errorInstance: null,
        isSupported: true,
    },
    captured: {} as { onSuccess?: () => void },
}));

vi.mock('@laravel/passkeys/react', () => ({
    usePasskeyRegister: (options?: { onSuccess?: () => void }) => {
        passkeys.captured.onSuccess = options?.onSuccess;

        return passkeys.state;
    },
}));

const originalUserAgent = navigator.userAgent;

let user: UserEvent;

function setUserAgent(value: string): void {
    Object.defineProperty(navigator, 'userAgent', {
        value,
        configurable: true,
    });
}

beforeEach(() => {
    user = userEvent.setup();
    passkeys.state.register = vi.fn();
    passkeys.state.isLoading = false;
    passkeys.state.error = null;
    passkeys.state.isSupported = true;
    passkeys.captured.onSuccess = undefined;
});

afterEach(() => {
    setUserAgent(originalUserAgent);
});

/**
 * Opens the name form, which is hidden behind the "Add passkey" button.
 */
async function openForm() {
    const result = render(<PasskeyRegistration onSuccess={vi.fn()} />);

    await user.click(screen.getByRole('button', { name: 'Add passkey' }));

    return result;
}

describe('support', () => {
    it('explains itself instead of rendering a button when unsupported', () => {
        passkeys.state.isSupported = false;

        render(<PasskeyRegistration onSuccess={vi.fn()} />);

        expect(
            screen.getByText('Passkeys are not supported in this browser.'),
        ).toBeInTheDocument();
        expect(screen.queryByRole('button')).not.toBeInTheDocument();
    });

    it('offers the button when supported', () => {
        render(<PasskeyRegistration onSuccess={vi.fn()} />);

        expect(
            screen.getByRole('button', { name: 'Add passkey' }),
        ).toBeInTheDocument();
        expect(screen.queryByRole('textbox')).not.toBeInTheDocument();
    });
});

describe('suggested name', () => {
    /**
     * The browser and OS lists are ordered so that the more specific user agent
     * wins: every Edge and Opera agent also claims Chrome, every Chrome agent
     * also claims Safari, and every iPhone agent also mentions Mac. These cases
     * pin that ordering.
     */
    const agents = [
        {
            label: 'Edge over the Chrome and Safari it also claims',
            ua: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0',
            expected: 'Edge on Windows',
        },
        {
            label: 'Opera over the Chrome and Safari it also claims',
            ua: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 OPR/106.0.0.0',
            expected: 'Opera on Mac',
        },
        {
            label: 'Firefox on the desktop',
            ua: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:121.0) Gecko/20100101 Firefox/121.0',
            expected: 'Firefox on Mac',
        },
        {
            label: 'Firefox on iOS over the Safari it also claims',
            ua: 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) FxiOS/121.0 Mobile/15E148 Safari/605.1.15',
            expected: 'Firefox on iPhone',
        },
        {
            label: 'Chrome over the Safari it also claims',
            ua: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            expected: 'Chrome on Windows',
        },
        {
            label: 'Chrome on iOS over the Safari it also claims',
            ua: 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/120.0.0.0 Mobile/15E148 Safari/604.1',
            expected: 'Chrome on iPhone',
        },
        {
            label: 'Safari on the desktop',
            ua: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Safari/605.1.15',
            expected: 'Safari on Mac',
        },
        {
            label: 'iPhone over the Mac its agent also mentions',
            ua: 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Mobile/15E148 Safari/604.1',
            expected: 'Safari on iPhone',
        },
        {
            label: 'a legacy iPad agent',
            ua: 'Mozilla/5.0 (iPad; CPU OS 17_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Mobile/15E148 Safari/604.1',
            expected: 'Safari on iPad',
        },
        {
            label: 'an iPad masquerading as a Macintosh but still mobile',
            ua: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Mobile/15E148 Safari/604.1',
            expected: 'Safari on iPad',
        },
        {
            label: 'Android',
            ua: 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
            expected: 'Chrome on Android',
        },
        {
            label: 'a browser with no recognisable OS',
            ua: 'Firefox/121.0',
            expected: 'Firefox',
        },
        {
            label: 'an OS with no recognisable browser',
            ua: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            expected: 'Windows',
        },
    ];

    it.each(agents)(
        'suggests $expected for $label',
        async ({ ua, expected }) => {
            setUserAgent(ua);

            await openForm();

            expect(screen.getByLabelText('Passkey name')).toHaveValue(expected);
        },
    );

    it('suggests nothing when it recognises neither browser nor OS', async () => {
        setUserAgent('Unrecognisable/1.0');

        await openForm();

        expect(screen.getByLabelText('Passkey name')).toHaveValue('');
    });
});

describe('registering', () => {
    it('registers under the suggested name', async () => {
        setUserAgent(
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:121.0) Gecko/20100101 Firefox/121.0',
        );

        await openForm();
        await user.click(
            screen.getByRole('button', { name: 'Register passkey' }),
        );

        expect(passkeys.state.register).toHaveBeenCalledExactlyOnceWith(
            'Firefox on Mac',
        );
    });

    it('registers under a name the user typed instead', async () => {
        await openForm();

        await user.type(screen.getByLabelText('Passkey name'), 'Work laptop');
        await user.click(
            screen.getByRole('button', { name: 'Register passkey' }),
        );

        expect(passkeys.state.register).toHaveBeenCalledExactlyOnceWith(
            'Work laptop',
        );
    });

    it('cannot be submitted while the name is empty', async () => {
        await openForm();

        expect(
            screen.getByRole('button', { name: 'Register passkey' }),
        ).toBeDisabled();
    });

    it('cannot be submitted while the name is only whitespace', async () => {
        await openForm();

        await user.type(screen.getByLabelText('Passkey name'), '   ');

        expect(
            screen.getByRole('button', { name: 'Register passkey' }),
        ).toBeDisabled();
    });

    it('refuses to register a blank name even if the form is submitted', async () => {
        const { container } = await openForm();

        fireEvent.submit(container.querySelector('form')!);

        expect(passkeys.state.register).not.toHaveBeenCalled();
    });

    it('refuses to register a whitespace name even if the form is submitted', async () => {
        const { container } = await openForm();

        await user.type(screen.getByLabelText('Passkey name'), '   ');
        fireEvent.submit(container.querySelector('form')!);

        expect(passkeys.state.register).not.toHaveBeenCalled();
    });

    it('reports progress and blocks a second submission while registering', async () => {
        passkeys.state.isLoading = true;

        await openForm();

        expect(
            screen.getByRole('button', { name: 'Registering...' }),
        ).toBeDisabled();
    });

    it('shows the error the hook reports', async () => {
        passkeys.state.error = 'That passkey already exists.';

        await openForm();

        expect(
            screen.getByText('That passkey already exists.'),
        ).toBeInTheDocument();
    });

    it('closes the form and notifies the parent once registration succeeds', async () => {
        const onSuccess = vi.fn();

        render(<PasskeyRegistration onSuccess={onSuccess} />);
        await user.click(screen.getByRole('button', { name: 'Add passkey' }));

        act(() => passkeys.captured.onSuccess?.());

        expect(onSuccess).toHaveBeenCalledOnce();
        expect(
            screen.getByRole('button', { name: 'Add passkey' }),
        ).toBeInTheDocument();
    });

    it('forgets the typed name after a successful registration', async () => {
        render(<PasskeyRegistration onSuccess={vi.fn()} />);
        await user.click(screen.getByRole('button', { name: 'Add passkey' }));
        await user.type(screen.getByLabelText('Passkey name'), 'Work laptop');

        act(() => passkeys.captured.onSuccess?.());
        await user.click(screen.getByRole('button', { name: 'Add passkey' }));

        expect(screen.getByLabelText('Passkey name')).toHaveValue('');
    });
});

describe('cancelling', () => {
    it('closes the form without registering', async () => {
        await openForm();

        await user.click(screen.getByRole('button', { name: 'Cancel' }));

        expect(passkeys.state.register).not.toHaveBeenCalled();
        expect(
            screen.getByRole('button', { name: 'Add passkey' }),
        ).toBeInTheDocument();
        expect(screen.queryByRole('textbox')).not.toBeInTheDocument();
    });

    it('forgets the typed name', async () => {
        await openForm();

        await user.type(screen.getByLabelText('Passkey name'), 'Work laptop');
        await user.click(screen.getByRole('button', { name: 'Cancel' }));
        await user.click(screen.getByRole('button', { name: 'Add passkey' }));

        expect(screen.getByLabelText('Passkey name')).toHaveValue('');
    });
});
