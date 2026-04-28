import type { User } from '~/types/models'

export function useSuperadminAuth() {
    const config = useRuntimeConfig()
    
    // Isolated state for superadmin (completely separate from regular auth)
    const user = useState<User | null>('superadmin-user', () => null)
    const tokenCookie = useCookie<string | null>('superadmin-token', {
        maxAge: 60 * 60 * 24 * 7, // 7 days
        sameSite: 'lax',
        secure: process.env.NODE_ENV === 'production',
        domain: config.public.cookieDomain || undefined,
    })
    const token = useState<string | null>('superadmin-token', () => tokenCookie.value || null)

    const isAuthenticated = computed(() => !!token.value && !!user.value)

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

    // Superadmin login (separate endpoint)
    async function login(email: string, password: string) {
        const response = await $fetch<{ user: User; token: string }>('sysadmin/auth/login', {
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

    // Superadmin logout
    async function logout() {
        try {
            await $fetch('sysadmin/auth/logout', {
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
    }

    // Check auth status (restore from cookie)
    async function checkAuth() {
        // Restore token from cookie if not set
        if (!token.value && tokenCookie.value) {
            token.value = tokenCookie.value
        }

        if (token.value && !user.value) {
            try {
                const response = await $fetch<User>('sysadmin/auth/user', {
                    baseURL,
                    headers: getHeaders(),
                })
                user.value = response
            } catch (error: any) {
                console.error('Superadmin auth verification failed:', error)
                // ONLY clear the token if the server explicitly says it is invalid (401)
                if (error.statusCode === 401) {
                    token.value = null
                    tokenCookie.value = null
                }
            }
        }
    }

    return {
        user,
        token,
        isAuthenticated,
        login,
        logout,
        checkAuth,
        getHeaders,
    }
}
