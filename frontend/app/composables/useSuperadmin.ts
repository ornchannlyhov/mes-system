import type { User, Organization, SuperadminDashboardStats, PaginatedResponse } from '~/types/models'

export function useSuperadmin() {
    const { getHeaders, baseURL } = useSuperadminApi()
    const toast = useToast()

    const stats = ref<SuperadminDashboardStats | null>(null)
    const pendingUsers = ref<User[]>([])
    const organizations = ref<Organization[]>([])

    async function fetchDashboardStats() {
        try {
            const response = await $fetch<{ stats: SuperadminDashboardStats; recent_pending: User[] }>('sysadmin/dashboard', {
                baseURL,
                headers: getHeaders(),
            })
            stats.value = response.stats
            return response
        } catch (e: any) {
            toast.error(e.data?.message || 'Failed to fetch dashboard stats')
            throw e
        }
    }

    async function fetchPendingUsers(page = 1, perPage = 10) {
        try {
            const response = await $fetch<PaginatedResponse<User>>(`sysadmin/pending-users?page=${page}&per_page=${perPage}`, {
                baseURL,
                headers: getHeaders(),
            })
            pendingUsers.value = response.data
            return response
        } catch (e: any) {
            toast.error(e.data?.message || 'Failed to fetch pending users')
            throw e
        }
    }

    async function approveUser(userId: number) {
        try {
            const response = await $fetch<{ message: string; user: User }>(`sysadmin/users/${userId}/approve`, {
                baseURL,
                method: 'POST',
                headers: getHeaders(),
            })
            toast.success(response.message)
            return response
        } catch (e: any) {
            toast.error(e.data?.message || 'Failed to approve user')
            throw e
        }
    }

    async function rejectUser(userId: number, reason?: string) {
        try {
            const response = await $fetch<{ message: string }>(`sysadmin/users/${userId}/reject`, {
                baseURL,
                method: 'POST',
                headers: getHeaders(),
                body: reason ? { reason } : undefined,
            })
            toast.success(response.message)
            return response
        } catch (e: any) {
            toast.error(e.data?.message || 'Failed to reject user')
            throw e
        }
    }

    async function fetchOrganizations(page = 1, perPage = 10, isActive?: boolean) {
        try {
            let url = `sysadmin/organizations?page=${page}&per_page=${perPage}`
            if (isActive !== undefined) {
                url += `&is_active=${isActive}`
            }
            const response = await $fetch<PaginatedResponse<Organization>>(url, {
                baseURL,
                headers: getHeaders(),
            })
            organizations.value = response.data
            return response
        } catch (e: any) {
            toast.error(e.data?.message || 'Failed to fetch organizations')
            throw e
        }
    }

    async function fetchOrganizationDetail(orgId: number) {
        try {
            const response = await $fetch<Organization>(`sysadmin/organizations/${orgId}`, {
                baseURL,
                headers: getHeaders(),
            })
            return response
        } catch (e: any) {
            toast.error(e.data?.message || 'Failed to fetch organization details')
            throw e
        }
    }

    async function activateOrganization(orgId: number) {
        try {
            const response = await $fetch<{ message: string; organization: Organization }>(`sysadmin/organizations/${orgId}/activate`, {
                baseURL,
                method: 'POST',
                headers: getHeaders(),
            })
            toast.success(response.message)
            return response
        } catch (e: any) {
            toast.error(e.data?.message || 'Failed to activate organization')
            throw e
        }
    }

    async function deactivateOrganization(orgId: number, reason?: string) {
        try {
            const response = await $fetch<{ message: string; organization: Organization }>(`sysadmin/organizations/${orgId}/deactivate`, {
                baseURL,
                method: 'POST',
                headers: getHeaders(),
                body: reason ? { reason } : undefined,
            })
            toast.success(response.message)
            return response
        } catch (e: any) {
            toast.error(e.data?.message || 'Failed to deactivate organization')
            throw e
        }
    }

    async function updateUserLimitOrg(orgId: number, userLimit: number) {
        try {
            const response = await $fetch<{ message: string; organization: Organization }>(`sysadmin/organizations/${orgId}/user-limit`, {
                baseURL,
                method: 'PUT',
                headers: getHeaders(),
                body: { user_limit: userLimit },
            })
            toast.success(response.message)
            return response
        } catch (e: any) {
            toast.error(e.data?.message || 'Failed to update user limit')
            throw e
        }
    }

    return {
        stats,
        pendingUsers,
        organizations,
        fetchDashboardStats,
        fetchPendingUsers,
        approveUser,
        rejectUser,
        fetchOrganizations,
        fetchOrganizationDetail,
        activateOrganization,
        deactivateOrganization,
        updateUserLimit: updateUserLimitOrg,
    }
}

// Helper for superadmin API calls
function useSuperadminApi() {
    const config = useRuntimeConfig()
    const { token } = useSuperadminAuth()

    const baseURL = (import.meta.server ? config.apiBase : config.public.apiBase) as string
    const apiKey = config.public.apiKey as string | undefined

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

    return { getHeaders, baseURL }
}
