<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Stock Overview</h1>
        <p class="text-gray-500 mt-1 hidden sm:block">View inventory levels across all locations</p>
      </div>
    </div>

    <!-- Filter by Location -->
    <!-- Toolbar -->
    <div class="card !overflow-visible z-20">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <!-- Search Left -->
        <div class="relative flex-1">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
             <Icon name="heroicons:magnifying-glass" class="h-5 w-5 text-gray-400" />
          </div>
          <input v-model="search" type="text" placeholder="Search products..." class="input w-full pl-10" />
        </div>

        <!-- Filter Right -->
        <div class="relative w-full sm:w-auto">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <Icon name="heroicons:funnel" class="h-5 w-5 text-gray-400" />
          </div>
          <UiSearchableSelect
            v-model="selectedLocation"
            :options="[{ label: 'All Locations', value: '' }, ...locations.map(l => ({ label: l.name, value: l.id }))]"
            placeholder="All Locations"
            class="min-w-[200px]"
          />
        </div>
      </div>
    </div>

    <!-- Stock Table -->
    <div class="card p-0 overflow-hidden">
      <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th class="w-16">Image</th>
            <th>Product</th>
            <th>Location</th>
            <th>Lot</th>
            <th>Available</th>
            <th>Reserved</th>
            <th>Total</th>
            <th class="w-16">History</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="stock in paginatedStock" :key="stock.id">
            <td>
                <div 
                    class="w-10 h-10 rounded bg-gray-50 border border-gray-200 flex items-center justify-center overflow-hidden cursor-pointer hover:ring-2 hover:ring-primary-500 hover:ring-offset-1 transition-all"
                    @click="openImage(stock.product?.image_url, stock.product?.name)"
                >
                <img 
                    v-if="stock.product?.image_url" 
                    :src="getImageUrl(stock.product.image_url)" 
                    class="w-full h-full object-cover" 
                />
                <Icon v-else name="heroicons:cube" class="w-5 h-5 text-gray-300" />
                </div>
            </td>
            <td class="font-medium text-primary-600 hover:text-primary-800 cursor-pointer" @click="openDetail(stock)">
                {{ stock.product?.name || 'N/A' }}
            </td>
            <td>{{ stock.location?.name || 'N/A' }}</td>
            <td class="font-mono text-sm">{{ stock.lot?.name || '-' }}</td>
            <td class="text-green-600 font-medium">{{ Math.floor(stock.quantity - stock.reserved_qty) }}</td>
            <td class="text-yellow-600">{{ Math.floor(stock.reserved_qty) }}</td>
            <td class="font-medium">{{ Math.floor(stock.quantity) }}</td>
            <td>
               <UiIconButton
                  @click="openDetail(stock)"
                  icon="heroicons:clock"
                  tooltip="View History"
                />
            </td>
          </tr>
          <tr v-if="filteredStock.length === 0 && !loading">
            <td colspan="7">
              <UiEmptyState 
                title="No stock found" 
                description="Inventory items will appear here once you purchase or manufacture products."
                icon="heroicons:archive-box"
              />
            </td>
          </tr>
        </tbody>
        <tbody v-if="loading">
             <tr v-for="i in 5" :key="`skel-${i}`" class="animate-pulse">
                <td class="px-6 py-4"><div class="w-10 h-10 bg-gray-200 rounded"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-48"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-32"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-20"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-16"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-16"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-16"></div></td>
                <td class="px-6 py-4"><div class="h-8 bg-gray-200 rounded w-10"></div></td>
             </tr>
        </tbody>
      </table>
      </div>
      <UiPagination
        v-if="filteredStock.length > pageSize"
        v-model="currentPage"
        :total-items="filteredStock.length"
        :page-size="pageSize"
      />
      </div>

    
    <UiImagePreview v-model="showImagePreview" :src="previewImage.src" :alt="previewImage.alt" />

    <!-- Stock Detail SlideOver -->
    <UiSlideOver v-model="showDetailSlideOver" title="Stock Details" width="max-w-2xl sm:w-[800px]">
      <div v-if="selectedStock" class="space-y-6">
          <!-- Header Info -->
          <div class="flex flex-col md:flex-row gap-6">
             <div class="w-24 h-24 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden shrink-0">
                 <img 
                    v-if="selectedStock.product?.image_url" 
                    :src="getImageUrl(selectedStock.product.image_url)" 
                    class="w-full h-full object-cover" 
                />
                <Icon v-else name="heroicons:cube" class="w-10 h-10 text-gray-400" />
            </div>
            <div class="flex-1 space-y-3">
                <div>
                   <h3 class="text-lg font-semibold text-gray-900">{{ selectedStock.product?.name }}</h3>
                   <p class="text-sm text-gray-500 font-mono">{{ selectedStock.product?.code }}</p>
                </div>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500 block">Location</span>
                        <span class="font-medium">{{ selectedStock.location?.name }}</span>
                    </div>
                     <div v-if="selectedStock.lot">
                        <span class="text-gray-500 block">Lot</span>
                        <span class="font-mono bg-gray-100 px-1.5 py-0.5 rounded">{{ selectedStock.lot.name }}</span>
                    </div>
                </div>
            </div>
             <div class="text-right">
               <p class="text-sm text-gray-500 mb-1">Current Quantity</p>
               <p class="text-3xl font-bold text-primary-600">{{ Number(selectedStock.quantity) }}</p>
               <div class="text-xs text-gray-500 mt-1">
                 <div>Available: {{ Number(selectedStock.quantity) - Number(selectedStock.reserved_qty) }}</div>
               </div>
            </div>
          </div>

          <div class="border-t border-gray-200"></div>

          <!-- History Table -->
          <div>
            <div class="flex items-center justify-between mb-4">
                 <h4 class="font-medium text-gray-900">Movement History</h4>
                  <!-- Spinner replaced by skeletons in table -->
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Qty</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ref</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-if="loadingHistory" v-for="i in 3" :key="`hskel-${i}`" class="animate-pulse">
                            <td class="px-3 py-2"><div class="h-4 bg-gray-200 rounded w-24"></div></td>
                            <td class="px-3 py-2"><div class="h-4 bg-gray-200 rounded w-16"></div></td>
                            <td class="px-3 py-2"><div class="h-4 bg-gray-200 rounded w-12 ml-auto"></div></td>
                            <td class="px-3 py-2"><div class="h-4 bg-gray-200 rounded w-20"></div></td>
                            <td class="px-3 py-2"><div class="h-4 bg-gray-200 rounded w-16"></div></td>
                            <td class="px-3 py-2"><div class="h-4 bg-gray-200 rounded w-24"></div></td>
                        </tr>
                        <template v-else>
                        <tr v-for="adj in paginatedHistory" :key="adj.id">
                            <td class="px-3 py-2 text-sm text-gray-600 whitespace-nowrap">{{ formatDate(adj.created_at) }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                <span :class="['text-xs font-medium px-2 py-0.5 rounded-full', Number(adj.quantity) > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800']">
                                    {{ Number(adj.quantity) > 0 ? 'In' : 'Out' }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-sm text-right font-medium" :class="Number(adj.quantity) > 0 ? 'text-green-600' : 'text-red-600'">
                                {{ Number(adj.quantity) > 0 ? '+' : '' }}{{ Number(adj.quantity) }}
                            </td>
                            <td class="px-3 py-2 text-sm">
                                <span :class="getReasonClass(adj.reason)">
                                    {{ formatReason(adj.reason) }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-sm">
                                <div v-if="adj.reference">
                                  <a v-if="isFileUrl(adj.reference)" :href="getImageUrl(getFileUrl(adj.reference))" target="_blank" class="text-primary-600 hover:underline flex items-center gap-1">
                                    <Icon name="heroicons:document" class="w-4 h-4" />
                                    View
                                  </a>
                                  <span v-else class="text-gray-500">{{ adj.reference }}</span>
                                </div>
                                <span v-else class="text-gray-400">-</span>
                            </td>
                            <td class="px-3 py-2 text-sm text-gray-500">{{ adj.user?.name || '-' }}</td>
                        </tr>
                        <tr v-if="adjustments.length === 0">
                            <td colspan="6" class="px-3 py-8 text-center text-gray-500 text-sm">
                                No history found for this item.
                            </td>
                        </tr>
                        </template>
                    </tbody>
                </table>
            </div>
             <div v-if="adjustments.length > historyPageSize" class="mt-4">
                 <UiPagination v-model="historyPage" :total-items="adjustments.length" :page-size="historyPageSize" />
            </div>
          </div>
      </div>
    </UiSlideOver>
  </div>
</template>

<script setup lang="ts">
import type { Stock, StockAdjustment, Lot } from '~/types/models'

const { $api } = useApi()
const toast = useToast()
const config = useRuntimeConfig()
const masterStore = useMasterStore()

const stocks = ref<Stock[]>([])
const locations = computed(() => masterStore.locations)
const selectedLocation = ref('')
const search = ref('')
const currentPage = ref(1)
const pageSize = 10
const loading = ref(true)
const { getImageUrl, formatDate } = useUtils()

// Search similar to adjustments page logic
function isFileUrl(ref: string) {
  if (!ref) return false
  return ref.startsWith('uploads/') || ref.startsWith('storage/') || ref.startsWith('/storage/')
}

function getFileUrl(ref: string) {
  if (ref.startsWith('/storage/')) return ref
  if (ref.startsWith('storage/')) return '/' + ref
  if (ref.startsWith('uploads/')) return '/storage/' + ref
  return ref
}

function getReasonClass(reason: string) {
  const classes: Record<string, string> = {
    purchase: 'badge-blue',
    physical_count: 'badge-purple',
    correction: 'badge-yellow',
    loss: 'badge-red',
    damage: 'badge-red',
    initial: 'badge-green',
  }
  return classes[reason] || 'badge-gray'
}

function formatReason(reason: string) {
  return reason.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

// Detail SlideOver State
const showDetailSlideOver = ref(false)
const selectedStock = ref<Stock | null>(null)
const adjustments = ref<StockAdjustment[]>([])
const loadingHistory = ref(false)
const historyPage = ref(1)
const historyPageSize = 10

const filteredStock = computed(() => {
  return stocks.value.filter(s => {
    const matchesLocation = !selectedLocation.value || s.location_id === Number(selectedLocation.value)
    const matchesSearch = !search.value || s.product?.name?.toLowerCase().includes(search.value.toLowerCase())
    return matchesLocation && matchesSearch
  })
})

const paginatedStock = computed(() => {
  const start = (currentPage.value - 1) * pageSize
  return filteredStock.value.slice(start, start + pageSize)
})

const paginatedHistory = computed(() => {
  const start = (historyPage.value - 1) * historyPageSize
  return adjustments.value.slice(start, start + historyPageSize)
})

// Image Preview
const showImagePreview = ref(false)
const previewImage = ref({ src: '', alt: '' })

function openImage(src?: string, alt?: string) {
  if (!src) return
  previewImage.value = { src: getImageUrl(src), alt: alt || 'Stock Item' }
  showImagePreview.value = true
}

async function openDetail(stock: Stock) {
  selectedStock.value = stock
  showDetailSlideOver.value = true
  loadingHistory.value = true
  adjustments.value = []
  historyPage.value = 1
  
  try {
     // Fetch full details to ensure relationships are loaded (though we pass most in stock obj)
     // Actually, we can just use the stock object we have if it's populated enough, 
     // but let's fetch history using its IDs.
     const params = new URLSearchParams()
     if (stock.product_id) params.append('product_id', String(stock.product_id))
     if (stock.location_id) params.append('location_id', String(stock.location_id))
     if (stock.lot_id) params.append('lot_id', String(stock.lot_id))
     
     const adjRes = await $api<{ data: StockAdjustment[] }>(`/stock-adjustments?${params.toString()}`)
     adjustments.value = adjRes.data || []
  } catch (e) {
      console.error('Failed to load history', e)
      toast.error('Failed to load history')
  } finally {
      loadingHistory.value = false
  }
}

watch([search, selectedLocation], () => {
  currentPage.value = 1
})

async function fetchData() {
  loading.value = true
  try {
    const [stockRes] = await Promise.all([
      $api<{ data: Stock[] }>('/stocks'),
      masterStore.fetchLocations(),
    ])
    stocks.value = stockRes.data || []
  } catch (e) {
    toast.error('Failed to fetch stock data')
  } finally {
    loading.value = false
  }
}

onMounted(fetchData)
</script>
