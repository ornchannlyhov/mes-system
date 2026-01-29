export default defineNuxtPlugin(async (nuxtApp) => {
    const { checkAuth } = useAuth()

    // Check auth on initial load (server or client)
    await checkAuth()
})
