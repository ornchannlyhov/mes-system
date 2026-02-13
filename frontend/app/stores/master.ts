import { defineStore } from 'pinia'
import type { Product, WorkCenter, Location } from '~/types/models'

export const useMasterStore = defineStore('master', {
    state: () => ({
        products: [] as Product[],
        workCenters: [] as WorkCenter[],
        locations: [] as Location[],
        boms: [] as any[],
        equipment: [] as any[],
        lastFetch: {
            products: 0,
            workCenters: 0,
            locations: 0,
            boms: 0,
            equipment: 0,
        },
    }),
    actions: {
        async fetchProducts(force = false) {
            const { $api } = useApi()
            const now = Date.now()
            // Cache for 5 minutes (300000 ms)
            if (!force && this.products.length > 0 && (now - this.lastFetch.products) < 300000) {
                return
            }
            const res = await $api<{ data: Product[] }>('/products')
            this.products = res.data || []
            this.lastFetch.products = now
        },

        async fetchWorkCenters(force = false) {
            const { $api } = useApi()
            const now = Date.now()
            if (!force && this.workCenters.length > 0 && (now - this.lastFetch.workCenters) < 300000) {
                return
            }
            const res = await $api<{ data: WorkCenter[] }>('/work-centers')
            this.workCenters = res.data || []
            this.lastFetch.workCenters = now
        },

        async fetchLocations(force = false) {
            const { $api } = useApi()
            const now = Date.now()
            if (!force && this.locations.length > 0 && (now - this.lastFetch.locations) < 300000) {
                return
            }
            const res = await $api<{ data: Location[] }>('/locations')
            this.locations = res.data || []
            this.lastFetch.locations = now
        },

        async fetchBoms(force = false) {
            const { $api } = useApi()
            const now = Date.now()
            if (!force && this.boms?.length > 0 && (now - this.lastFetch.boms) < 300000) {
                return
            }
            const res = await $api<{ data: any[] }>('/boms')
            this.boms = res.data || []
            this.lastFetch.boms = now
        },

        async fetchEquipment(force = false) {
            const { $api } = useApi()
            const now = Date.now()
            if (!force && this.equipment?.length > 0 && (now - this.lastFetch.equipment) < 300000) {
                return
            }
            const res = await $api<{ data: any[] }>('/equipment')
            this.equipment = res.data || []
            this.lastFetch.equipment = now
        }
    }
})
