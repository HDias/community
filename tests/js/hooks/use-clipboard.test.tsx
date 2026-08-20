import { act, renderHook } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { useClipboard } from '@/hooks/use-clipboard';

/**
 * jsdom ships no clipboard, so the property is defined per test and removed
 * afterwards to keep the `!navigator?.clipboard` branch reachable.
 */
function stubClipboard(writeText: (text: string) => Promise<void>): void {
    Object.defineProperty(navigator, 'clipboard', {
        value: { writeText },
        configurable: true,
    });
}

beforeEach(() => {
    vi.spyOn(console, 'warn').mockImplementation(() => {});
});

afterEach(() => {
    Reflect.deleteProperty(navigator, 'clipboard');
    vi.restoreAllMocks();
});

describe('useClipboard', () => {
    it('starts with nothing copied', () => {
        const { result } = renderHook(() => useClipboard());

        expect(result.current[0]).toBeNull();
    });

    it('writes to the clipboard and records the copied text', async () => {
        const writeText = vi.fn().mockResolvedValue(undefined);
        stubClipboard(writeText);

        const { result } = renderHook(() => useClipboard());

        let copied: boolean | undefined;
        await act(async () => {
            copied = await result.current[1]('a-secret');
        });

        expect(copied).toBe(true);
        expect(writeText).toHaveBeenCalledWith('a-secret');
        expect(result.current[0]).toBe('a-secret');
    });

    it('reports failure and warns when the clipboard is unsupported', async () => {
        const { result } = renderHook(() => useClipboard());

        let copied: boolean | undefined;
        await act(async () => {
            copied = await result.current[1]('a-secret');
        });

        expect(copied).toBe(false);
        expect(result.current[0]).toBeNull();
        expect(console.warn).toHaveBeenCalledWith('Clipboard not supported');
    });

    it('reports failure and warns when the write is rejected', async () => {
        const error = new Error('permission denied');
        stubClipboard(vi.fn().mockRejectedValue(error));

        const { result } = renderHook(() => useClipboard());

        let copied: boolean | undefined;
        await act(async () => {
            copied = await result.current[1]('a-secret');
        });

        expect(copied).toBe(false);
        expect(result.current[0]).toBeNull();
        expect(console.warn).toHaveBeenCalledWith('Copy failed', error);
    });

    it('clears the copied text when a later write fails', async () => {
        const writeText = vi
            .fn()
            .mockResolvedValueOnce(undefined)
            .mockRejectedValueOnce(new Error('permission denied'));
        stubClipboard(writeText);

        const { result } = renderHook(() => useClipboard());

        await act(async () => {
            await result.current[1]('first');
        });

        expect(result.current[0]).toBe('first');

        await act(async () => {
            await result.current[1]('second');
        });

        expect(result.current[0]).toBeNull();
    });
});
