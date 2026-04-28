// Middleware for organization routes - prevents superadmins from accessing org portal
export default defineNuxtRouteMiddleware(async (to, from) => {
    const { user, checkAuth } = useAuth()
    
    // Try to restore auth state
    await checkAuth()
    
    // If user is superadmin, redirect to superadmin portal
    if (user.value?.role?.name === 'superadmin') {
        return navigateTo('/sysadmin')
    }
    
    // If user is not approved, redirect to login with message
    if (user.value && !user.value.is_approved) {
        return navigateTo('/auth/login?error=pending_approval')
    }
    
    // If user's organization is not active, redirect to login with message
    if (user.value && user.value.organization && !user.value.organization.is_active) {
        return navigateTo('/auth/login?error=organization_suspended')
    }
})
