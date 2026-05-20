import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            colors: {
                primary: {
                    DEFAULT: '#FACC15',
                    hover: '#EAB308',
                    soft: '#FEF9C3',
                },
                neutral: {
                    light: '#F9FAFB',
                    border: '#E5E7EB',
                    text: '#111827',
                    muted: '#6B7280',
                },
                success: '#16A34A',
                danger: '#DC2626',
                info: '#2563EB',
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
