import type { Config } from 'tailwindcss'

export default {
    content: [
        './app/**/*.{vue,js,ts}',
        './components/**/*.{vue,js,ts}',
        './layouts/**/*.vue',
        './pages/**/*.vue',
        './plugins/**/*.{js,ts}',
    ],
    theme: {
        extend: {
            colors: {
                // Brand Colors
                primary: {
                    DEFAULT: '#05624e',
                    50: '#e6f5f2',
                    100: '#b3e0d7',
                    200: '#80cbbc',
                    300: '#4db6a1',
                    400: '#26a68b',
                    500: '#05624e',
                    600: '#045645',
                    700: '#034a3b',
                    800: '#023e32',
                    900: '#012e25',
                },
                secondary: {
                    DEFAULT: '#3ac35e',
                    50: '#e8f9ec',
                    100: '#c5efce',
                    200: '#9fe5af',
                    300: '#79db90',
                    400: '#5cd378',
                    500: '#3ac35e',
                    600: '#32b053',
                    700: '#299a47',
                    800: '#21843c',
                    900: '#14612b',
                },
                // Neutral/Gray scale
                gray: {
                    50: '#f9fafb',
                    100: '#f3f4f6',
                    200: '#e5e7eb',
                    300: '#d1d5db',
                    400: '#9ca3af',
                    500: '#6b7280',
                    600: '#4b5563',
                    700: '#374151',
                    800: '#1f2937',
                    900: '#111827',
                },
                // Status colors
                success: '#10b981',
                warning: '#f59e0b',
                error: '#ef4444',
                info: '#3b82f6',
            },
            fontFamily: {
                sans: ['Kontumruy Pro', 'Inter', 'system-ui', 'sans-serif'],
            },
            boxShadow: {
                'soft': '0 2px 15px -3px rgba(0, 0, 0, 0.07), 0 10px 20px -2px rgba(0, 0, 0, 0.04)',
                'card': '0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1)',
            },
            borderRadius: {
                'xl': '1rem',
                '2xl': '1.5rem',
            },
        },
    },
    plugins: [],
} satisfies Config
