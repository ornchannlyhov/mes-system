// API utility for making authenticated requests
export function useApi() {
    const config = useRuntimeConfig()
    const { token } = useAuth()

    const baseURL = config.public.apiBase as string
    const apiKey = config.public.apiKey as string | undefined

    async function $api<T>(
        endpoint: string,
        options: {
            method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'
            body?: any
            query?: Record<string, any>
        } = {}
    ): Promise<T> {
        const headers: Record<string, string> = {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        }

        // Remove Content-Type for FormData to let browser set boundary
        if (options.body instanceof FormData) {
            delete headers['Content-Type']
        }

        // Add API key
        if (apiKey && typeof apiKey === 'string') {
            headers['X-API-Key'] = apiKey
        }

        // Add auth token
        if (token.value) {
            headers['Authorization'] = `Bearer ${token.value}`
        }

        try {
            const response = await $fetch<T>(endpoint, {
                baseURL,
                method: options.method || 'GET',
                headers,
                body: options.body,
                query: options.query,
            })
            return response
        } catch (error: any) {
            // Handle 401 - redirect to login
            if (error.statusCode === 401) {
                const { logout } = useAuth()
                await logout()
                navigateTo('/auth/login')
            }
            throw error
        }
    }

    return { $api }
}
