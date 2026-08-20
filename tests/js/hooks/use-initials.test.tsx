import { renderHook } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { useInitials } from '@/hooks/use-initials';

function getInitials() {
    return renderHook(() => useInitials()).result.current;
}

describe('useInitials', () => {
    it('returns an empty string for blank names', () => {
        const initials = getInitials();

        expect(initials('')).toBe('');
        expect(initials('   ')).toBe('');
    });

    it('returns a single initial for a one-word name', () => {
        const initials = getInitials();

        expect(initials('Ana')).toBe('A');
    });

    it('uppercases lowercase input', () => {
        expect(getInitials()('ana silva')).toBe('AS');
    });

    it('combines the first and last initial', () => {
        expect(getInitials()('Ana Silva')).toBe('AS');
    });

    it('skips middle names', () => {
        expect(getInitials()('Ana Maria Clara Silva')).toBe('AS');
    });

    it('tolerates surrounding and repeated whitespace', () => {
        const initials = getInitials();

        expect(initials('  Ana Silva  ')).toBe('AS');
        expect(initials('Ana    Silva')).toBe('AS');
        expect(initials('Ana\tSilva')).toBe('AS');
    });

    it('keeps accented initials intact', () => {
        expect(getInitials()('Ávila Éboli')).toBe('ÁÉ');
    });

    it('takes whole code points rather than half a surrogate pair', () => {
        expect(getInitials()('𝒜na Silva')).toBe('𝒜S');
    });

    it('returns a stable callback across re-renders', () => {
        const { result, rerender } = renderHook(() => useInitials());
        const first = result.current;

        rerender();

        expect(result.current).toBe(first);
    });
});
