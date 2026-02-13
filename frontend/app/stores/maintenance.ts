import type { MaintenanceRequest, MaintenanceSchedule } from '~/types/models'

export const useMaintenanceStore = defineStore('maintenance', {
    state: () => ({
        requests: [] as MaintenanceRequest[],
        schedules: [] as MaintenanceSchedule[],
        lastFetch: {
            requests: 0,
            schedules: 0,
        },
    }),

    actions: {
        async fetchRequests(force = false) {
            const { $api } = useApi()
            const now = Date.now()

            // Cache valid for 60 seconds
            if (!force && this.requests.length > 0 && (now - this.lastFetch.requests) < 60000) {
                return
            }

            try {
                const res = await $api<{ data: MaintenanceRequest[] }>('/maintenance/requests')
                this.requests = res.data || []
                this.lastFetch.requests = now
            } catch (e) {
                console.error('Failed to fetch maintenance requests', e)
                throw e
            }
        },

        async fetchSchedules(force = false) {
            const { $api } = useApi()
            const now = Date.now()

            // Cache valid for 60 seconds
            if (!force && this.schedules.length > 0 && (now - this.lastFetch.schedules) < 60000) {
                return
            }

            try {
                const res = await $api<{ data: MaintenanceSchedule[] }>('/maintenance/schedules')
                this.schedules = res.data || []
                this.lastFetch.schedules = now
            } catch (e) {
                console.error('Failed to fetch maintenance schedules', e)
                throw e
            }
        }
    }
})
