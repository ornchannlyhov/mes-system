// Composable for detecting user inactivity and auto-logout
// Automatically logs out user after 2 hours of inactivity

const INACTIVITY_TIMEOUT = 2 * 60 * 60 * 1000 // 2 hours in milliseconds
const WARNING_TIME = 5 * 60 * 1000 // 5 minutes warning before logout

export function useIdleTimeout() {
    const warningShown = ref(false)
    const timeRemaining = ref(INACTIVITY_TIMEOUT)
    let inactivityTimer: NodeJS.Timeout | null = null
    let warningTimer: NodeJS.Timeout | null = null
    let countdownInterval: NodeJS.Timeout | null = null

    const { logout } = useAuth()
    const router = useRouter()

    // Reset the inactivity timer
    function resetTimer() {
        // Clear existing timers
        if (inactivityTimer) clearTimeout(inactivityTimer)
        if (warningTimer) clearTimeout(warningTimer)
        if (countdownInterval) clearInterval(countdownInterval)

        warningShown.value = false
        timeRemaining.value = INACTIVITY_TIMEOUT

        // Set warning timer (show warning 5 minutes before logout)
        warningTimer = setTimeout(() => {
            warningShown.value = true
            startCountdown()
        }, INACTIVITY_TIMEOUT - WARNING_TIME)

        // Set logout timer
        inactivityTimer = setTimeout(() => {
            performLogout()
        }, INACTIVITY_TIMEOUT)
    }

    // Start countdown for warning display
    function startCountdown() {
        timeRemaining.value = WARNING_TIME
        countdownInterval = setInterval(() => {
            timeRemaining.value -= 1000
            if (timeRemaining.value <= 0) {
                if (countdownInterval) clearInterval(countdownInterval)
            }
        }, 1000)
    }

    // Perform logout and redirect
    async function performLogout() {
        await logout()
        router.push('/auth/login')
        // Show toast notification
        const toast = useToast()
        toast.error('Your session has expired due to inactivity. Please log in again.')
    }

    // User wants to stay logged in - refresh token
    async function stayLoggedIn() {
        try {
            const { token } = useAuth()
            const config = useRuntimeConfig()
            const baseURL = (import.meta.server ? config.apiBase : config.public.apiBase) as string
            const apiKey = config.public.apiKey as string | undefined

            const headers: Record<string, string> = {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token.value}`,
            }
            if (apiKey) {
                headers['X-API-Key'] = apiKey
            }

            // Call refresh endpoint
            const response = await $fetch<{ token: string; expires_in: number }>('auth/refresh', {
                baseURL,
                method: 'POST',
                headers,
            })

            // Update token in auth composable
            token.value = response.token

            // Update the cookie
            const tokenCookie = useCookie<string | null>('auth-token', {
                maxAge: 60 * 60 * 24 * 7,
                sameSite: 'lax',
                secure: process.env.NODE_ENV === 'production',
                domain: config.public.cookieDomain || undefined,
            })
            tokenCookie.value = response.token

            // Reset the timer
            resetTimer()

            const toast = useToast()
            toast.success('Session refreshed successfully')
        } catch (error) {
            console.error('Failed to refresh token:', error)
            await performLogout()
        }
    }

    // Named handler — anonymous arrow functions cannot be passed to removeEventListener
    const handleVisibilityChange = () => {
        if (document.visibilityState === 'visible') {
            resetTimer()
        }
    }

    // Initialize idle detection
    function startIdleDetection() {
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click']
        events.forEach((event) => {
            document.addEventListener(event, resetTimer, { passive: true })
        })
        document.addEventListener('visibilitychange', handleVisibilityChange)
        resetTimer()
    }

    // Stop idle detection (cleanup)
    function stopIdleDetection() {
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click']
        events.forEach((event) => {
            document.removeEventListener(event, resetTimer)
        })
        document.removeEventListener('visibilitychange', handleVisibilityChange)

        if (inactivityTimer) clearTimeout(inactivityTimer)
        if (warningTimer) clearTimeout(warningTimer)
        if (countdownInterval) clearInterval(countdownInterval)
    }

    // Format remaining time for display (mm:ss)
    const formattedTimeRemaining = computed(() => {
        const minutes = Math.floor(timeRemaining.value / 60000)
        const seconds = Math.floor((timeRemaining.value % 60000) / 1000)
        return `${minutes}:${seconds.toString().padStart(2, '0')}`
    })

    return {
        warningShown,
        timeRemaining,
        formattedTimeRemaining,
        startIdleDetection,
        stopIdleDetection,
        resetTimer,
        stayLoggedIn,
    }
}
