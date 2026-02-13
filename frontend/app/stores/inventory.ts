import { defineStore } from 'pinia'

export const useInventoryStore = defineStore('inventory', {
    state: () => ({
        stocks: [] as any[],
        adjustments: [] as any[],
        lastFetchConfigs: {
            stocks: 0,
            adjustments: 0
        }
    }),

    actions: {
        async fetchStocks(force = false) {
            const { $api } = useApi()
            const now = Date.now()

            // Cache valid for 60 seconds
            if (!force && this.stocks.length > 0 && (now - this.lastFetchConfigs.stocks) < 60000) {
                return
            }

            try {
                const res = await $api<{ data: any[] }>('/stocks')
                this.stocks = res.data || []
                this.lastFetchConfigs.stocks = now
            } catch (e) {
                console.error('Failed to fetch stocks', e)
                throw e
            }
        },

        async fetchAdjustments(force = false) {
            const { $api } = useApi()
            const now = Date.now()

            // Cache valid for 60 seconds
            if (!force && this.adjustments.length > 0 && (now - this.lastFetchConfigs.adjustments) < 60000) {
                return
            }

            try {
                // Only cache the first page/list for speed
                const res = await $api<{ data: any[] }>('/stock-adjustments')
                this.adjustments = res.data || []
                this.lastFetchConfigs.adjustments = now
            } catch (e) {
                console.error('Failed to fetch adjustments', e)
                throw e
            }
        }
    }
})
