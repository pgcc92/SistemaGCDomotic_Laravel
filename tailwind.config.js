import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './node_modules/preline/dist/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['var(--gc-font)', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: 'rgb(var(--gc-primary) / <alpha-value>)',
                secondary: 'rgb(var(--gc-secondary) / <alpha-value>)',
            },
        },
    },

    plugins: [forms],
};
