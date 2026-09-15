import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // "Campus Trust" palette
                primary: {
                    DEFAULT: '#123B5D', // Campus Navy — brand, headers, navigation
                    dark: '#0C2A42',
                    light: '#1B5480',
                },
                secondary: {
                    DEFAULT: '#0F8B8D', // Teal — actions, icons, highlights
                    dark: '#0B6B6C',
                },
                success: {
                    DEFAULT: '#2E7D32', // Recovery Green — recovered/returned items
                    dark: '#1B5E20',
                },
                accent: {
                    DEFAULT: '#F9A825', // Amber — found/pending/attention
                    dark: '#F57F17',
                },
                danger: {
                    DEFAULT: '#D32F2F', // Crimson — urgent reports / warnings
                    dark: '#B71C1C',
                },
                charcoal: '#263238', // main text
                muted: '#607D8B',    // slate — secondary text
                softgray: '#F4F7F9', // app background
            },
        },
    },

    plugins: [forms],
};
