import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/**
 * Colores, tipografía y radios en docs/04-DISENO-VISUAL.md y la skill
 * epycus-ui. No agregues un color aquí que no sea `var(--color-*)`: la
 * regla del proyecto es tokens semánticos, nunca literales — ver
 * docs/04-DISENO-VISUAL.md §4.
 *
 * @type {import('tailwindcss').Config}
 */
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
                sans: ['Nunito', ...defaultTheme.fontFamily.sans],
                display: ['Quicksand', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                bg: 'var(--color-bg)',
                backdrop: 'var(--color-backdrop)',
                surface: {
                    DEFAULT: 'var(--color-surface)',
                    raised: 'var(--color-surface-raised)',
                    sunken: 'var(--color-surface-sunken)',
                },
                content: {
                    primary: 'var(--color-content-primary)',
                    secondary: 'var(--color-content-secondary)',
                    muted: 'var(--color-content-muted)',
                },
                primary: {
                    DEFAULT: 'var(--color-primary)',
                    strong: 'var(--color-primary-strong)',
                },
                'on-primary': 'var(--color-on-primary)',
                'on-primary-strong': 'var(--color-on-primary-strong)',
                'on-accent': 'var(--color-on-accent)',
                secondary: 'var(--color-secondary)',
                accent: 'var(--color-accent)',
                success: 'var(--color-success)',
                warning: 'var(--color-warning)',
                danger: {
                    DEFAULT: 'var(--color-danger)',
                    text: 'var(--color-danger-text)',
                },
                border: {
                    DEFAULT: 'var(--color-border)',
                    strong: 'var(--color-border-strong)',
                    interactive: 'var(--color-border-interactive)',
                },
            },
            borderRadius: {
                sm: '8px',
                DEFAULT: '12px',
                lg: '16px',
                xl: '24px',
                full: '9999px',
            },
        },
    },

    plugins: [forms],
};
