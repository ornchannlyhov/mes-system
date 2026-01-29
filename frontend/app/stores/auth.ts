import { defineStore } from 'pinia'
import type { User } from '~/types/models'

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null as User | null,
        token: null as string | null,
    }),
    getters: {
        isAuthenticated: (state) => !!state.token,
    },
    actions: {
        setUser(user: User) {
            this.user = user
        },
        setToken(token: string) {
            this.token = token
        },
        logout() {
            this.user = null
            this.token = null
        },
    },
})
