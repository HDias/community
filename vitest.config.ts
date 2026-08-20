import { fileURLToPath } from 'node:url';
import react from '@vitejs/plugin-react';
import { defineConfig } from 'vitest/config';

const alias = {
    '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
};

export default defineConfig({
    test: {
        projects: [
            // Tests are split by file extension: `.test.ts` runs in the node
            // environment, `.test.tsx` gets a jsdom document. A test needing a
            // DOM therefore has to be named `.test.tsx`, even if it contains
            // no JSX itself.
            {
                plugins: [react()],
                resolve: { alias },
                test: {
                    name: 'unit',
                    globals: true,
                    environment: 'node',
                    include: ['tests/js/**/*.test.ts'],
                },
            },
            {
                plugins: [react()],
                resolve: { alias },
                test: {
                    name: 'dom',
                    globals: true,
                    environment: 'jsdom',
                    include: ['tests/js/**/*.test.tsx'],
                    setupFiles: ['tests/js/setup.ts'],
                },
            },
        ],
        coverage: {
            include: ['resources/js/**/*.{ts,tsx}'],
            exclude: [
                'resources/js/actions/**',
                'resources/js/routes/**',
                'resources/js/wayfinder/**',
                'resources/js/types/**',
                'resources/js/app.tsx',
            ],
        },
    },
});
