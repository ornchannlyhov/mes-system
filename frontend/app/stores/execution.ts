import { defineStore } from 'pinia'

export const useExecutionStore = defineStore('execution', {
    state: () => ({
        manufacturingOrders: [] as any[],
        workOrders: [] as any[],
        lastFetchConfigs: {
            manufacturingOrders: 0,
            workOrders: 0
        }
    }),

    actions: {
        async fetchManufacturingOrders(force = false) {
            const { $api } = useApi()
            const now = Date.now()

            // Cache valid for 60 seconds
            if (!force && this.manufacturingOrders.length > 0 && (now - this.lastFetchConfigs.manufacturingOrders) < 60000) {
                return
            }

            try {
                const res = await $api<{ data: any[] }>('/manufacturing-orders?per_page=100') // Fetch more for client-side search
                this.manufacturingOrders = res.data || []
                this.lastFetchConfigs.manufacturingOrders = now
            } catch (e) {
                console.error('Failed to fetch manufacturing orders', e)
                throw e
            }
        },

        async fetchWorkOrders(force = false) {
            const { $api } = useApi()
            const now = Date.now()

            // Cache valid for 60 seconds
            if (!force && this.workOrders.length > 0 && (now - this.lastFetchConfigs.workOrders) < 60000) {
                return
            }

            try {
                const res = await $api<{ data: any[] }>('/work-orders?per_page=100')
                this.workOrders = res.data || []
                this.lastFetchConfigs.workOrders = now
            } catch (e) {
                console.error('Failed to fetch work orders', e)
                throw e
            }
        }
    }
})
