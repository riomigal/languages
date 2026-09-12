import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    publicDir: false,
    plugins: [tailwindcss()],
    build: {
        outDir: 'public',
        emptyOutDir: false,
        rollupOptions: {
            input: 'resources/js/app.js',
            output: {
                entryFileNames: 'js/app.js',
                chunkFileNames: 'js/[name].js',
                assetFileNames: ({ name }) => {
                    if (name && name.endsWith('.css')) {
                        return 'css/[name][extname]';
                    }

                    return 'assets/[name][extname]';
                },
            },
        },
    },
});
