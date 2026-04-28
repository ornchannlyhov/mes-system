// Middleware to protect superadmin routes
export default defineNuxtRouteMiddleware(async (to, from) => {
    const { isAuthenticated, user, checkAuth } = useSuperadminAuth()
    
    // Try to restore auth state
    await checkAuth()
    
    // Not authenticated - redirect to superadmin login
    if (!isAuthenticated.value) {
        return navigateTo('/sysadmin/login')
    }
    
    // Extra safety: verify role is superadmin
    if (user.value?.role?.name !== 'superadmin') {
        // Not a superadmin - clear auth and redirect
        const { logout } = useSuperadminAuth()
        await logout()
        return navigateTo('/sysadmin/login')
    }
})
