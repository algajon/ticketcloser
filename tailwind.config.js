import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Structural
                bg:      'rgb(var(--tc-bg) / <alpha-value>)',
                surface: 'rgb(var(--tc-surface) / <alpha-value>)',
                border:  'rgb(var(--tc-border) / <alpha-value>)',
                // Text
                text:    'rgb(var(--tc-text) / <alpha-value>)',
                muted:   'rgb(var(--tc-muted) / <alpha-value>)',
                // Brand
                primary: {
                    DEFAULT: 'rgb(var(--tc-primary) / <alpha-value>)',
                    hover:   'rgb(var(--tc-primary-hover) / <alpha-value>)',
                    fg:      'rgb(var(--tc-primary-fg) / <alpha-value>)',
                },
                // Semantic
                success: {
                    DEFAULT: 'rgb(var(--tc-success) / <alpha-value>)',
                    light:   'rgb(var(--tc-success-light) / <alpha-value>)',
                    fg:      'rgb(var(--tc-success-fg) / <alpha-value>)',
                },
                warning: {
                    DEFAULT: 'rgb(var(--tc-warning) / <alpha-value>)',
                    light:   'rgb(var(--tc-warning-light) / <alpha-value>)',
                    fg:      'rgb(var(--tc-warning-fg) / <alpha-value>)',
                },
                danger: {
                    DEFAULT: 'rgb(var(--tc-danger) / <alpha-value>)',
                    light:   'rgb(var(--tc-danger-light) / <alpha-value>)',
                    fg:      'rgb(var(--tc-danger-fg) / <alpha-value>)',
                },
                info: {
                    DEFAULT: 'rgb(var(--tc-info) / <alpha-value>)',
                    light:   'rgb(var(--tc-info-light) / <alpha-value>)',
                    fg:      'rgb(var(--tc-info-fg) / <alpha-value>)',
                },
            },
            borderRadius: {
                card: '1rem',   // 16px
                btn:  '0.75rem', // 12px
            },
            boxShadow: {
                card: '0 1px 3px 0 rgb(0 0 0 / .06), 0 1px 2px -1px rgb(0 0 0 / .06)',
                elevated: '0 4px 16px -2px rgb(0 0 0 / .10)',
            },
        },
    },

    plugins: [forms],
};
