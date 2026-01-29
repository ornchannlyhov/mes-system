import { defineStore } from 'pinia'

interface OeeRecord {
    id: number
    work_center_id: number
    work_center?: { name: string }
    record_date: string
    availability_score: number
    performance_score: number
    quality_score: number
    oee_score: number
}

interface CostEntry {
    id: number
    manufacturing_order_id: number
    product_id: number
    product?: { name: string }
    cost_type: string
    quantity: number
    unit_cost: number
    total_cost: number
}

interface DashboardStats {
    total_products: number
    total_mos: number
    completed_mos: number
    pending_mos: number
}

export const useReportingStore = defineStore('reporting', {
    state: () => ({
        oeeRecords: [] as OeeRecord[],
        costEntries: [] as CostEntry[],
        dashboardStats: null as DashboardStats | null,
        lastFetch: {
            oee: 0,
            cost: 0,
            dashboard: 0,
        },
    }),
    getters: {
        avgOee: (state) => {
            if (!state.oeeRecords.length) return 0
            return state.oeeRecords.reduce((sum, r) => sum + r.oee_score * 100, 0) / state.oeeRecords.length
        },
        totalCost: (state) => {
            return state.costEntries.reduce((sum, e) => sum + Number(e.total_cost || 0), 0)
        },
        costByType: (state) => {
            const result = { material: 0, labor: 0, overhead: 0, scrap: 0 }
            state.costEntries.forEach(e => {
                if (e.cost_type in result) {
                    result[e.cost_type as keyof typeof result] += Number(e.total_cost || 0)
                }
            })
            return result
        },
    },
    actions: {
        async fetchOeeRecords(force = false) {
            const { $api } = useApi()
            const now = Date.now()
            if (!force && this.oeeRecords.length > 0 && (now - this.lastFetch.oee) < 300000) {
                return
            }
            const res = await $api<{ data: OeeRecord[] }>('/reporting/oee')
            this.oeeRecords = res.data || []
            this.lastFetch.oee = now
        },

        async fetchCostEntries(force = false) {
            const { $api } = useApi()
            const now = Date.now()
            if (!force && this.costEntries.length > 0 && (now - this.lastFetch.cost) < 300000) {
                return
            }
            const res = await $api<{ data: CostEntry[] }>('/reporting/cost')
            this.costEntries = res.data || []
            this.lastFetch.cost = now
        },

        async fetchDashboardStats(force = false) {
            const { $api } = useApi()
            const now = Date.now()
            if (!force && this.dashboardStats && (now - this.lastFetch.dashboard) < 60000) {
                return
            }
            const res = await $api<DashboardStats>('/reporting/dashboard')
            this.dashboardStats = res
            this.lastFetch.dashboard = now
        },
    },
})
