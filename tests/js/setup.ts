import '@testing-library/jest-dom/vitest';

/**
 * Radix primitives rely on DOM APIs that jsdom does not implement. Without
 * these, opening any `Select` throws instead of failing an assertion.
 */
if (!Element.prototype.scrollIntoView) {
    Element.prototype.scrollIntoView = function scrollIntoView(): void {};
}

if (!Element.prototype.hasPointerCapture) {
    Element.prototype.hasPointerCapture =
        function hasPointerCapture(): boolean {
            return false;
        };
    Element.prototype.setPointerCapture = function setPointerCapture(): void {};
    Element.prototype.releasePointerCapture =
        function releasePointerCapture(): void {};
}

if (!globalThis.ResizeObserver) {
    globalThis.ResizeObserver = class ResizeObserver {
        observe(): void {}
        unobserve(): void {}
        disconnect(): void {}
    };
}
