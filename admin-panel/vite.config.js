import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue'
import vuetify from 'vite-plugin-vuetify'
import { glob } from 'glob';
import dotenv from 'dotenv';
// Load environment variables from .env file
dotenv.config();

/**
 * Get Files from a directory
 * @param {string} query
 * @returns array
 */
function GetFilesArray(query) {
    return glob.sync(query);
}

const pageJsFiles = GetFilesArray('resources/assets/js/*.js');
const vendorJsFiles = GetFilesArray('resources/assets/vendor/js/*.js');

const LibsJsFiles = GetFilesArray('resources/assets/vendor/libs/**/*.js');

// const CoreScssFiles = GetFilesArray('resources/assets/vendor/scss/**/!(_)*.scss');

// Processing Libs Scss & Css Files
const LibsScssFiles = GetFilesArray('resources/assets/vendor/libs/**/!(_)*.scss');
const LibsScssNFiles = GetFilesArray('resources/assets/vendor/libs/**/*.scss');
const LibsCssFiles = GetFilesArray('resources/assets/vendor/libs/**/*.css');


const isHttps = process.env.VITE_HTTPS === 'true';
export default defineConfig({
    plugins: [
        vue(),
        vuetify(),
        laravel({
            input: [
                ...pageJsFiles,
                ...vendorJsFiles,
                ...LibsJsFiles,
                'resources/assets/scss/admin/app.scss',
                'resources/assets/scss/admin/app-dark.scss',
                'resources/assets/scss/admin/auth.scss',
                'resources/js/app.js',
                ...LibsScssFiles,
                ...LibsCssFiles,
                ...LibsScssNFiles
            ],
            refresh: true,
        }),

    ],
    build: {
        chunkSizeWarningLimit: 1500,
    },
    server: {
        host: '0.0.0.0',
        port: 3000,
        hmr: {
            host: '127.0.0.1',
        },
        https: isHttps, // Enable HTTPS for Vite
    },
});
