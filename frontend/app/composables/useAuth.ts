import type { User } from '~/types/models'

export function useAuth() {
    const user = useState<User | null>('auth-user', () => null)
    // Use useCookie instead of localStorage for SSR compatibility
    const tokenCookie = useCookie<string | null>('auth-token', {
        maxAge: 60 * 60 * 24 * 7, // 7 days
        sameSite: 'lax',
    })
    const token = useState<string | null>('auth-token', () => tokenCookie.value || null)

    const isAuthenticated = computed(() => !!token.value && !!user.value)

    const config = useRuntimeConfig()
    const baseURL = (import.meta.server ? config.apiBase : config.public.apiBase) as string
    const apiKey = config.public.apiKey as string | undefined

    // Get headers for API calls
    function getHeaders(): Record<string, string> {
        const headers: Record<string, string> = {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        }
        if (apiKey && typeof apiKey === 'string') {
            headers['X-API-Key'] = apiKey
        }
        if (token.value) {
            headers['Authorization'] = `Bearer ${token.value}`
        }
        return headers
    }

    // Login
    async function login(email: string, password: string) {
        const response = await $fetch<{ user: User; token: string }>('auth/login', {
            baseURL,
            method: 'POST',
            headers: getHeaders(),
            body: { email, password },
        })

        user.value = response.user
        token.value = response.token
        tokenCookie.value = response.token

        return response
    }

    // Register
    async function register(name: string, email: string, password: string, password_confirmation: string) {
        const response = await $fetch<any>('auth/register', {
            baseURL,
            method: 'POST',
            body: { name, email, password, password_confirmation },
            headers: getHeaders(), // Use getHeaders to include API Key
        })

        if (response?.token) {
            token.value = response.token
            tokenCookie.value = response.token
            if (response?.user) {
                user.value = response.user
            }
        }

        return response
    }

    // Verify Email
    async function verifyEmail(email: string, code: string) {
        const response = await $fetch<{ user: User; token: string; message: string }>('auth/verify-email', {
            baseURL,
            method: 'POST',
            body: { email, code },
            headers: getHeaders(),
        })

        if (response?.token) {
            token.value = response.token
            tokenCookie.value = response.token
            if (response?.user) {
                user.value = response.user
            }
        }

        return response
    }

    // Logout
    async function logout() {
        try {
            await $fetch('auth/logout', {
                baseURL,
                method: 'POST',
                headers: getHeaders(),
            })
        } catch (e) {
            // Ignore errors
        }

        user.value = null
        token.value = null
        tokenCookie.value = null

        // Reset all stores to clear cached data
        // We call them here; Nuxt 3 auto-imports stores from the stores/ directory
        try {
            useAuthStore().$reset()
            useAdminStore().$reset()
            useExecutionStore().$reset()
            useInventoryStore().$reset()
            useMaintenanceStore().$reset()
            useMasterStore().$reset()
            useReportingStore().$reset()
        } catch (e) {
            console.error('Error resetting stores:', e)
        }
    }

    // Check auth status (restore from cookie)
    async function checkAuth() {
        // Restore token from cookie if not set
        if (!token.value && tokenCookie.value) {
            token.value = tokenCookie.value
        }

        if (token.value && !user.value) {
            try {
                const response = await $fetch<User>('auth/user', {
                    baseURL,
                    headers: getHeaders(),
                })
                user.value = response
            } catch (e) {
                // Token invalid, clear
                token.value = null
                tokenCookie.value = null
            }
        }
    }

    return {
        user,
        token,
        isAuthenticated,
        login,
        register,
        verifyEmail,
        logout,
        checkAuth,
    }
}
