import type { User, Role, Permission } from '~/types/models'

export const useAdminStore = defineStore('admin', {
    state: () => ({
        users: [] as User[],
        roles: [] as Role[],
        permissions: [] as Permission[],
        lastFetch: {
            users: 0,
            roles: 0,
            permissions: 0
        }
    }),

    actions: {
        async fetchUsers(force = false) {
            const { $api } = useApi()
            const now = Date.now()

            // Cache valid for 5 minutes (admin data rarely changes)
            if (!force && this.users.length > 0 && (now - this.lastFetch.users) < 300000) {
                return
            }

            try {
                const res = await $api<{ data: User[] }>('/users', { query: { per_page: 100 } })
                this.users = res.data || []
                this.lastFetch.users = now
            } catch (e) {
                console.error('Failed to fetch users', e)
                throw e
            }
        },

        async fetchRoles(force = false) {
            const { $api } = useApi()
            const now = Date.now()

            if (!force && this.roles.length > 0 && (now - this.lastFetch.roles) < 300000) {
                return
            }

            try {
                const res = await $api<{ data: Role[] }>('/roles')
                this.roles = res.data || []
                this.lastFetch.roles = now
            } catch (e) {
                console.error('Failed to fetch roles', e)
                throw e
            }
        },

        async fetchPermissions(force = false) {
            const { $api } = useApi()
            const now = Date.now()

            if (!force && this.permissions.length > 0 && (now - this.lastFetch.permissions) < 300000) {
                return
            }

            try {
                const res = await $api<{ data: Permission[] }>('/permissions')
                this.permissions = res.data || []
                this.lastFetch.permissions = now
            } catch (e) {
                console.error('Failed to fetch permissions', e)
                throw e
            }
        }
    }
})
