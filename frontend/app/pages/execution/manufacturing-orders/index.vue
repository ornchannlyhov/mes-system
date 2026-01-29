<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Manufacturing Orders</h1>
        <p class="text-gray-500 mt-1 hidden sm:block">Track and manage production orders</p>
      </div>
      <div class="flex gap-2">
        <button 
          @click="viewMode = viewMode === 'table' ? 'calendar' : 'table'" 
          class="btn-outline"
        >
          <Icon :name="viewMode === 'table' ? 'heroicons:calendar' : 'heroicons:list-bullet'" class="w-4 h-4" />
          <span class="hidden sm:inline">{{ viewMode === 'table' ? 'Calendar' : 'Table' }}</span>
        </button>
        <button class="btn-primary" @click="openModal()">
          <Icon name="heroicons:plus" class="w-4 h-4" />
          <span class="hidden sm:inline">New Order</span>
          <span class="sm:hidden">New</span>
        </button>
      </div>
    </div>

    <!-- Status Tabs -->
    <div class="flex gap-2 border-b border-gray-200 overflow-x-auto scrollbar-hide -mx-4 px-4 md:mx-0 md:px-0">
      <button 
        @click="filters.status = ''" 
        :class="['px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors whitespace-nowrap', filters.status === '' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700']"
      >
        All <span :class="['ml-1 text-xs px-1.5 py-0.5 rounded-full', filters.status === '' ? 'bg-gray-100 text-gray-800' : 'bg-gray-100 text-gray-600']">{{ counts.all || total }}</span>
      </button>
      <button 
        v-for="status in ['draft', 'confirmed', 'in_progress', 'done', 'scheduled']" 
        :key="status"
        @click="filters.status = status" 
        :class="['px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors capitalize whitespace-nowrap', filters.status === status ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700']"
      >
        {{ status.replace('_', ' ') }} 
        <span 
          :class="[
            'ml-1 text-xs px-1.5 py-0.5 rounded-full', 
            status === 'draft' ? 'bg-gray-100 text-gray-700' :
            status === 'confirmed' ? 'bg-blue-100 text-blue-700' :
            status === 'in_progress' ? 'bg-orange-100 text-orange-700' :
            status === 'done' ? 'bg-green-100 text-green-700' :
            'bg-purple-100 text-purple-700'
          ]"
        >
          {{ counts[status] || 0 }}
        </span>
      </button>
    </div>

    <!-- Search -->
    <div v-if="viewMode === 'table'" class="flex gap-4">
      <input v-model="search" type="text" placeholder="Search orders..." class="input max-w-xs" />
    </div>

    <!-- Calendar View -->
    <div v-if="viewMode === 'calendar'" class="card p-6">
      <div class="flex items-center justify-between mb-4">
        <button @click="changeMonth(-1)" class="btn-ghost">
          <Icon name="heroicons:chevron-left" class="w-5 h-5" />
        </button>
        <h2 class="text-xl font-semibold">{{ currentMonthName }} {{ currentYear }}</h2>
        <button @click="changeMonth(1)" class="btn-ghost">
          <Icon name="heroicons:chevron-right" class="w-5 h-5" />
        </button>
      </div>

      <div class="grid grid-cols-7 gap-px bg-gray-200 border border-gray-200 rounded-lg overflow-hidden">
        <div v-for="day in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']" :key="day" class="bg-gray-50 p-2 text-center text-sm font-medium text-gray-700">
          {{ day }}
        </div>
        
        <div 
          v-for="(day, index) in calendarDays" 
          :key="index"
          :class="['bg-white p-2 min-h-[120px] relative', !day.isCurrentMonth && 'bg-gray-50 text-gray-400', day.isToday && 'bg-blue-50']"
          @drop="onDrop($event, day)"
          @dragover.prevent
          @dragenter="onDragEnter($event)"
          @dragleave="onDragLeave($event)"
        >
          <div class="text-sm font-medium mb-2">{{ day.date }}</div>
          <div class="space-y-1">
            <div 
              v-for="mo in day.orders" 
              :key="mo.id"
              draggable="true"
              @dragstart="onDragStart($event, mo)"
              @dragend="onDragEnd"
              @click="viewOrder(mo.id)"
              :class="['text-xs p-2 rounded cursor-move shadow-sm border-l-4', getStatusColor(mo.status)]"
              :title="`${mo.name}: ${mo.product?.name} (${mo.qty_to_produce})`"
            >
              <div class="font-semibold">{{ mo.name }}</div>
              <div class="text-xs opacity-75 truncate">{{ mo.product?.name }}</div>
              <div class="text-xs mt-1 opacity-60">Qty: {{ Math.floor(mo.qty_to_produce) }}</div>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-4 flex flex-wrap gap-4 text-sm">
        <div class="flex items-center gap-2"><div class="w-4 h-4 rounded bg-gray-200 border-l-4 border-gray-400"></div><span>Draft</span></div>
        <div class="flex items-center gap-2"><div class="w-4 h-4 rounded bg-blue-100 border-l-4 border-blue-500"></div><span>Confirmed</span></div>
        <div class="flex items-center gap-2"><div class="w-4 h-4 rounded bg-orange-100 border-l-4 border-orange-500"></div><span>In Progress</span></div>
        <div class="flex items-center gap-2"><div class="w-4 h-4 rounded bg-green-100 border-l-4 border-green-500"></div><span>Done</span></div>
        <div class="flex items-center gap-2"><div class="w-4 h-4 rounded bg-purple-100 border-l-4 border-purple-500"></div><span>Scheduled</span></div>
      </div>
    </div>

    <!-- Table View -->
    <div v-if="viewMode === 'table'" class="card p-0 overflow-hidden">
      <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Order</th>
            <th>Product</th>
            <th>Progress</th>
            <th>Status</th>
            <th>Priority</th>
            <th>Start</th>
            <th>End</th>
            <th class="w-48">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="mo in tableOrders" :key="mo.id" class="hover:bg-gray-50">
            <td>
              <button @click="openDetail(mo)" class="font-medium text-primary-600 hover:underline">
                {{ mo.name }}
              </button>
            </td>
            <td>
              <div class="flex items-center gap-2">
                <div 
                  class="w-8 h-8 rounded overflow-hidden bg-gray-100 flex items-center justify-center cursor-pointer hover:ring-2 hover:ring-primary-500 transition-all"
                  @click="openImage(mo.product?.image_url, mo.product?.name)"
                >
                  <img 
                    v-if="mo.product?.image_url" 
                    :src="getImageUrl(mo.product.image_url)" 
                    :alt="mo.product.name"
                    class="w-full h-full object-cover"
                  />
                  <Icon v-else name="heroicons:cube" class="w-4 h-4 text-gray-400" />
                </div>
                <span>{{ mo.product?.name || 'N/A' }}</span>
              </div>
            </td>
            <td>
              <div class="flex items-center gap-2">
                <div class="w-20 h-2 bg-gray-200 rounded-full overflow-hidden">
                  <div 
                    class="h-full rounded-full transition-all" 
                    :class="progressClass(mo)"
                    :style="{ width: `${progressPercent(mo)}%` }"
                  ></div>
                </div>
                <span class="text-sm text-gray-600">{{ Number(mo.qty_produced) }}/{{ Number(mo.qty_to_produce) }}</span>
              </div>
            </td>
            <td><UiStatusBadge :status="mo.status" /></td>
            <td><UiPriorityBadge :priority="mo.priority" /></td>
            <td class="text-sm text-gray-500">{{ formatDate(mo.scheduled_start) }}</td>
            <td class="text-sm text-gray-500">{{ formatDate(mo.scheduled_end) }}</td>
            <td>
              <div class="flex gap-2">
                <!-- Schedule button -->
                <UiIconButton 
                  v-if="mo.status === 'draft' && mo.scheduled_start && mo.scheduled_end"
                  @click="scheduleOrder(mo)" 
                  icon="heroicons:calendar-days"
                  tooltip="Schedule Order"
                  color="text-purple-600 hover:text-purple-800"
                  :loading="actionLoading === mo.id"
                  :disabled="actionLoading === mo.id"
                />
                
                <!-- Confirm button -->
                <UiIconButton 
                  v-if="mo.status === 'draft'"
                  @click="confirmOrder(mo)" 
                  icon="heroicons:check-circle"
                  tooltip="Confirm Order"
                  color="text-blue-600 hover:text-blue-800"
                  :loading="actionLoading === mo.id"
                  :disabled="actionLoading === mo.id"
                />
                
                <!-- Start button -->
                <UiIconButton 
                  v-if="mo.status === 'confirmed' || mo.status === 'scheduled'"
                  @click="startOrder(mo)" 
                  icon="heroicons:play"
                  tooltip="Start Production"
                  color="text-green-600 hover:text-green-800"
                  :loading="actionLoading === mo.id"
                  :disabled="actionLoading === mo.id"
                />

                <!-- Mark Done button -->
                <UiIconButton 
                  v-if="mo.status === 'in_progress'" 
                  @click="completeOrder(mo)" 
                  icon="heroicons:check-badge"
                  tooltip="Mark as Done"
                  color="text-green-600 hover:text-green-800"
                  :loading="actionLoading === mo.id"
                  :disabled="actionLoading === mo.id"
                />
                
                <!-- View Detail -->
                <UiIconButton
                  @click="openDetail(mo)"
                  icon="heroicons:eye"
                  tooltip="View Details"
                />

                <!-- Edit -->
                <UiIconButton
                  v-if="mo.status === 'draft' || mo.status === 'scheduled'"
                  @click="openModal(mo)"
                  icon="heroicons:pencil"
                  tooltip="Edit Order"
                />

                <!-- Delete -->
                <UiIconButton
                   v-if="mo.status === 'draft'"
                   @click="deleteOrder(mo)"
                   icon="heroicons:trash"
                   tooltip="Delete"
                   color="text-red-400 hover:text-red-600"
                   :loading="actionLoading === mo.id"
                   :disabled="actionLoading === mo.id"
                />
              </div>
            </td>
          </tr>
          <tr v-if="tableOrders.length === 0 && !loading">
            <td colspan="7">
              <UiEmptyState 
                title="No orders found" 
                description="Create a manufacturing order to schedule production."
                icon="heroicons:clipboard-document-list"
              >
                <button class="btn-primary" @click="openModal()">
                  <Icon name="heroicons:plus" class="w-4 h-4" />
                  New Order
                </button>
              </UiEmptyState>
            </td>
          </tr>
        </tbody>
        <tbody v-if="loading">
          <tr v-for="i in 5" :key="i" class="animate-pulse">
            <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-24"></div></td>
            <td class="px-6 py-4"><div class="flex items-center gap-2"><div class="w-8 h-8 bg-gray-200 rounded"></div><div class="h-4 bg-gray-200 rounded w-32"></div></div></td>
            <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-20"></div></td>
            <td class="px-6 py-4"><div class="h-6 bg-gray-200 rounded w-16"></div></td>
            <td class="px-6 py-4"><div class="h-6 bg-gray-200 rounded w-16"></div></td>
            <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-24"></div></td>
            <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-24"></div></td>
            <td class="px-6 py-4"><div class="h-8 bg-gray-200 rounded w-24"></div></td>
          </tr>
        </tbody>
      </table>
      </div>

      <!-- Pagination -->
      <UiPagination
        v-if="Math.ceil(total / perPage) > 1"
        v-model="page"
        :total-items="total"
        :page-size="perPage"
      />
    </div>

    <!-- Create/Edit SlideOver -->
    <UiSlideOver v-model="showModal" :title="editing ? 'Edit Manufacturing Order' : 'New Manufacturing Order'">
      <form @submit.prevent="save" class="space-y-6">
        <!-- Product Selection -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Product to Manufacture *</label>
          <UiSearchableSelect
            v-model="form.product_id"
            :options="finishedProducts.map(p => ({ label: `${p.name} (${p.code})`, value: p.id }))"
            placeholder="Select a finished product..."
            @update:modelValue="onProductChange"
          />
        </div>

        <!-- BOM Selection (auto-filtered) -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Bill of Materials *</label>
           <UiSearchableSelect
            v-model="form.bom_id"
            :options="filteredBoms.map(b => ({ label: `BOM #${b.id} - ${b.type} (produces ${Number(b.qty_produced)})`, value: b.id }))"
            placeholder="Select a BOM..."
            :disabled="!form.product_id"
          />
          <p v-if="form.product_id && filteredBoms.length === 0" class="text-xs text-amber-600 mt-1">
            No BOM found for this product. Create one first.
          </p>
        </div>

        <!-- Quantity and Priority -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Quantity to Produce *</label>
          <input v-model.number="form.qty_to_produce" type="number" min="1" class="input" required />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
          <select v-model="form.priority" class="input">
            <option value="low">Low</option>
            <option value="normal">Normal</option>
            <option value="high">High</option>
            <option value="urgent">Urgent</option>
          </select>
        </div>

        <!-- Scheduling -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Scheduled Start</label>
          <input v-model="form.scheduled_start" type="datetime-local" class="input" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Scheduled End</label>
          <input v-model="form.scheduled_end" type="datetime-local" class="input" />
        </div>

        <div class="flex justify-end gap-3 mt-6">
          <button type="button" @click="showModal = false" class="btn-ghost">Cancel</button>
          <button type="submit" class="btn-primary" :disabled="saving">
            {{ saving ? 'Saving...' : (editing ? 'Update Order' : 'Create Order') }}
          </button>
        </div>
      </form>
    </UiSlideOver>

    <!-- Image Preview -->
    <UiImagePreview v-model="showImagePreview" :src="previewImage.src" :alt="previewImage.alt" />

    <UiSlideOver v-model="showDetailModal" title="Manufacturing Order Detail" width="sm:w-[75vw]">
       <ExecutionManufacturingOrderDetail v-if="selectedOrderId" :order-id="selectedOrderId" @updated="refreshTable" />
    </UiSlideOver>

    <!-- Confirm Modal -->
    <UiConfirmModal
      v-model="showConfirmModal"
      :title="confirmTitle"
      :message="confirmMessage"
      @confirm="handleConfirm"
    />
  </div>
</template>

<script setup lang="ts">
import { useServerDataTable } from '~/composables/useServerDataTable'
import type { ManufacturingOrder, Product, Bom } from '~/types/models'

const { $api } = useApi()
const toast = useToast()
const config = useRuntimeConfig()
const masterStore = useMasterStore()
const { getImageUrl, formatDate } = useUtils()

// View State
const viewMode = ref<'table' | 'calendar'>('table')

// Data Table (Server-side)
const table = useServerDataTable<ManufacturingOrder>({
  url: 'manufacturing-orders',
  initialFilters: { status: '' },
  perPage: 10
})

const { 
  items: tableOrders, 
  total, 
  loading, 
  counts, 
  page, 
  perPage, 
  search, 
  filters, 
  refresh: refreshTable 
} = table

// Calendar State
const calendarOrders = ref<ManufacturingOrder[]>([])
const currentDate = ref(new Date())
const currentYear = computed(() => currentDate.value.getFullYear())
const currentMonth = computed(() => currentDate.value.getMonth())
const currentMonthName = computed(() => currentDate.value.toLocaleDateString('en-US', { month: 'long' }))

// Calendar Logic
async function fetchCalendarData() {
    const start = new Date(currentYear.value, currentMonth.value, 1)
    const end = new Date(currentYear.value, currentMonth.value + 1, 0)
    const startStr = start.toISOString().split('T')[0]
    const endStr = end.toISOString().split('T')[0]

    try {
        const res = await $api<{ data: ManufacturingOrder[] }>('manufacturing-orders', {
            query: {
                start_date: startStr,
                end_date: endStr,
                per_page: 1000 // Fetch all for calendar
            }
        })
        calendarOrders.value = res.data
    } catch (e) {
        console.error('Failed to fetch calendar data', e)
    }
}

watch([currentYear, currentMonth, viewMode], () => {
    if (viewMode.value === 'calendar') {
        fetchCalendarData()
    }
})

// Calendar Days Generation
const calendarDays = computed(() => {
  const year = currentYear.value
  const month = currentMonth.value
  const firstDay = new Date(year, month, 1)
  const lastDay = new Date(year, month + 1, 0)
  const firstDayOfWeek = firstDay.getDay()
  const daysInMonth = lastDay.getDate()
  const days: any[] = []
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  
  const prevMonthLastDay = new Date(year,month, 0).getDate()
  for (let i = firstDayOfWeek - 1; i >= 0; i--) {
    days.push({ date: prevMonthLastDay - i, isCurrentMonth: false, isToday: false, orders: [], fullDate: new Date(year, month - 1, prevMonthLastDay - i) })
  }
  
  for (let date = 1; date <= daysInMonth; date++) {
    const dayDate = new Date(year, month, date)
    const isToday = dayDate.getTime() === today.getTime()
    
    const ordersOnDay = calendarOrders.value.filter(mo => {
      if (!mo.scheduled_start) return false
      const startDate = new Date(mo.scheduled_start)
      const endDate = mo.scheduled_end ? new Date(mo.scheduled_end) : startDate
      return dayDate >= new Date(startDate.getFullYear(), startDate.getMonth(), startDate.getDate()) &&
             dayDate <= new Date(endDate.getFullYear(), endDate.getMonth(), endDate.getDate())
    })
    days.push({ date, isCurrentMonth: true, isToday, orders: ordersOnDay, fullDate: dayDate })
  }
  
  const remainingDays = 42 - days.length
  for (let date = 1; date <= remainingDays; date++) {
    days.push({ date, isCurrentMonth: false, isToday: false, orders: [], fullDate: new Date(year, month + 1, date) })
  }
  return days
})

function changeMonth(delta: number) {
  currentDate.value = new Date(currentYear.value, currentMonth.value + delta, 1)
}

// Master Data
const boms = ref<Bom[]>([])
const products = computed(() => masterStore.products)
const finishedProducts = computed(() => products.value.filter(p => p.type === 'finished'))

onMounted(async () => {
    await Promise.all([
        masterStore.fetchProducts(),
         $api<{ data: Bom[] }>('/boms').then(r => boms.value = r.data || []).catch(() => {})
    ])
    
    if (viewMode.value === 'calendar') {
        fetchCalendarData()
    }
})

// Modals & Form
const showModal = ref(false)
const showDetailModal = ref(false)
const selectedOrderId = ref<number | null>(null)
const editing = ref<ManufacturingOrder | null>(null)
const saving = ref(false)
const actionLoading = ref<number | null>(null)

const form = ref({
  product_id: null as number | null,
  bom_id: null as number | null,
  qty_to_produce: 1,
  priority: 'normal',
  scheduled_start: '',
  scheduled_end: '',
})

const filteredBoms = computed(() => {
  if (!form.value.product_id) return []
  return boms.value.filter(b => b.product_id === form.value.product_id && b.is_active)
})

function onProductChange() {
  form.value.bom_id = null
  if (filteredBoms.value.length === 1) {
    form.value.bom_id = filteredBoms.value[0]?.id ?? null
  }
}

function openModal(mo?: ManufacturingOrder) {
  if (mo) {
    editing.value = mo
    form.value = {
      product_id: mo.product_id,
      bom_id: mo.bom_id,
      qty_to_produce: mo.qty_to_produce,
      priority: mo.priority,
      scheduled_start: mo.scheduled_start?.substring(0, 16) || '',
      scheduled_end: mo.scheduled_end?.substring(0, 16) || '',
    }
  } else {
    editing.value = null
    form.value = { product_id: null, bom_id: null, qty_to_produce: 1, priority: 'normal', scheduled_start: '', scheduled_end: '' }
  }
  showModal.value = true
}

function openDetail(mo: ManufacturingOrder) {
    selectedOrderId.value = mo.id
    showDetailModal.value = true
}

// Actions
async function save() {
  saving.value = true
  try {
    const payload = { ...form.value }
    if (editing.value) {
      await $api(`/manufacturing-orders/${editing.value.id}`, { method: 'PUT', body: payload })
      toast.success('Order updated')
    } else {
      await $api('/manufacturing-orders', { method: 'POST', body: payload })
      toast.success('Order created')
    }
    showModal.value = false
    refreshTable()
    if (viewMode.value === 'calendar') fetchCalendarData()
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to save order')
  } finally {
    saving.value = false
  }
}

async function handleAction(mo: ManufacturingOrder, action: () => Promise<void>, successMsg: string) {
    actionLoading.value = mo.id
    try {
        await action()
        toast.success(successMsg)
        refreshTable()
        if (viewMode.value === 'calendar') fetchCalendarData()
    } catch (e: any) {
        toast.error(e.data?.message || 'Action failed')
    } finally {
        actionLoading.value = null
    }
}

function scheduleOrder(mo: ManufacturingOrder) {
    if (!mo.scheduled_start || !mo.scheduled_end) {
        toast.error('Please set scheduled dates first')
        return
    }
    handleAction(
        mo, 
        () => $api(`/manufacturing-orders/${mo.id}/reschedule`, { 
            method: 'PUT', 
            body: { scheduled_start: mo.scheduled_start, scheduled_end: mo.scheduled_end } 
        }), 
        'Order scheduled'
    )
}

function confirmOrder(mo: ManufacturingOrder) {
    handleAction(mo, () => $api(`/manufacturing-orders/${mo.id}/confirm`, { method: 'POST' }), 'Order confirmed')
}

function startOrder(mo: ManufacturingOrder) {
    handleAction(mo, () => $api(`/manufacturing-orders/${mo.id}/start`, { method: 'POST' }), 'Production started')
}

function completeOrder(mo: ManufacturingOrder) {
    handleAction(mo, () => $api(`/manufacturing-orders/${mo.id}/complete`, { method: 'POST' }), 'Order completed')
}

// Confirm Modal
const showConfirmModal = ref(false)
const confirmMessage = ref('')
const confirmTitle = ref('')
const confirmAction = ref<(() => void) | null>(null)

function openConfirmModal(title: string, message: string, action: () => void) {
  confirmTitle.value = title
  confirmMessage.value = message
  confirmAction.value = action
  showConfirmModal.value = true
}

function handleConfirm() {
  if (confirmAction.value) confirmAction.value()
  showConfirmModal.value = false
}

function deleteOrder(mo: ManufacturingOrder) {
    if (actionLoading.value) return
    openConfirmModal(
        'Delete Order', 
        `Are you sure you want to delete MO #${mo.id}? This cannot be undone.`, 
        () => handleAction(mo, () => $api(`/manufacturing-orders/${mo.id}`, { method: 'DELETE' }), 'Order deleted')
    )
}

// Drag & Drop (Calendar)
const draggingMO = ref<any>(null)
function onDragStart(event: DragEvent, mo: any) {
  draggingMO.value = mo
  if (event.dataTransfer) event.dataTransfer.effectAllowed = 'move'
}
function onDragEnd() { draggingMO.value = null }
function onDragEnter(event: DragEvent) {
  if (!draggingMO.value) return
  const target = event.currentTarget as HTMLElement
  target?.classList.add('bg-blue-100')
}
function onDragLeave(event: DragEvent) {
  const target = event.currentTarget as HTMLElement
  target?.classList.remove('bg-blue-100')
}
async function onDrop(event: DragEvent, day: any) {
  const target = event.currentTarget as HTMLElement
  target?.classList.remove('bg-blue-100')
  if (!draggingMO.value || !day.isCurrentMonth) return
  
  const mo = draggingMO.value
  const newStart = new Date(day.fullDate)
  newStart.setHours(8, 0, 0, 0)
  const duration = mo.scheduled_end ? new Date(mo.scheduled_end).getTime() - new Date(mo.scheduled_start).getTime() : 8 * 60 * 60 * 1000
  const newEnd = new Date(newStart.getTime() + duration)
  
  try {
    await $api(`/manufacturing-orders/${mo.id}/reschedule`, {
      method: 'PUT',
      body: { scheduled_start: newStart.toISOString(), scheduled_end: newEnd.toISOString() }
    })
    toast.success('Order rescheduled')
    fetchCalendarData()
    refreshTable() // Update list too
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to reschedule order')
  }
  draggingMO.value = null
}

const route = useRoute()
onMounted(async () => {
    if (route.query.id) {
        selectedOrderId.value = Number(route.query.id)
        showDetailModal.value = true
    }
})

// Misc utils
function getStatusColor(status: string) {
  const colors: Record<string, string> = {
    draft: 'bg-gray-200 text-gray-800 border-gray-400',
    confirmed: 'bg-blue-100 text-blue-800 border-blue-500',
    in_progress: 'bg-orange-100 text-orange-800 border-orange-500',
    done: 'bg-green-100 text-green-800 border-green-500',
    scheduled: 'bg-purple-100 text-purple-800 border-purple-500',
    cancelled: 'bg-red-100 text-red-800 border-red-500',
  }
  return colors[status] || 'bg-gray-200 text-gray-800 border-gray-400'
}

function viewOrder(id: number) { navigateTo(`/execution/manufacturing-orders/${id}`) }
function progressPercent(mo: ManufacturingOrder) {
  if (!mo.qty_to_produce) return 0
  return mo.qty_to_produce > 0 ? (mo.qty_produced / mo.qty_to_produce) * 100 : 0
}
function progressClass(mo: ManufacturingOrder) {
  const percent = progressPercent(mo)
  if (percent >= 100) return 'bg-green-500'
  if (percent > 0) return 'bg-primary-500'
  return 'bg-gray-300'
}

// Image Preview
const showImagePreview = ref(false)
const previewImage = ref({ src: '', alt: '' })
function openImage(url?: string, alt?: string) {
  if (!url) return
  previewImage.value = { src: getImageUrl(url), alt: alt || 'Product Image' }
  showImagePreview.value = true
}
</script>
