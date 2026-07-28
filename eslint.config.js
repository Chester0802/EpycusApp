import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';
import globals from 'globals';
import prettier from 'eslint-config-prettier';

export default [
    {
        ignores: ['public/build/**', 'node_modules/**'],
    },
    js.configs.recommended,
    ...pluginVue.configs['flat/recommended'],
    {
        files: ['resources/js/**/*.{js,vue}'],
        languageOptions: {
            globals: {
                ...globals.browser,
                // Ziggy inyecta route() como global del lado del cliente.
                route: 'readonly',
            },
        },
        rules: {
            'vue/multi-word-component-names': 'off',
        },
    },
    prettier,
];
