import { defineStore } from 'pinia'
import { useStorage } from '@vueuse/core'

export const useUiStore = defineStore('ui', {
    state: () => ({
        sidebarCollapsed: useStorage('sidebar-collapsed', false),
    }),
    actions: {
        toggleSidebar() {
            this.sidebarCollapsed = !this.sidebarCollapsed
        },
        setSidebarCollapsed(value: boolean) {
            this.sidebarCollapsed = value
        }
    },
})
