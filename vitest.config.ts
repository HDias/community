import { fileURLToPath } from 'node:url';
import react from '@vitejs/plugin-react';
import { defineConfig } from 'vitest/config';

const alias = {
    '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
};

export default defineConfig({
    test: {
        projects: [
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
