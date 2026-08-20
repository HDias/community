import { describe, expect, it } from 'vitest';
import { formatPhone } from '@/components/phone-input';

describe('formatPhone', () => {
    it('returns an empty string for empty input', () => {
        expect(formatPhone('')).toBe('');
    });

    it('leaves the area code unpunctuated until it is complete', () => {
        expect(formatPhone('1')).toBe('1');
        expect(formatPhone('11')).toBe('11');
    });

    it('parenthesises the area code once a third digit arrives', () => {
        expect(formatPhone('113')).toBe('(11) 3');
        expect(formatPhone('113456')).toBe('(11) 3456');
    });

    it('inserts the hyphen once the subscriber exceeds four digits', () => {
        expect(formatPhone('1134567')).toBe('(11) 3456-7');
    });

    it('splits a ten-digit landline after four subscriber digits', () => {
        expect(formatPhone('1134567890')).toBe('(11) 3456-7890');
    });

    it('splits an eleven-digit mobile after five subscriber digits', () => {
        expect(formatPhone('11987654321')).toBe('(11) 98765-4321');
    });

    it('truncates input beyond eleven digits', () => {
        expect(formatPhone('11987654321999')).toBe('(11) 98765-4321');
    });

    it('discards non-digit characters', () => {
        expect(formatPhone('abc11def3456')).toBe('(11) 3456');
        expect(formatPhone('!@#')).toBe('');
    });

    it('is idempotent for already-formatted input', () => {
        expect(formatPhone('(11) 98765-4321')).toBe('(11) 98765-4321');
        expect(formatPhone('(11) 3456-7890')).toBe('(11) 3456-7890');
        expect(formatPhone(formatPhone('1134567890'))).toBe('(11) 3456-7890');
    });
});
