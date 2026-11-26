import { defineConfig } from 'vite';
import Symfony from '@symfony/reprise/vite';

export default defineConfig(({ mode }) => ({
    input: {
        dashboard: './assets/dashboard.ts',
    },
    plugins: [Symfony({ stimulus: 'assets/controllers.json' })],
    build: {
        emptyOutDir: true,
        sourcemap: mode !== 'production',
    },
}));
