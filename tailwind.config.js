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
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['Archivo', ...defaultTheme.fontFamily.sans],
                mono: ['"IBM Plex Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                // "kertas & tinta" — palet utama, hangat dan tidak generik
                paper: '#F4F2EC',
                surface: '#FFFFFF',
                ink: {
                    DEFAULT: '#1B1815',
                    soft: '#57534E',
                    faint: '#8A857C',
                },
                line: '#E4E0D6',
                // aksen ungu "spot ink" (sesuai PRD, tanpa gradien generik)
                accent: {
                    DEFAULT: '#5A45C7',
                    dark: '#4836A6',
                    deep: '#34287A',
                    soft: '#EFEBFB',
                    line: '#D9D2F2',
                },
                // warna dukung CMYK-ish
                cmyk: {
                    cyan: '#2E9BD6',
                    magenta: '#E0448E',
                    yellow: '#F2B705',
                },
                ok: '#1A7F4E',
                warn: '#C96A0E',
                bad: '#B42318',
            },
            boxShadow: {
                card: '0 1px 2px rgba(27, 24, 21, 0.05)',
                lift: '0 4px 14px rgba(27, 24, 21, 0.09)',
            },
            borderRadius: {
                card: '12px',
            },
        },
    },

    plugins: [forms],
};
