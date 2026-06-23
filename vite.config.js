import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", 
                    "resources/js/app.js",
                    'resources/css/dashboard.css',
                    'resources/css/manage.css',
                    'resources/js/dashboard.js',
                    'resources/js/manage.js',],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        port: 4000,
        strictPort: true,
    },
});
