/**
 * Common utility composable for formatting and URL helpers
 */
export function useUtils() {
    const config = useRuntimeConfig()

    /**
     * Format a date string to YYYY-MM-DD format
     */
    function formatDate(date?: string, fallback = '-'): string {
        if (!date) return fallback
        try {
            const d = new Date(date)
            if (isNaN(d.getTime())) return fallback
            return d.toISOString().split('T')[0] || fallback
        } catch (e) {
            return fallback
        }
    }

    /**
     * Get the full URL for an image/file path
     */
    function getImageUrl(url?: string): string {
        if (!url) return ''
        if (url.startsWith('http')) return url

        let path = url
        // Ensure path starts with /
        if (!path.startsWith('/')) {
            path = `/${path}`
        }

        // Add /storage prefix if likely a local file and missing it
        if (!path.startsWith('/storage') && !path.startsWith('/images')) {
            path = `/storage${path}`
        }

        return (config.public.apiBase as string).replace(/\/api\/?$/, '') + path
    }

    /**
     * Format duration in minutes to human readable format
     */
    function formatDuration(minutes?: number): string {
        if (!minutes) return '-'
        const h = Math.floor(minutes / 60)
        const m = Math.floor(minutes % 60)
        return h > 0 ? `${h}h ${m}m` : `${m}m`
    }

    function formatCurrency(amount?: number, currency = 'USD'): string {
        if (amount === undefined || amount === null) return '-'
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency,
        }).format(amount)
    }

    return {
        formatDate,
        getImageUrl,
        formatDuration,
        formatCurrency,
    }
}
