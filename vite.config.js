import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        // Wajib terdaftar agar Tailwind v4 memindai view & menghasilkan
        // utility class — tanpa ini CSS hanya berisi reset dasar
        // (temuan UAT; lihat build_logs/fase9.log)
        tailwindcss(),
    ],
});
