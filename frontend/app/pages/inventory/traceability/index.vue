<template>
  <div class="space-y-6">
    <div class="page-header">
      <div>
        <h1 class="page-title">Traceability</h1>
        <p class="text-gray-500 mt-1 hidden sm:block">Track product lots and serial numbers</p>
      </div>
      <button v-if="activeTab === 'lots'" class="btn-primary" @click="openLotModal()">
        <Icon name="heroicons:plus" class="w-4 h-4" />
        <span class="hidden sm:inline">Create Lot</span>
        <span class="sm:hidden">Create</span>
      </button>
    </div>

    <!-- Tabs -->
    <div class="flex gap-2 border-b border-gray-200 overflow-x-auto scrollbar-hide -mx-4 px-4 md:mx-0 md:px-0">
      <button
        @click="activeTab = 'lots'"
        :class="[
          'px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors whitespace-nowrap',
          activeTab === 'lots'
            ? 'border-primary-600 text-primary-600'
            : 'border-transparent text-gray-500 hover:text-gray-700'
        ]"
      >
        Lots
        <span :class="['ml-1 text-xs px-1.5 py-0.5 rounded-full', activeTab === 'lots' ? 'bg-gray-100 text-gray-800' : 'bg-gray-100 text-gray-600']">
          {{ totalLots }}
        </span>
      </button>
      <button
        @click="activeTab = 'serials'"
        :class="[
          'px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors whitespace-nowrap',
          activeTab === 'serials'
            ? 'border-primary-600 text-primary-600'
            : 'border-transparent text-gray-500 hover:text-gray-700'
        ]"
      >
        Serials
        <span :class="['ml-1 text-xs px-1.5 py-0.5 rounded-full', activeTab === 'serials' ? 'bg-gray-100 text-gray-800' : 'bg-gray-100 text-gray-600']">
          {{ totalSerials }}
        </span>
      </button>
    </div>

    <!-- Lots Tab -->
    <div v-if="activeTab === 'lots'" class="card p-0 overflow-hidden">
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Name/Code</th>
              <th>Product</th>
              <th>Expiry Date</th>
              <th>Initial Qty</th>
              <th class="w-24">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="lot in lots" :key="lot.id">
              <td class="font-mono">{{ lot.name }}</td>
              <td>{{ lot.product?.name || 'N/A' }}</td>
              <td>{{ formatDate(lot.expiry_date) }}</td>
              <td>{{ Number(lot.initial_qty || 0) }}</td>
              <td class="flex gap-2">
                <UiIconButton
                  @click="openLotModal(lot)"
                  icon="heroicons:pencil"
                  tooltip="Edit Lot"
                />
                <UiIconButton
                  @click="confirmDeleteLot(lot)"
                  icon="heroicons:trash"
                  tooltip="Delete Lot"
                  color="text-red-400 hover:text-red-600"
                />
              </td>
            </tr>
            <tr v-if="lots.length === 0 && !lotsLoading">
              <td colspan="5">
                <UiEmptyState 
                  title="No lots found" 
                  description="Track product batches and expiry dates using lots."
                  icon="heroicons:tag"
                >
                  <button class="btn-primary" @click="openLotModal()">
                    <Icon name="heroicons:plus" class="w-4 h-4" />
                    Create Lot
                  </button>
                </UiEmptyState>
              </td>
            </tr>
          </tbody>
          <tbody v-if="lotsLoading">
             <tr v-for="i in 5" :key="`skel-lot-${i}`" class="animate-pulse">
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-24"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-32"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-24"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-16"></div></td>
                <td class="px-6 py-4"><div class="h-8 bg-gray-200 rounded w-20"></div></td>
             </tr>
          </tbody>
        </table>
      </div>
      <UiPagination 
        v-model="lotCurrentPage" 
        :total-items="totalLots"
        :page-size="lotPageSize"
      />
    </div>

    <!-- Serials Tab -->
    <div v-if="activeTab === 'serials'" class="card p-0 overflow-hidden">
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Serial Name</th>
              <th>Product</th>
              <th>Lot</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="serial in serials" :key="serial.id">
              <td class="font-mono">{{ serial.name }}</td>
              <td>{{ serial.product?.name || 'N/A' }}</td>
              <td>{{ serial.lot?.name || '-' }}</td>
              <td><UiStatusBadge :status="serial.status" /></td>
              <td>
                <div class="flex items-center gap-2">
                  <button @click="viewGenealogy(serial)" class="text-primary-600 hover:underline text-sm font-medium">
                    Genealogy
                  </button>
                  <div class="w-px h-4 bg-gray-300 mx-1"></div>
                  <UiIconButton
                    @click="openSerialModal(serial)"
                    icon="heroicons:pencil"
                    tooltip="Edit Serial"
                  />
                  <UiIconButton
                    @click="confirmDeleteSerial(serial)"
                    icon="heroicons:trash"
                    tooltip="Delete Serial"
                    color="text-red-400 hover:text-red-600"
                  />
                </div>
              </td>
            </tr>
            <tr v-if="serials.length === 0 && !serialsLoading">
              <td colspan="5">
                <UiEmptyState 
                  title="No serial numbers found" 
                  description="Track individual units with unique serial numbers."
                  icon="heroicons:qr-code"
                />
              </td>
            </tr>
          </tbody>
          <tbody v-if="serialsLoading">
             <tr v-for="i in 5" :key="`skel-serial-${i}`" class="animate-pulse">
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-24"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-32"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-24"></div></td>
                <td class="px-6 py-4"><div class="h-5 bg-gray-200 rounded w-16"></div></td>
                <td class="px-6 py-4"><div class="h-8 bg-gray-200 rounded w-32"></div></td>
             </tr>
          </tbody>
        </table>
      </div>
      <UiPagination 
        v-model="serialCurrentPage" 
        :total-items="totalSerials"
        :page-size="serialPageSize"
      />
    </div>

    <!-- Lot SlideOver -->
    <UiSlideOver v-model="showLotModal" :title="editingLot ? 'Edit Lot' : 'Create Lot'">
      <form @submit.prevent="saveLot" class="space-y-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Name/Code *</label>
          <input v-model="lotForm.name" type="text" class="input" required />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Product *</label>
          <UiSearchableSelect
            v-model="lotForm.product_id"
            :options="products.map(p => ({ label: p.name, value: p.id }))"
            placeholder="Select product..."
          />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Initial Qty</label>
            <input v-model.number="lotForm.initial_qty" type="number" step="0.01" class="input" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Expiry Date</label>
            <input v-model="lotForm.expiry_date" type="date" class="input" />
          </div>
        </div>
        <div class="flex justify-end gap-3 mt-6">
          <button type="button" @click="showLotModal = false" class="btn-ghost">Cancel</button>
          <button type="submit" class="btn-primary" :disabled="lotSaving">{{ lotSaving ? 'Saving...' : 'Save' }}</button>
        </div>
      </form>
    </UiSlideOver>

    <!-- Serial SlideOver -->
    <UiSlideOver v-model="showSerialModal" title="Edit Serial">
      <form @submit.prevent="saveSerial" class="space-y-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Serial Number *</label>
          <input v-model="serialForm.name" type="text" class="input" required />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
          <select v-model="serialForm.status" class="input">
            <option value="active">Active</option>
            <option value="consumed">Consumed</option>
            <option value="scrapped">Scrapped</option>
            <option value="sold">Sold</option>
          </select>
        </div>
        <div class="flex justify-end gap-3 mt-6">
          <button type="button" @click="showSerialModal = false" class="btn-ghost">Cancel</button>
          <button type="submit" class="btn-primary" :disabled="serialSaving">{{ serialSaving ? 'Saving...' : 'Update' }}</button>
        </div>
      </form>
    </UiSlideOver>

    <!-- Genealogy SlideOver -->
    <UiSlideOver v-model="showGenealogy" title="Serial Genealogy" width="max-w-4xl">
      <div v-if="genealogy && genealogy.id" class="p-8 overflow-auto flex justify-center min-h-[400px]">
          <!-- Visual Tree -->
           <GenealogyTree :node="genealogy" :is-root="true" />
      </div>
      <div v-else class="p-8 text-center text-gray-400">
        <p v-if="loadingGenealogy">Loading tree...</p>
        <p v-else>No genealogy data found.</p>
      </div>
    </UiSlideOver>

    <!-- Delete Modals -->
    <UiConfirmModal
      v-model="showDeleteLotModal"
      title="Delete Lot"
      :message="`Are you sure you want to delete '${deletingLot?.name}'?`"
      confirm-text="Delete"
      variant="danger"
      :loading="lotDeleting"
      @confirm="handleDeleteLot"
    />

    <UiConfirmModal
      v-model="showDeleteSerialModal"
      title="Delete Serial"
      :message="`Are you sure you want to delete '${deletingSerial?.name}'?`"
      confirm-text="Delete"
      variant="danger"
      :loading="serialDeleting"
      @confirm="handleDeleteSerial"
    />
  </div>
</template>

<script setup lang="ts">
import type { Product, PaginatedResponse, Serial } from '~/types/models'
import GenealogyTree from '~/components/ui/GenealogyTree.vue'

interface Lot {
  id: number
  name: string
  product_id: number
  product?: Product
  expiry_date?: string
  initial_qty?: number
}

const { $api } = useApi()
const toast = useToast()
const masterStore = useMasterStore()
const { formatDate } = useUtils()

// Tab State
const activeTab = ref<'lots' | 'serials'>('lots')

// Lots State
const lots = ref<Lot[]>([])
const lotCurrentPage = ref(1)
const totalLots = ref(0)
const lotPageSize = ref(10)
const showLotModal = ref(false)
const editingLot = ref<Lot | null>(null)
const lotSaving = ref(false)
const showDeleteLotModal = ref(false)
const lotDeleting = ref(false)
const deletingLot = ref<Lot | null>(null)
const lotForm = ref({ name: '', product_id: null as number | null, expiry_date: '', initial_qty: 0 })
const lotsLoading = ref(true)

// Serials State
const serials = ref<Serial[]>([])
const serialCurrentPage = ref(1)
const totalSerials = ref(0)
const serialPageSize = ref(10)
const showSerialModal = ref(false)
const editingSerial = ref<Serial | null>(null)
const serialSaving = ref(false)
const showDeleteSerialModal = ref(false)
const serialDeleting = ref(false)
const deletingSerial = ref<Serial | null>(null)
const serialForm = ref({ name: '', status: 'active' })
const serialsLoading = ref(false)

// Genealogy State
const showGenealogy = ref(false)
const genealogy = ref<any>({})
const loadingGenealogy = ref(false)

// Shared
const products = computed(() => masterStore.products)

// Lots Functions
async function fetchLots(page = 1) {
  lotsLoading.value = true
  try {
    const [lotRes] = await Promise.all([
      $api<PaginatedResponse<Lot>>(`/lots?page=${page}`),
      masterStore.fetchProducts(),
    ])
    lots.value = lotRes.data || []
    totalLots.value = lotRes.meta?.counts?.total || lotRes.counts?.total || lotRes.meta?.total || lotRes.total || 0
    lotPageSize.value = lotRes.meta?.per_page || lotRes.per_page || 10
    lotCurrentPage.value = lotRes.meta?.current_page || lotRes.current_page || 1
  } catch (e) {
    toast.error('Failed to fetch lots')
  } finally {
    lotsLoading.value = false
  }
}

function openLotModal(lot?: Lot) {
  if (lot) {
    editingLot.value = lot
    lotForm.value = { 
      name: lot.name, 
      product_id: lot.product_id, 
      expiry_date: lot.expiry_date || '', 
      initial_qty: lot.initial_qty || 0 
    }
  } else {
    editingLot.value = null
    lotForm.value = { name: '', product_id: null, expiry_date: '', initial_qty: 0 }
  }
  showLotModal.value = true
}

async function saveLot() {
  lotSaving.value = true
  try {
    if (editingLot.value) {
      await $api(`/lots/${editingLot.value.id}`, { method: 'PUT', body: lotForm.value })
      toast.success('Lot updated successfully')
    } else {
      await $api('/lots', { method: 'POST', body: lotForm.value })
      toast.success('Lot created successfully')
    }
    showLotModal.value = false
    await fetchLots(lotCurrentPage.value)
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to save lot')
  } finally {
    lotSaving.value = false
  }
}

function confirmDeleteLot(lot: Lot) {
  deletingLot.value = lot
  showDeleteLotModal.value = true
}

async function handleDeleteLot() {
  if (!deletingLot.value) return
  lotDeleting.value = true
  try {
    await $api(`/lots/${deletingLot.value.id}`, { method: 'DELETE' })
    toast.success('Lot deleted')
    showDeleteLotModal.value = false
    await fetchLots(lotCurrentPage.value)
  } catch (e) {
    toast.error('Failed to delete lot')
  } finally {
    lotDeleting.value = false
    deletingLot.value = null
  }
}

// Serials Functions
async function fetchSerials(page = 1) {
  serialsLoading.value = true
  try {
    const res = await $api<PaginatedResponse<Serial>>(`/serials?page=${page}`)
    serials.value = res.data || []
    totalSerials.value = res.meta?.total || res.total || 0
    serialPageSize.value = res.meta?.per_page || res.per_page || 10
    serialCurrentPage.value = res.meta?.current_page || res.current_page || 1
  } catch (e) {
    toast.error('Failed to fetch serials')
  } finally {
    serialsLoading.value = false
  }
}

function openSerialModal(serial: Serial) {
  editingSerial.value = serial
  serialForm.value = { name: serial.name, status: serial.status }
  showSerialModal.value = true
}

async function saveSerial() {
  if (!editingSerial.value) return
  serialSaving.value = true
  try {
    await $api(`/serials/${editingSerial.value.id}`, { method: 'PUT', body: serialForm.value })
    toast.success('Serial updated')
    showSerialModal.value = false
    fetchSerials(serialCurrentPage.value)
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to update serial')
  } finally {
    serialSaving.value = false
  }
}

function confirmDeleteSerial(serial: Serial) {
  deletingSerial.value = serial
  showDeleteSerialModal.value = true
}

async function handleDeleteSerial() {
  if (!deletingSerial.value) return
  serialDeleting.value = true
  try {
    await $api(`/serials/${deletingSerial.value.id}`, { method: 'DELETE' })
    toast.success('Serial deleted')
    showDeleteSerialModal.value = false
    fetchSerials(serialCurrentPage.value)
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to delete serial')
  } finally {
    serialDeleting.value = false
    deletingSerial.value = null
  }
}

async function viewGenealogy(serial: Serial) {
  genealogy.value = {}
  showGenealogy.value = true
  loadingGenealogy.value = true
  try {
    const res = await $api<any>(`/serials/${serial.id}/genealogy`)
    genealogy.value = res // The backend now returns the root node object directly
  } catch (e) {
    toast.error('Failed to fetch genealogy')
  } finally {
    loadingGenealogy.value = false
  }
}

// Watchers
watch(lotCurrentPage, (newPage) => fetchLots(newPage))
watch(serialCurrentPage, (newPage) => fetchSerials(newPage))
watch(activeTab, (tab) => {
  if (tab === 'lots' && lots.value.length === 0) {
    fetchLots(1)
  } else if (tab === 'serials' && serials.value.length === 0) {
    fetchSerials(1)
  }
})

onMounted(() => fetchLots(1))
</script>
