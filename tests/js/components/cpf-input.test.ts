import { describe, expect, it } from 'vitest';
import { formatCpf } from '@/components/cpf-input';

describe('formatCpf', () => {
    it('returns an empty string for empty input', () => {
        expect(formatCpf('')).toBe('');
    });

    it('leaves the first three digits unpunctuated', () => {
        expect(formatCpf('1')).toBe('1');
        expect(formatCpf('12')).toBe('12');
        expect(formatCpf('123')).toBe('123');
    });

    it('inserts the first dot once a fourth digit arrives', () => {
        expect(formatCpf('1234')).toBe('123.4');
        expect(formatCpf('123456')).toBe('123.456');
    });

    it('inserts the second dot once a seventh digit arrives', () => {
        expect(formatCpf('1234567')).toBe('123.456.7');
        expect(formatCpf('123456789')).toBe('123.456.789');
    });

    it('inserts the hyphen once a tenth digit arrives', () => {
        expect(formatCpf('1234567890')).toBe('123.456.789-0');
        expect(formatCpf('12345678901')).toBe('123.456.789-01');
    });

    it('truncates input beyond eleven digits', () => {
        expect(formatCpf('123456789012345')).toBe('123.456.789-01');
    });

    it('discards non-digit characters', () => {
        expect(formatCpf('abc123def456')).toBe('123.456');
        expect(formatCpf('!@#')).toBe('');
    });

    it('is idempotent for already-formatted input', () => {
        expect(formatCpf('123.456.789-01')).toBe('123.456.789-01');
        expect(formatCpf(formatCpf('12345678901'))).toBe('123.456.789-01');
    });
});
