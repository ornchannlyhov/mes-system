import { ref, watch, computed } from 'vue'

interface DataTableOptions {
    url: string
    perPage?: number
    initialFilters?: Record<string, any>
    initialSearch?: string
    debounce?: number
}

interface Meta {
    current_page: number
    from: number
    last_page: number
    per_page: number
    to: number
    total: number
}

interface ApiResponse<T> {
    data: T[]
    meta?: Meta // if wrapped in data/meta from Resource or standard paginate
    // our BaseController returns paginator directly which has data property and meta props at root or meta key
    // Laravel default pagination: { data: [...], current_page: 1, ... }
    // The BaseController respondWithPagination returns: { data: [...], current_page: ..., ... others } AND extra meta merged in
}

export function useServerDataTable<T = any>(options: DataTableOptions) {
    const { $api } = useApi()

    // State
    const page = ref(1)
    const perPage = ref(options.perPage || 10)
    const search = ref(options.initialSearch || '')
    const filters = ref<Record<string, any>>({ ...options.initialFilters })
    const sort = ref<{ column: string, direction: 'asc' | 'desc' } | null>(null)

    const items = ref<T[]>([]) as Ref<T[]>
    const total = ref(0)
    const loading = ref(true)
    const counts = ref<Record<string, number>>({}) // For tab counts

    // Pagination Meta
    const pageCount = computed(() => Math.ceil(total.value / perPage.value))
    const from = ref(0)
    const to = ref(0)

    // Fetch Function
    async function refresh() {
        loading.value = true
        try {
            const query: Record<string, any> = {
                page: page.value,
                per_page: perPage.value,
                ...filters.value
            }

            if (search.value) {
                query.search = search.value
            }

            if (sort.value) {
                query.sort_by = sort.value.column
                query.sort_dir = sort.value.direction
            }

            const response: any = await $api(options.url, { query })

            // Handle Laravel Paginator Response Structure
            if (response.data) {
                items.value = response.data
            } else {
                // Fallback if data is root (array) - unlikely for paginate
                items.value = Array.isArray(response) ? response : []
            }

            // Pagination meta
            total.value = response.meta?.total || response.total || 0
            page.value = response.meta?.current_page || response.current_page || 1
            perPage.value = response.meta?.per_page || response.per_page || perPage.value
            from.value = response.meta?.from || response.from || 0
            to.value = response.meta?.to || response.to || 0

            // Extra meta (like counts)
            if (response.counts) {
                counts.value = response.counts
            }
            // sometimes counts are in meta object
            if (response.meta && response.meta.counts) {
                counts.value = response.meta.counts
            }

        } catch (error) {
            console.error('DataTable fetch error:', error)
            // items.value = [] // Optional: clear on error
        } finally {
            loading.value = false
        }
    }

    // Watchers
    watch([page, perPage, filters], () => {
        refresh()
    }, { deep: true })

    // Debounce Search
    let searchTimeout: any
    watch(search, () => {
        page.value = 1 // Reset to page 1 on search
        clearTimeout(searchTimeout)
        searchTimeout = setTimeout(() => {
            refresh()
        }, options.debounce || 300)
    })

    // Sorting Handler
    function handleSort(column: string) {
        if (sort.value?.column === column) {
            // Toggle direction or clear
            if (sort.value.direction === 'asc') {
                sort.value.direction = 'desc'
            } else {
                sort.value = null // Clear sort
            }
        } else {
            sort.value = { column, direction: 'asc' }
        }
        refresh() // trigger refresh immediately
    }

    // Initial Fetch (can be disabled if needed, but usually we want it)
    // refresh() - wait, standard composition API practice is to call it or return it for valid use onMounted.
    // We'll call it immediately if implied, or let consumer call it.
    // Actually, usually automatic.
    // Let's call it here.
    refresh()

    return {
        items,
        total,
        loading,
        counts,
        page,
        perPage,
        search,
        filters,
        sort,
        pageCount,
        from,
        to,
        refresh,
        handleSort
    }
}
