<template>
  <div class="space-y-6">
    <div class="page-header">
      <div>
        <h1 class="page-title">Production Problems</h1>
        <p class="text-gray-500 mt-1 hidden sm:block">Track scrap, material losses, and disassemble products</p>
      </div>
      <div class="flex gap-2">
        <button v-if="activeTab === 'scrap'" class="btn-primary" @click="openScrapModal()">
          <Icon name="heroicons:plus" class="w-4 h-4" />
          <span class="hidden sm:inline">Record Scrap</span>
          <span class="sm:hidden">Scrap</span>
        </button>
        <button v-if="activeTab === 'variances'" class="btn-primary" @click="openLossModal()">
          <Icon name="heroicons:plus" class="w-4 h-4" />
          <span class="hidden sm:inline">Record Loss</span>
          <span class="sm:hidden">Loss</span>
        </button>
        <button v-if="activeTab === 'unbuild'" class="btn-primary" @click="openUnbuildModal()">
          <Icon name="heroicons:plus" class="w-4 h-4" />
          <span class="hidden sm:inline">Unbuild Order</span>
          <span class="sm:hidden">Unbuild</span>
        </button>
      </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-2 border-b border-gray-200 overflow-x-auto scrollbar-hide -mx-4 px-4 md:mx-0 md:px-0">
      <button
        @click="activeTab = 'scrap'"
        :class="[
          'px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors whitespace-nowrap',
          activeTab === 'scrap'
            ? 'border-primary-600 text-primary-600'
            : 'border-transparent text-gray-500 hover:text-gray-700'
        ]"
      >
        Scrap Reports 
        <span :class="['ml-1 text-xs px-1.5 py-0.5 rounded-full', activeTab === 'scrap' ? 'bg-gray-100 text-gray-800' : 'bg-gray-100 text-gray-600']">{{ scraps.length }}</span>
      </button>
      <button
        @click="activeTab = 'variances'"
        :class="[
          'px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors whitespace-nowrap',
          activeTab === 'variances'
            ? 'border-primary-600 text-primary-600'
            : 'border-transparent text-gray-500 hover:text-gray-700'
        ]"
      >
        Manufacturing Losses
        <span :class="['ml-1 text-xs px-1.5 py-0.5 rounded-full', activeTab === 'variances' ? 'bg-gray-100 text-gray-800' : 'bg-gray-100 text-gray-600']">{{ variances.length }}</span>
      </button>
       <button
        @click="activeTab = 'unbuild'"
        :class="[
          'px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors whitespace-nowrap',
          activeTab === 'unbuild'
            ? 'border-primary-600 text-primary-600'
            : 'border-transparent text-gray-500 hover:text-gray-700'
        ]"
      >
        Unbuild Orders
        <span :class="['ml-1 text-xs px-1.5 py-0.5 rounded-full', activeTab === 'unbuild' ? 'bg-gray-100 text-gray-800' : 'bg-gray-100 text-gray-600']">{{ unbuildOrders.length }}</span>
      </button>
    </div>

    <!-- Scrap Tab -->
    <div v-if="activeTab === 'scrap'" class="space-y-6">
      <!-- Summary Stats -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="card bg-red-50 border-red-200">
          <p class="text-xs text-red-600 uppercase font-medium">Total Scrap Records</p>
          <p class="text-2xl font-bold text-red-700">{{ scraps.length }}</p>
        </div>
        <div class="card bg-red-50 border-red-200">
          <p class="text-xs text-red-600 uppercase font-medium">Total Qty Scrapped</p>
          <p class="text-2xl font-bold text-red-700">{{ totalScrapQty }}</p>
        </div>
        <div class="card bg-red-50 border-red-200">
          <p class="text-xs text-red-600 uppercase font-medium">Scrap Cost</p>
          <p class="text-2xl font-bold text-red-700">{{ formatCurrency(totalScrapCost) }}</p>
        </div>
        <div class="card bg-gray-50">
          <p class="text-xs text-gray-600 uppercase font-medium">Most Common Reason</p>
          <p class="text-lg font-semibold text-gray-800 truncate">{{ topScrapReason || '-' }}</p>
        </div>
      </div>

      <!-- Table -->
      <div class="card p-0 overflow-hidden">
        <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Product</th>
              <th>Quantity</th>
              <th>Location</th>
              <th>Cost</th>
              <th>Reason</th>
              <th>MO</th>
              <th>Date</th>
              <th class="w-16">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="scrap in paginatedScraps" :key="scrap.id">
              <td class="font-mono text-sm">#{{ scrap.id }}</td>
              <td>
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden flex-shrink-0">
                    <img v-if="scrap.product?.image_url" :src="getImageUrl(scrap.product?.image_url)" class="w-full h-full object-cover" />
                    <Icon v-else name="heroicons:cube" class="w-5 h-5 text-gray-400" />
                  </div>
                  <span class="font-medium">{{ scrap.product?.name || 'N/A' }}</span>
                </div>
              </td>
              <td class="text-red-600 font-medium">-{{ Number(scrap.quantity) }}</td>
              <td>{{ scrap.location?.name || '-' }}</td>
              <td class="text-red-600 font-medium">{{ formatCurrency(getScrapCost(scrap)) }}</td>
              <td>{{ scrap.reason }}</td>
              <td>{{ scrap.manufacturing_order_id ? `MO #${scrap.manufacturing_order_id}` : '-' }}</td>
              <td class="text-sm text-gray-500">{{ formatDate(scrap.created_at) }}</td>
              <td>
                <div class="flex items-center gap-2">
                  <UiIconButton
                    @click="openScrapModal(scrap)"
                    icon="heroicons:pencil"
                    tooltip="Edit Scrap"
                  />
                  <UiIconButton
                    @click="confirmDelete('scrap', scrap)"
                    icon="heroicons:trash"
                    tooltip="Delete Scrap"
                    color="text-red-400 hover:text-red-600"
                  />
                </div>
              </td>
            </tr>
            <tr v-if="scraps.length === 0">
              <td colspan="9">
                <UiEmptyState 
                  title="No scrap records" 
                  description="Record scrap when materials are discarded during production."
                  icon="heroicons:trash"
                >
                  <button class="btn-primary" @click="openScrapModal()">
                    <Icon name="heroicons:plus" class="w-4 h-4" />
                    Record Scrap
                  </button>
                </UiEmptyState>
              </td>
            </tr>
          </tbody>
        </table>
        </div>
        <UiPagination v-model="currentPage" :total-items="scrapTotal" :page-size="pageSize" />
      </div>
    </div>

    <!-- Variances Tab -->
    <div v-if="activeTab === 'variances'" class="space-y-6">
      <!-- Summary Stats -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="card bg-orange-50 border-orange-200">
          <p class="text-xs text-orange-600 uppercase font-medium">Total Loss Records</p>
          <p class="text-2xl font-bold text-orange-700">{{ variances.length }}</p>
        </div>
        <div class="card bg-orange-50 border-orange-200">
          <p class="text-xs text-orange-600 uppercase font-medium">Over Consumption</p>
          <p class="text-2xl font-bold text-orange-700">{{ overConsumptionCount }}</p>
        </div>
        <div class="card bg-orange-50 border-orange-200">
          <p class="text-xs text-orange-600 uppercase font-medium">Total Cost Impact</p>
          <p class="text-2xl font-bold text-orange-700">{{ formatCurrency(totalVarianceCost) }}</p>
        </div>
        <div class="card bg-green-50 border-green-200">
          <p class="text-xs text-green-600 uppercase font-medium">Savings (Under)</p>
          <p class="text-2xl font-bold text-green-700">{{ formatCurrency(Math.abs(totalSavings)) }}</p>
        </div>
      </div>

      <!-- Table -->
      <div class="card p-0 overflow-hidden">
        <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>MO</th>
              <th>Product</th>
              <th>Planned</th>
              <th>Consumed</th>
              <th>Variance</th>
              <th>Cost Impact</th>
              <th>Date</th>
              <th class="w-16">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="variance in paginatedVariances" :key="variance.id">
              <td class="font-mono text-sm">MO #{{ variance.manufacturing_order_id }}</td>
              <td>
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden flex-shrink-0">
                    <img v-if="variance.product?.image_url" :src="getImageUrl(variance.product?.image_url)" class="w-full h-full object-cover" />
                    <Icon v-else name="heroicons:cube" class="w-5 h-5 text-gray-400" />
                  </div>
                  <span class="font-medium">{{ variance.product?.name || 'N/A' }}</span>
                </div>
              </td>
              <td>{{ Number(variance.qty_planned) }}</td>
              <td>{{ Number(variance.qty_consumed) }}</td>
              <td :class="Number(variance.variance) > 0 ? 'text-red-600 font-medium' : 'text-green-600 font-medium'">
                {{ Number(variance.variance) > 0 ? '+' : '' }}{{ Number(variance.variance) }}
              </td>
              <td :class="Number(variance.cost_impact) > 0 ? 'font-medium text-red-600' : 'font-medium text-green-600'">
                 {{ formatCurrency(variance.cost_impact) }}
              </td>
              <td class="text-sm text-gray-500">{{ formatDate(variance.created_at) }}</td>
              <td>
                <div class="flex items-center gap-2">
                  <UiIconButton
                    @click="openLossModal(variance)"
                    icon="heroicons:pencil"
                    tooltip="Edit Variance"
                  />
                  <UiIconButton
                    @click="confirmDelete('variance', variance)"
                    icon="heroicons:trash"
                    tooltip="Delete Variance"
                    color="text-red-400 hover:text-red-600"
                  />
                </div>
              </td>
            </tr>
            <tr v-if="variances.length === 0">
              <td colspan="7">
                <UiEmptyState 
                  title="No material variances found" 
                  description="Material variances are automatically recorded when consumption differs from planned."
                  icon="heroicons:scale"
                />
              </td>
            </tr>
          </tbody>
        </table>
        </div>
        <UiPagination v-model="variancePage" :total-items="varianceTotal" :page-size="pageSize" />
      </div>
    </div>

    <!-- Unbuild Orders Tab -->
    <div v-if="activeTab === 'unbuild'" class="space-y-6">
      <!-- Summary Stats -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="card bg-purple-50 border-purple-200">
          <p class="text-xs text-purple-600 uppercase font-medium">Total Orders</p>
          <p class="text-2xl font-bold text-purple-700">{{ unbuildOrders.length }}</p>
        </div>
        <div class="card bg-purple-50 border-purple-200">
          <p class="text-xs text-purple-600 uppercase font-medium">Pending</p>
          <p class="text-2xl font-bold text-purple-700">{{ unbuildPendingCount }}</p>
        </div>
        <div class="card bg-purple-50 border-purple-200">
          <p class="text-xs text-purple-600 uppercase font-medium">Completed</p>
          <p class="text-2xl font-bold text-purple-700">{{ unbuildCompletedCount }}</p>
        </div>
         <div class="card bg-gray-50 border-gray-200">
          <p class="text-xs text-gray-600 uppercase font-medium">Total Qty Unbuilt</p>
          <p class="text-2xl font-bold text-gray-700">{{ totalUnbuildQty }}</p>
        </div>
      </div>

       <!-- Table -->
       <div class="card p-0 overflow-hidden">
        <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Order #</th>
              <th>Product</th>
              <th>Quantity</th>
              <th>BOM</th>
              <th>Source MO</th>
              <th>Status</th>
              <th>Date</th>
              <th class="w-16">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="order in paginatedUnbuildOrders" :key="order.id">
              <td class="font-medium">{{ order.name }}</td>
              <td>{{ order.product?.name || 'N/A' }}</td>
              <td>{{ Number(order.quantity) }}</td>
              <td>{{ order.bom ? `BOM #${order.bom.id}` : '-' }}</td>
              <td>{{ order.manufacturing_order_id ? `#${order.manufacturing_order_id}` : '-' }}</td>
              <td>
                <UiStatusBadge :status="order.status || 'done'" />
              </td>
              <td class="text-sm text-gray-500">{{ formatDate(order.created_at) }}</td>
              <td>
                <div class="flex items-center gap-2">
                  <UiIconButton
                    @click="openUnbuildModal(order)"
                    icon="heroicons:pencil"
                    tooltip="Edit Unbuild Order"
                  />
                  <UiIconButton
                    @click="confirmDelete('unbuild', order)"
                    icon="heroicons:trash"
                    tooltip="Delete Unbuild Order"
                    color="text-red-400 hover:text-red-600"
                  />
                </div>
              </td>
            </tr>
            <tr v-if="unbuildOrders.length === 0">
              <td colspan="8">
                <UiEmptyState 
                  title="No unbuild orders" 
                  description="Create an unbuild order to disassemble finished goods back into components."
                  icon="heroicons:arrow-path"
                >
                  <button class="btn-primary" @click="openUnbuildModal()">
                    <Icon name="heroicons:plus" class="w-4 h-4" />
                    New Unbuild Order
                  </button>
                </UiEmptyState>
              </td>
            </tr>
          </tbody>
        </table>
        </div>
        <UiPagination v-model="unbuildPage" :total-items="unbuildTotal" :page-size="pageSize" />
      </div>
    </div>

    <!-- Record Scrap SlideOver -->
    <UiSlideOver v-model="showScrapModal" :title="editingScrap ? 'Edit Scrap Record' : 'Record Scrap'">
      <form @submit.prevent="saveScrap" class="space-y-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Product</label>
          <UiSearchableSelect
            v-model="scrapForm.product_id"
            :options="products.map(p => ({ label: p.name, value: p.id }))"
            placeholder="Select product..."
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
          <UiSearchableSelect
            v-model="scrapForm.location_id"
            :options="scrapForm.product_id 
              ? productStocks.map(s => ({ label: `${s.location?.name || 'Unknown'} (Qty: ${String(s.quantity)})`, value: s.location_id })) 
              : locations.map(l => ({ label: l.name, value: l.id }))"
            placeholder="Select location..."
          />
          <p v-if="scrapForm.product_id" class="text-xs text-gray-500 mt-1">Showing locations where product is in stock</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
          <input v-model.number="scrapForm.quantity" type="number" min="0.0001" step="0.0001" class="input" required :disabled="!!editingScrap" />
          <p v-if="editingScrap" class="text-xs text-gray-500 mt-1">Quantity cannot be edited to preserve stock integrity.</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
          <input v-model="scrapForm.reason" type="text" class="input" required />
        </div>
        <div class="flex justify-end gap-3 mt-6">
          <button type="button" @click="showScrapModal = false" class="btn-ghost">Cancel</button>
          <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Saving...' : 'Save' }}</button>
        </div>
      </form>
    </UiSlideOver>

    <!-- Record Loss SlideOver -->
    <UiSlideOver v-model="showLossModal" :title="editingLoss ? 'Edit Material Loss' : 'Record Material Loss'">
      <form @submit.prevent="saveLoss" class="space-y-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Manufacturing Order</label>
          <UiSearchableSelect
            v-model="lossForm.manufacturing_order_id"
            :options="manufacturingOrders.map(mo => ({ label: `${mo.name} - ${mo.product?.name}`, value: mo.id }))"
            placeholder="Select MO..."
           />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Product</label>
          <UiSearchableSelect
            v-model="lossForm.product_id"
            :options="lossForm.manufacturing_order_id && moComponents.length > 0
                ? moComponents.map(p => ({ label: p.name, value: p.id }))
                : products.map(p => ({ label: p.name, value: p.id }))"
            placeholder="Select product..."
          />
          <p v-if="lossForm.manufacturing_order_id" class="text-xs text-gray-500 mt-1">
            {{ moComponents.length > 0 ? 'Showing components from BOM' : 'Select standard components' }}
          </p>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Planned Qty</label>
            <input v-model.number="lossForm.qty_planned" type="number" min="0" step="0.0001" class="input" required />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Actual Consumed</label>
            <input v-model.number="lossForm.qty_consumed" type="number" min="0" step="0.0001" class="input" required />
          </div>
        </div>
        <div class="p-3 bg-gray-50 rounded-lg">
          <p class="text-sm text-gray-500">Variance: <span :class="lossVariance > 0 ? 'text-red-600 font-medium' : 'text-green-600 font-medium'">{{ lossVariance > 0 ? '+' : '' }}{{ lossVariance }}</span></p>
        </div>
        <div class="flex justify-end gap-3 mt-6">
          <button type="button" @click="showLossModal = false" class="btn-ghost">Cancel</button>
          <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Saving...' : 'Save' }}</button>
        </div>
      </form>
    </UiSlideOver>

    <!-- Create Unbuild Order SlideOver -->
    <UiSlideOver v-model="showUnbuildModal" :title="editingUnbuild ? 'Edit Unbuild Order' : 'Create Unbuild Order'">
      <form @submit.prevent="saveUnbuild" class="space-y-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Product to Unbuild</label>
          <UiSearchableSelect
            v-model="unbuildForm.product_id"
            :options="finishedGoods.map(p => ({ label: p.name, value: p.id }))"
            placeholder="Select product..."
          />
          <p class="text-xs text-gray-500 mt-1">Only finished goods can be unbuilt</p>
        </div>

        <div v-if="unbuildForm.product_id">
            <label class="block text-sm font-medium text-gray-700 mb-1">BOM</label>
            <UiSearchableSelect
                v-model="unbuildForm.bom_id"
                :options="productBoms.map(b => ({ label: `BOM #${b.id} (${b.type})`, value: b.id }))"
                placeholder="Select BOM..."
            />
            <p v-if="productBoms.length === 0" class="text-xs text-amber-600 mt-1">No active BOMs found for this product.</p>
        </div>

        <div>
           <label class="block text-sm font-medium text-gray-700 mb-1">Original MO (Optional)</label>
           <UiSearchableSelect
             v-model="unbuildForm.manufacturing_order_id"
             :options="manufacturingOrders.map(mo => ({ label: `${mo.name} - ${mo.product?.name}`, value: mo.id }))"
             placeholder="Select original MO (optional)..."
           />
           <p class="text-xs text-gray-500 mt-1">Link to original MO if known</p>
        </div>

        <div>
           <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
           <input v-model.number="unbuildForm.quantity" type="number" min="0.0001" step="0.0001" class="input" required />
        </div>

        <div>
           <label class="block text-sm font-medium text-gray-700 mb-1">Reason (Optional)</label>
           <input v-model="unbuildForm.reason" type="text" class="input" placeholder="e.g. Defect found in QA" />
        </div>

        <div class="flex justify-end gap-3 mt-6">
          <button type="button" @click="showUnbuildModal = false" class="btn-ghost">Cancel</button>
          <button type="submit" class="btn-primary" :disabled="saving">
            {{ saving ? 'Saving...' : 'Create Order' }}
          </button>
        </div>
      </form>
    </UiSlideOver>

    <!-- Delete Confirmation Modal -->
    <UiConfirmModal
      v-model="showDeleteModal"
      title="Delete Record"
      :message="`Are you sure you want to delete this ${deletingType}? This action cannot be undone.`"
      confirm-text="Delete"
      variant="danger"
      :loading="deleting"
      @confirm="executeDelete"
    />
  </div>

</template>

<script setup lang="ts">
import type { ManufacturingOrder, Scrap, Consumption, UnbuildOrder, Product, Bom, Stock } from '~/types/models'

const { $api } = useApi()
const toast = useToast()
const masterStore = useMasterStore()
const { formatDate, formatCurrency, getImageUrl } = useUtils()

const activeTab = ref('scrap')
const scraps = ref<Scrap[]>([])
const variances = ref<Consumption[]>([])
const unbuildOrders = ref<UnbuildOrder[]>([])
const manufacturingOrders = ref<ManufacturingOrder[]>([])

const products = computed(() => masterStore.products)
const locations = computed(() => masterStore.locations)

// Modals
const showScrapModal = ref(false)
const showLossModal = ref(false)
const showUnbuildModal = ref(false)
const saving = ref(false)
const editingLoss = ref<Consumption | null>(null)
const editingScrap = ref<Scrap | null>(null)
const editingUnbuild = ref<UnbuildOrder | null>(null)

// Dependent Data
const productBoms = ref<Bom[]>([])
const productStocks = ref<Stock[]>([])
const moComponents = ref<Product[]>([])

const finishedGoods = computed(() => products.value.filter(p => p.type === 'finished'))

// Pagination & Totals
const currentPage = ref(1)
const variancePage = ref(1)
const unbuildPage = ref(1)
const pageSize = 10

const scrapTotal = ref(0)
const varianceTotal = ref(0)
const unbuildTotal = ref(0)

// Stats from Backend
const stats = ref({
    scrap: { count: 0, quantity: 0, cost: 0, top_reason: '' },
    variance: { count: 0, cost: 0, savings: 0, over_consumption_count: 0 },
    unbuild: { count: 0, pending: 0, completed: 0, quantity: 0 }
})

// Forms
const scrapForm = ref({ product_id: null as number | null, location_id: null as number | null, quantity: 1, reason: '' })
const lossForm = ref({ manufacturing_order_id: null as number | null, product_id: null as number | null, qty_planned: 0, qty_consumed: 0 })
const unbuildForm = ref({ product_id: null as number | null, bom_id: null as number | null, manufacturing_order_id: null as number | null, quantity: 1, reason: '' })

const lossVariance = computed(() => lossForm.value.qty_consumed - lossForm.value.qty_planned)

// Watchers for dependent filtering
watch(() => scrapForm.value.product_id, async (newVal) => {
    if (newVal && !editingScrap.value) {
        scrapForm.value.location_id = null
        try {
            const res = await $api<{ data: Stock[] }>(`/stocks?product_id=${newVal}`)
            productStocks.value = res.data || []
        } catch (e) {
            console.error('Failed to fetch product stocks', e)
            productStocks.value = []
        }
    } else if (!newVal) {
      productStocks.value = []
    }
})

watch(() => lossForm.value.manufacturing_order_id, async (newVal) => {
    if (newVal) {
         if (!editingLoss.value) lossForm.value.product_id = null
         const mo = manufacturingOrders.value.find(m => m.id === newVal)
         if (mo && mo.product_id) {
             try {
                 let bomId = mo.bom_id
                 if (!bomId) {
                     const res = await $api<{ data: Bom[] }>(`/boms?product_id=${mo.product_id}&is_active=1`)
                     const boms = res?.data
                     const firstBom = boms?.[0]
                     if (firstBom) bomId = firstBom.id
                 }

                 if (bomId) {
                     const bomRes = await $api<Bom>(`/boms/${bomId}`)
                     if (bomRes && bomRes.lines) {
                         moComponents.value = bomRes.lines.map(line => line.product).filter((p): p is Product => !!p)
                     } else {
                         moComponents.value = []
                     }
                 }
             } catch (e) {
                 console.error('Failed to fetch MO components', e)
                 moComponents.value = []
             }
         }
    } else {
        moComponents.value = []
    }
})

watch(() => unbuildForm.value.product_id, async (newVal) => {
    if (newVal) {
        if (!editingUnbuild.value) unbuildForm.value.bom_id = null
        try {
            const res = await $api<{ data: Bom[] }>(`/boms?product_id=${newVal}&is_active=1`)
            productBoms.value = res.data || []
            if (productBoms.value.length === 1 && !unbuildForm.value.bom_id) {
                const firstBom = productBoms.value[0];
                if (firstBom) {
                    unbuildForm.value.bom_id = firstBom.id
                }
            }
        } catch (e) {
            console.error('Failed to fetch BOMs', e)
            productBoms.value = []
        }
    } else {
        productBoms.value = []
    }
})

// Pagination Watchers
watch(currentPage, () => fetchScraps())
watch(variancePage, () => fetchVariances())
watch(unbuildPage, () => fetchUnbuilds())


async function fetchStats() {
    try {
        const res = await $api<any>('/reporting/production-problems')
        stats.value = res
    } catch (e) {
        console.error('Failed to fetch stats', e)
    }
}

async function fetchScraps() {
    try {
        const res = await $api<{ data: Scrap[], total: number }>(`/scraps?page=${currentPage.value}&per_page=${pageSize}`)
        scraps.value = res.data || []
        scrapTotal.value = res.total || res.data.length 
        if ((res as any).meta) {
            scrapTotal.value = (res as any).meta.total
        } else if ((res as any).total) {
            scrapTotal.value = (res as any).total
        }
    } catch (e) {
        toast.error('Failed to fetch scraps')
    }
}

async function fetchVariances() {
    try {
        const res = await $api<{ data: Consumption[], meta?: any }>(`/consumptions?has_variance=true&page=${variancePage.value}&per_page=${pageSize}`)
        variances.value = res.data || []
        if (res.meta) varianceTotal.value = res.meta.total
    } catch (e) {
        console.error(e)
    }
}

async function fetchUnbuilds() {
    try {
        const res = await $api<{ data: UnbuildOrder[], meta?: any }>(`/unbuild-orders?page=${unbuildPage.value}&per_page=${pageSize}`)
        unbuildOrders.value = res.data || []
        if (res.meta) unbuildTotal.value = res.meta.total
    } catch (e) {
        console.error(e)
    }
}


async function fetchData() {
  try {
    await Promise.all([
      fetchScraps(),
      fetchVariances(),
      fetchUnbuilds(),
      fetchStats(),
      $api<{ data: ManufacturingOrder[] }>('/manufacturing-orders').then(res => manufacturingOrders.value = res.data || []),
      masterStore.fetchProducts(),
      masterStore.fetchLocations(),
    ])
  } catch (e) {
    toast.error('Failed to fetch data')
  }
}

function openScrapModal(scrap?: Scrap) {
    if (scrap) {
        editingScrap.value = scrap
        scrapForm.value = {
            product_id: scrap.product_id,
            location_id: scrap.location_id || null,
            quantity: scrap.quantity,
            reason: scrap.reason
        }
        if (scrap.product_id) {
             $api<{ data: Stock[] }>(`/stocks?product_id=${scrap.product_id}`).then(res => {
                productStocks.value = res.data || []
             })
        }
    } else {
        editingScrap.value = null
        scrapForm.value = { product_id: null, location_id: null, quantity: 1, reason: '' }
        productStocks.value = []
    }
    showScrapModal.value = true
}

async function saveScrap() {
  saving.value = true
  try {
    if (editingScrap.value) {
        await $api(`/scraps/${editingScrap.value.id}`, { method: 'PUT', body: scrapForm.value })
        toast.success('Scrap record updated')
    } else {
        await $api('/scraps', { method: 'POST', body: scrapForm.value })
        toast.success('Scrap recorded successfully')
    }
    showScrapModal.value = false
    await Promise.all([fetchScraps(), fetchStats()])
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to record scrap')
  } finally {
    saving.value = false
  }
}

function openLossModal(variance?: Consumption) {
    if (variance) {
        editingLoss.value = variance
        lossForm.value = {
            manufacturing_order_id: variance.manufacturing_order_id,
            product_id: variance.product?.id || null,
            qty_planned: variance.qty_planned,
            qty_consumed: variance.qty_consumed
        }
        if (variance.manufacturing_order_id) {
            const mo = manufacturingOrders.value.find(m => m.id === variance.manufacturing_order_id)
             if (mo && mo.bom_id) {
                 $api<Bom>(`/boms/${mo.bom_id}`).then(bomRes => {
                     if (bomRes && bomRes.lines) {
                         moComponents.value = bomRes.lines.map(line => line.product).filter((p): p is Product => !!p)
                     }
                 })
             }
        }
    } else {
        editingLoss.value = null
        lossForm.value = { manufacturing_order_id: null, product_id: null, qty_planned: 0, qty_consumed: 0 }
        moComponents.value = []
    }
    showLossModal.value = true
}

async function saveLoss() {
  saving.value = true
  try {
    if (editingLoss.value) {
        await $api(`/consumptions/${editingLoss.value.id}`, { method: 'PUT', body: lossForm.value })
        toast.success('Loss updated successfully')
    } else {
        await $api('/consumptions', { method: 'POST', body: lossForm.value })
        toast.success('Loss recorded successfully')
    }
    showLossModal.value = false
     await Promise.all([fetchVariances(), fetchStats()])
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to record loss')
  } finally {
    saving.value = false
  }
}

function openUnbuildModal(order?: UnbuildOrder) {
    if (order) {
        editingUnbuild.value = order
        unbuildForm.value = {
            product_id: order.product_id,
            bom_id: order.bom_id || null,
            manufacturing_order_id: order.manufacturing_order_id || null,
            quantity: order.quantity,
            reason: order.reason || ''
        }
        if (order.product_id) {
            $api<{ data: Bom[] }>(`/boms?product_id=${order.product_id}&is_active=1`).then(res => {
                productBoms.value = res.data || []
            })
        }
    } else {
        editingUnbuild.value = null
        unbuildForm.value = { product_id: null, bom_id: null, manufacturing_order_id: null, quantity: 1, reason: '' }
        productBoms.value = []
    }
    showUnbuildModal.value = true
}

async function saveUnbuild() {
  if (!unbuildForm.value.product_id || !unbuildForm.value.bom_id) return
  saving.value = true
  try {
    if (editingUnbuild.value) {
        await $api(`/unbuild-orders/${editingUnbuild.value.id}`, { method: 'PUT', body: unbuildForm.value })
        toast.success('Unbuild order updated')
    } else {
        await $api('/unbuild-orders', { method: 'POST', body: unbuildForm.value }) 
        toast.success('Unbuild order created')
    }
    showUnbuildModal.value = false
    await Promise.all([fetchUnbuilds(), fetchStats()])
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to create unbuild order')
  } finally {
    saving.value = false
  }
}

function getScrapCost(scrap: Scrap): number {
  return Number(scrap.quantity) * (scrap.product?.cost ?? 0)
}

// Client-side pagination logic removed - using direct array from offset
// Just return the array as is, since it's already a single page
const paginatedScraps = computed(() => scraps.value)
const paginatedVariances = computed(() => variances.value)
const paginatedUnbuildOrders = computed(() => unbuildOrders.value)

// Stats are now from backend
const totalScrapQty = computed(() => stats.value.scrap.quantity)
const totalScrapCost = computed(() => stats.value.scrap.cost)
const topScrapReason = computed(() => stats.value.scrap.top_reason)

const overConsumptionCount = computed(() => stats.value.variance.over_consumption_count)
const totalVarianceCost = computed(() => stats.value.variance.cost)
const totalSavings = computed(() => stats.value.variance.savings)

const unbuildPendingCount = computed(() => stats.value.unbuild.pending)
const unbuildCompletedCount = computed(() => stats.value.unbuild.completed)
const totalUnbuildQty = computed(() => stats.value.unbuild.quantity)


const deletingType = ref('')
const deletingItem = ref<any>(null)
const showDeleteModal = ref(false)
const deleting = ref(false)

async function executeDelete() {
  if (!deletingItem.value) return
  deleting.value = true
  try {
    if (deletingType.value === 'scrap') {
        await $api(`/scraps/${deletingItem.value.id}`, { method: 'DELETE' })
        await Promise.all([fetchScraps(), fetchStats()])
    } else if (deletingType.value === 'variance') {
        await $api(`/consumptions/${deletingItem.value.id}`, { method: 'DELETE' })
        await Promise.all([fetchVariances(), fetchStats()])
    } else if (deletingType.value === 'unbuild') {
        await $api(`/unbuild-orders/${deletingItem.value.id}`, { method: 'DELETE' })
        await Promise.all([fetchUnbuilds(), fetchStats()])
    }
    toast.success('Record deleted')
    showDeleteModal.value = false
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to delete record')
  } finally {
    deleting.value = false
    deletingItem.value = null
  }
}
// Fix confirmDelete to match template usage
function confirmDelete(type: string, item: any) {
  deletingType.value = type
  deletingItem.value = item
  showDeleteModal.value = true
}


onMounted(fetchData)
</script>
