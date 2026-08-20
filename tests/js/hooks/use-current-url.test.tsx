import { renderHook } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { resetInertia, setPage } from '../support/inertia';

vi.mock('@inertiajs/react', async () => {
    const { createInertiaMock } = await import('../support/inertia');

    return createInertiaMock();
});

function atUrl(url: string) {
    setPage({ url });

    return renderHook(() => useCurrentUrl()).result.current;
}

beforeEach(() => {
    resetInertia();
});

afterEach(() => {
    resetInertia();
});

describe('useCurrentUrl', () => {
    it('exposes the path of the current page', () => {
        expect(atUrl('/members').currentUrl).toBe('/members');
    });

    it('strips the query string from the current path', () => {
        expect(atUrl('/members?community=3').currentUrl).toBe('/members');
    });

    describe('isCurrentUrl', () => {
        it('matches the current path exactly', () => {
            const { isCurrentUrl } = atUrl('/members');

            expect(isCurrentUrl('/members')).toBe(true);
            expect(isCurrentUrl('/communities')).toBe(false);
        });

        it('does not treat a child path as the current one', () => {
            const { isCurrentUrl } = atUrl('/members/3/edit');

            expect(isCurrentUrl('/members')).toBe(false);
        });

        it('compares only the path of an absolute url', () => {
            const { isCurrentUrl } = atUrl('/members');

            expect(isCurrentUrl('https://example.test/members')).toBe(true);
            expect(isCurrentUrl('https://example.test/communities')).toBe(
                false,
            );
        });

        it('rejects a malformed absolute url instead of throwing', () => {
            const { isCurrentUrl } = atUrl('/members');

            expect(isCurrentUrl('https://')).toBe(false);
        });

        it('accepts an action object as the url to check', () => {
            const { isCurrentUrl } = atUrl('/members');

            expect(isCurrentUrl({ url: '/members', method: 'get' })).toBe(true);
        });

        it('honours an explicitly supplied current url', () => {
            const { isCurrentUrl } = atUrl('/members');

            expect(isCurrentUrl('/communities', '/communities')).toBe(true);
            expect(isCurrentUrl('/members', '/communities')).toBe(false);
        });

        it('matches by prefix when asked to', () => {
            const { isCurrentUrl } = atUrl('/members/3/edit');

            expect(isCurrentUrl('/members', undefined, true)).toBe(true);
            expect(isCurrentUrl('/communities', undefined, true)).toBe(false);
        });
    });

    describe('isCurrentOrParentUrl', () => {
        it('matches the current path and its descendants', () => {
            const { isCurrentOrParentUrl } = atUrl('/members/3/edit');

            expect(isCurrentOrParentUrl('/members')).toBe(true);
            expect(isCurrentOrParentUrl('/members/3')).toBe(true);
            expect(isCurrentOrParentUrl('/communities')).toBe(false);
        });

        it('matches the path itself', () => {
            const { isCurrentOrParentUrl } = atUrl('/members');

            expect(isCurrentOrParentUrl('/members')).toBe(true);
        });
    });

    describe('whenCurrentUrl', () => {
        it('returns the first value on a match', () => {
            const { whenCurrentUrl } = atUrl('/members');

            expect(whenCurrentUrl('/members', 'active')).toBe('active');
        });

        it('returns null by default when there is no match', () => {
            const { whenCurrentUrl } = atUrl('/members');

            expect(whenCurrentUrl('/communities', 'active')).toBeNull();
        });

        it('returns the supplied fallback when there is no match', () => {
            const { whenCurrentUrl } = atUrl('/members');

            expect(whenCurrentUrl('/communities', 'active', 'idle')).toBe(
                'idle',
            );
        });
    });
});
