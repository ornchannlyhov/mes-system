<template>
  <div class="space-y-6">
    <div class="page-header">
      <div>
        <h1 class="page-title">Stock Adjustments</h1>
        <p class="text-gray-500 mt-1 hidden sm:block">Manual inventory adjustments and receipts</p>
      </div>
      <button class="btn-primary" @click="openModal()">
        <Icon name="heroicons:plus" class="w-4 h-4" />
        <span class="hidden sm:inline">New Adjustment</span>
        <span class="sm:hidden">New</span>
      </button>
    </div>

    <!-- Tabs / Filters -->
    <div class="flex gap-2 border-b border-gray-200 overflow-x-auto scrollbar-hide -mx-4 px-4 md:mx-0 md:px-0">
      <button
        v-for="tab in tabs"
        :key="tab.value"
        @click="filterReason = tab.value"
        :class="[
          'px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors whitespace-nowrap',
          filterReason === tab.value
            ? 'border-primary-600 text-primary-600'
            : 'border-transparent text-gray-500 hover:text-gray-700'
        ]"
      >
        {{ tab.label }}
        <span :class="['ml-1 text-xs px-1.5 py-0.5 rounded-full', filterReason === tab.value ? 'bg-gray-100 text-gray-800' : 'bg-gray-100 text-gray-600']">
          {{ getTabCount(tab.value) }}
        </span>
      </button>
    </div>

    <div class="card p-0 overflow-hidden">
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Product</th>
              <th>Location</th>
              <th>Lot</th>
              <th>Qty</th>
              <th>Reason</th>
              <th>Reference</th>
              <th>User</th>
              <th class="w-16">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="adj in paginatedAdjustments" :key="adj.id">
              <td class="text-sm text-gray-500">{{ formatDate(adj.created_at) }}</td>
              <td class="font-medium">{{ adj.product?.name || 'N/A' }}</td>
              <td>{{ adj.location?.name || 'N/A' }}</td>
              <td class="font-mono text-sm">{{ adj.lot?.name || '-' }}</td>
              <td :class="Number(adj.quantity) >= 0 ? 'text-green-600 font-medium' : 'text-red-600 font-medium'">
                {{ Number(adj.quantity) >= 0 ? '+' : '' }}{{ Number(adj.quantity) }}
              </td>
              <td>
                <span :class="getReasonClass(adj.reason)">
                  {{ formatReason(adj.reason) }}
                </span>
              </td>
              <td class="text-sm">
                <div v-if="adj.reference">
                  <a v-if="isFileUrl(adj.reference)" :href="getImageUrl(getFileUrl(adj.reference))" target="_blank" class="text-primary-600 hover:underline flex items-center gap-1">
                    <Icon name="heroicons:document" class="w-4 h-4" />
                    View Document
                  </a>
                  <span v-else>{{ adj.reference }}</span>
                </div>
                <span v-else class="text-gray-400">-</span>
              </td>
              <td class="text-sm">{{ adj.user?.name || 'Unknown' }}</td>
              <td>
                <div class="flex gap-1">
                  <UiIconButton
                    @click="openModal(adj)"
                    icon="heroicons:pencil"
                    tooltip="Edit Adjustment"
                  />
                  <UiIconButton
                    v-if="!['manufacturing_consumption', 'manufacturing_production'].includes(adj.reason)"
                    @click="confirmDelete(adj)"
                    icon="heroicons:trash"
                    tooltip="Delete Adjustment"
                    color="text-red-400 hover:text-red-600"
                  />
                </div>
              </td>
            </tr>
            <tr v-if="filteredAdjustments.length === 0 && !loading">
              <td colspan="9">
                <UiEmptyState 
                  title="No adjustments found" 
                  description="Create an adjustment to manually update stock levels."
                  icon="heroicons:arrows-up-down"
                >
                  <button class="btn-primary" @click="openModal()">
                    <Icon name="heroicons:plus" class="w-4 h-4" />
                    New Adjustment
                  </button>
                </UiEmptyState>
              </td>
            </tr>
          </tbody>
          <tbody v-if="loading">
             <tr v-for="i in 5" :key="`skel-${i}`" class="animate-pulse">
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-24"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-48"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-32"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-16"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-16"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-24"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-32"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-24"></div></td>
                <td class="px-6 py-4"><div class="h-8 bg-gray-200 rounded w-20"></div></td>
             </tr>
          </tbody>
        </table>
      </div>
      <UiPagination v-model="currentPage" :total-items="filteredAdjustments.length" :page-size="pageSize" />
    </div>

    <!-- Create/Edit Adjustment SlideOver -->
    <UiSlideOver v-model="showModal" :title="editingItem ? 'Edit Stock Adjustment' : 'New Stock Adjustment'">
      <form @submit.prevent="save" class="space-y-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Product *</label>
          <UiSearchableSelect
            v-model="form.product_id"
            :options="products.map(p => ({ label: p.name, value: p.id }))"
            placeholder="Select product..."
            @update:modelValue="onProductChange"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Location *</label>
          <UiSearchableSelect
            v-model="form.location_id"
            :options="availableLocations"
            placeholder="Select location..."
            @update:modelValue="onLocationChange"
          />
          <p v-if="form.product_id && productStocks.length === 0" class="text-xs text-amber-600 mt-1">
            No existing stock found for this product. Select any location to add initial stock.
          </p>
        </div>
        
        <!-- Current Stock Info -->
        <div v-if="currentStock" class="p-4 bg-gray-50 rounded-lg border border-gray-200">
          <p class="text-sm font-medium text-gray-700 mb-2">Current Stock Information</p>
          <div class="grid grid-cols-2 gap-3 text-sm">
            <div>
              <span class="text-gray-500">Available:</span>
              <span class="ml-2 font-semibold text-green-600">{{ Math.floor(currentStock.quantity - currentStock.reserved_qty) }}</span>
            </div>
            <div>
              <span class="text-gray-500">Reserved:</span>
              <span class="ml-2 font-semibold text-yellow-600">{{ Math.floor(currentStock.reserved_qty) }}</span>
            </div>
            <div>
              <span class="text-gray-500">Total:</span>
              <span class="ml-2 font-semibold text-gray-900">{{ Math.floor(currentStock.quantity) }}</span>
            </div>
            <div v-if="currentStock.lot">
              <span class="text-gray-500">Lot:</span>
              <span class="ml-2 font-mono text-xs font-medium">{{ currentStock.lot.name }}</span>
            </div>
          </div>
        </div>
        <div v-if="selectedProduct?.tracking === 'lot'">
          <label class="block text-sm font-medium text-gray-700 mb-1">Lot</label>
          <UiSearchableSelect
            v-model="form.lot_id"
            :options="lots.map(l => ({ label: l.name, value: l.id }))"
            placeholder="Select lot (optional)..."
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
          <input 
            v-model.number="form.quantity" 
            type="number" 
            step="0.01" 
            class="input" 
            placeholder="Positive to add, negative to subtract"
            required 
          />
          <p class="text-xs text-gray-500 mt-1">Use positive numbers to increase stock, negative to decrease</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Reason *</label>
          <select v-model="form.reason" class="input" required>
            <option value="">Select reason...</option>
            <option value="physical_count">Physical Count</option>
            <option value="purchase">Purchase Receipt</option>
            <option value="correction">Correction</option>
            <option value="loss">Loss</option>
            <option value="damage">Damage</option>
            <option value="initial">Initial Stock</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Reference</label>
          <div class="flex gap-2">
            <input 
              v-model="form.reference" 
              type="text" 
              class="input flex-1" 
              placeholder="PO number, invoice, etc."
              :disabled="!!uploadedFile"
            />
            <span class="text-gray-400 self-center">OR</span>
            <label class="btn-outline cursor-pointer whitespace-nowrap">
              <Icon name="heroicons:document-arrow-up" class="w-4 h-4" />
              Upload
              <input 
                type="file" 
                class="hidden" 
                accept="image/*,.pdf,.doc,.docx"
                @change="handleFileUpload"
              />
            </label>
          </div>
          <div v-if="uploadedFile" class="mt-2 flex items-center gap-2 text-sm">
            <Icon name="heroicons:document" class="w-4 h-4 text-primary-600" />
            <span class="text-gray-700">{{ uploadedFile.name }}</span>
            <button type="button" @click="removeFile" class="text-red-600 hover:underline">
              Remove
            </button>
          </div>
          <p class="text-xs text-gray-500 mt-1">Upload a document or enter text reference</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
          <textarea v-model="form.notes" rows="3" class="input" placeholder="Optional notes..."></textarea>
        </div>
        <div class="flex justify-end gap-3 mt-6">
          <button type="button" @click="showModal = false" class="btn-ghost">Cancel</button>
          <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Saving...' : 'Save Adjustment' }}</button>
        </div>
      </form>
    </UiSlideOver>

    <!-- Delete Confirmation Modal -->
    <UiConfirmModal
      v-model="showDeleteModal"
      title="Delete Adjustment"
      :message="`Are you sure you want to delete this stock adjustment? This action cannot be undone.`"
      confirm-text="Delete"
      variant="danger"
      :loading="deleting"
      @confirm="handleDelete"
    />
  </div>
</template>

<script setup lang="ts">
import type { Product, Location, Lot } from '~/types/models'

interface StockAdjustment {
  id: number
  product_id: number
  product?: Product
  location_id: number
  location?: Location
  lot_id?: number | null
  lot?: Lot | null
  quantity: number
  reason: string
  reference?: string
  notes?: string
  user?: { name: string }
  created_at: string
}

const { $api } = useApi()
const toast = useToast()
const masterStore = useMasterStore()
const { formatDate, getImageUrl } = useUtils()

function isFileUrl(ref: string) {
  // Check if it's a file path (uploaded files start with 'uploads/')
  // or already has storage/ or /storage/ prefix
  return ref.startsWith('uploads/') || ref.startsWith('storage/') || ref.startsWith('/storage/')
}

function getFileUrl(ref: string) {
  // If already has /storage/, use it as is
  if (ref.startsWith('/storage/')) return ref
  // If starts with storage/, add leading slash
  if (ref.startsWith('storage/')) return '/' + ref
  // If starts with uploads/, add /storage/ prefix
  if (ref.startsWith('uploads/')) return '/storage/' + ref
  // Otherwise return as is
  return ref
}

const adjustments = ref<StockAdjustment[]>([])
const products = computed(() => masterStore.products)
const locations = computed(() => masterStore.locations)
const lots = ref<Lot[]>([])
const productStocks = ref<any[]>([])
const editingItem = ref<StockAdjustment | null>(null)
const uploadedFile = ref<File | null>(null)
const showModal = ref(false)
const saving = ref(false)
const showDeleteModal = ref(false)
const deleting = ref(false)
const deletingItem = ref<StockAdjustment | null>(null)
const currentPage = ref(1)
const pageSize = 10
const filterReason = ref('')
const loading = ref(true)

const tabs = [
  { label: 'All', value: '' },
  { label: 'Purchase', value: 'purchase' },
  { label: 'Physical Count', value: 'physical_count' },
  { label: 'Correction', value: 'correction' },
  { label: 'Initial', value: 'initial' },
  { label: 'Loss/Damage', value: 'loss' },
  { label: 'Manufacturing', value: 'manufacturing' },
]

const form = ref({
  product_id: null as number | null,
  location_id: null as number | null,
  lot_id: null as number | null,
  quantity: 0,
  reason: '',
  reference: '',
  notes: '',
})

const selectedProduct = computed(() => 
  products.value.find(p => p.id === form.value.product_id)
)

const availableLocations = computed(() => {
  if (!form.value.product_id) {
    return locations.value.map(l => ({ label: l.name, value: l.id }))
  }
  
  // Show locations with stock first, then all others
  const locationsWithStock = productStocks.value.map(s => s.location_id)
  return locations.value.map(l => ({
    label: locationsWithStock.includes(l.id) 
      ? `${l.name} (has stock)` 
      : l.name,
    value: l.id
  }))
})

const currentStock = computed(() => {
  if (!form.value.product_id || !form.value.location_id) return null
  return productStocks.value.find(
    s => s.product_id === form.value.product_id && 
         s.location_id === form.value.location_id &&
         (form.value.lot_id ? s.lot_id === form.value.lot_id : !s.lot_id)
  )
})

const filteredAdjustments = computed(() => {
  if (!filterReason.value) return adjustments.value
  // Handle Loss/Damage tab - show both loss and damage
  if (filterReason.value === 'loss') {
    return adjustments.value.filter(a => a.reason === 'loss' || a.reason === 'damage')
  }
  // Handle Manufacturing tab
  if (filterReason.value === 'manufacturing') {
    return adjustments.value.filter(a => a.reason === 'manufacturing_consumption' || a.reason === 'manufacturing_production')
  }
  return adjustments.value.filter(a => a.reason === filterReason.value)
})

const paginatedAdjustments = computed(() => {
  const start = (currentPage.value - 1) * pageSize
  return filteredAdjustments.value.slice(start, start + pageSize)
})

function getReasonClass(reason: string) {
  const classes: Record<string, string> = {
    purchase: 'badge-blue',
    physical_count: 'badge-purple',
    correction: 'badge-yellow',
    loss: 'badge-red',
    damage: 'badge-red',
    initial: 'badge-green',
    manufacturing_consumption: 'badge-orange',
    manufacturing_production: 'badge-blue',
  }
  return classes[reason] || 'badge-gray'
}

function formatReason(reason: string) {
  return reason.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

function getTabCount(value: string) {
  if (!value) return adjustments.value.length
  // Handle Loss/Damage tab - count both loss and damage
  if (value === 'loss') {
    return adjustments.value.filter(a => a.reason === 'loss' || a.reason === 'damage').length
  }
  if (value === 'manufacturing') {
    return adjustments.value.filter(a => a.reason === 'manufacturing_consumption' || a.reason === 'manufacturing_production').length
  }
  return adjustments.value.filter(a => a.reason === value).length
}

async function onProductChange() {
  form.value.lot_id = null
  form.value.location_id = null
  productStocks.value = []
  
  if (!form.value.product_id) return
  
  // Fetch stock levels for this product
  try {
    const res = await $api<{ data: any[] }>('/stocks')
    productStocks.value = (res.data || []).filter(
      (s: any) => s.product_id === form.value.product_id
    )
  } catch (e) {
    console.error('Failed to fetch stock')
  }
  
  if (selectedProduct.value?.tracking === 'lot') {
    fetchLots()
  }
}

function onLocationChange() {
  // Reset lot when location changes
  form.value.lot_id = null
}

async function fetchLots() {
  try {
    const res = await $api<{ data: Lot[] }>('/lots')
    lots.value = res.data || []
  } catch (e) {
    console.error('Failed to fetch lots')
  }
}

async function fetchData() {
  loading.value = true
  try {
    const [adjRes] = await Promise.all([
      $api<{ data: StockAdjustment[] }>('/stock-adjustments'),
      masterStore.fetchProducts(),
      masterStore.fetchLocations(),
    ])
    adjustments.value = adjRes.data || []
  } catch (e) {
    toast.error('Failed to fetch data')
  } finally {
    loading.value = false
  }
}

function openModal(adj?: StockAdjustment) {
  if (adj) {
    editingItem.value = adj
    form.value = {
      product_id: adj.product_id,
      location_id: adj.location_id,
      lot_id: adj.lot_id || null,
      quantity: adj.quantity,
      reason: adj.reason,
      reference: isFileUrl(adj.reference || '') ? '' : (adj.reference || ''),
      notes: adj.notes || '',
    }
    uploadedFile.value = null
    // Fetch stocks for the product
    onProductChange()
  } else {
    editingItem.value = null
    form.value = {
      product_id: null,
      location_id: null,
      lot_id: null,
      quantity: 0,
      reason: '',
      reference: '',
      notes: '',
    }
    uploadedFile.value = null
  }
  showModal.value = true
}

function handleFileUpload(event: Event) {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (file) {
    uploadedFile.value = file
    form.value.reference = ''
  }
}

function removeFile() {
  uploadedFile.value = null
}

async function save() {
  saving.value = true
  try {
    let referenceValue = form.value.reference
    
    // Upload file if present
    if (uploadedFile.value) {
      const formData = new FormData()
      formData.append('file', uploadedFile.value)
      
      const uploadRes = await $api<{ path: string }>('/upload', { 
        method: 'POST', 
        body: formData 
      })
      referenceValue = uploadRes.path
    }
    
    const payload = { ...form.value, reference: referenceValue }
    
    if (editingItem.value) {
      await $api(`/stock-adjustments/${editingItem.value.id}`, { method: 'PUT', body: payload })
      toast.success('Stock adjustment updated successfully')
    } else {
      await $api('/stock-adjustments', { method: 'POST', body: payload })
      toast.success('Stock adjusted successfully')
    }
    showModal.value = false
    uploadedFile.value = null
    await fetchData()
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to save adjustment')
  } finally {
    saving.value = false
  }
}

function confirmDelete(adj: StockAdjustment) {
  deletingItem.value = adj
  showDeleteModal.value = true
}

async function handleDelete() {
  if (!deletingItem.value) return
  deleting.value = true
  try {
    await $api(`/stock-adjustments/${deletingItem.value.id}`, { method: 'DELETE' })
    toast.success('Adjustment deleted')
    showDeleteModal.value = false
    await fetchData()
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to delete adjustment')
  } finally {
    deleting.value = false
    deletingItem.value = null
  }
}

onMounted(fetchData)
</script>
