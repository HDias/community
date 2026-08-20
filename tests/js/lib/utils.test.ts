import { describe, expect, it } from 'vitest';
import { cn, toUrl } from '@/lib/utils';

describe('cn', () => {
    it('returns an empty string when given no arguments', () => {
        expect(cn()).toBe('');
    });

    it('joins plain class names', () => {
        expect(cn('flex', 'items-center')).toBe('flex items-center');
    });

    it('drops falsy values', () => {
        expect(cn('flex', false, null, undefined, '')).toBe('flex');
    });

    it('accepts array and object syntax', () => {
        expect(cn(['flex', 'p-2'])).toBe('flex p-2');
        expect(cn({ flex: true, hidden: false })).toBe('flex');
    });

    it('lets the last conflicting Tailwind utility win', () => {
        expect(cn('p-2', 'p-4')).toBe('p-4');
        expect(cn('text-sm text-red-500', 'text-lg')).toBe(
            'text-red-500 text-lg',
        );
    });
});

describe('toUrl', () => {
    it('passes a string href through unchanged', () => {
        expect(toUrl('/members')).toBe('/members');
    });

    it('unwraps the url of an action object', () => {
        expect(toUrl({ url: '/members', method: 'get' })).toBe('/members');
    });
});
