<template>
  <div v-if="mo" class="space-y-6">
    
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <h1 class="text-2xl font-bold text-gray-900">{{ mo.name }}</h1>
                <UiStatusBadge :status="mo.status || 'draft'" size="lg" />
            </div>
            <h2 class="text-lg text-gray-600 font-medium">{{ mo.product?.name }}</h2>
            <p class="text-sm text-gray-500 mt-1">BOM: #{{ mo.bom_id }}</p>
        </div>
        
        <!-- Actions -->
        <div class="flex gap-2">
            <button 
            v-if="mo.status === 'draft'" 
            @click="confirmOrder" 
            class="btn-primary"
            :disabled="actionLoading"
            >
            Confirm Order
            </button>
            <button 
            v-if="mo.status === 'confirmed' || mo.status === 'scheduled'" 
            @click="confirmReset" 
            class="btn-outline"
            :disabled="actionLoading"
            >
            Reset to Draft
            </button>
            <button 
            v-if="mo.status === 'confirmed' || mo.status === 'scheduled'" 
            @click="startOrder" 
            class="btn-primary"
            :disabled="actionLoading"
            >
            Start Production
            </button>
            <button 
            v-if="mo.status === 'in_progress'" 
            @click="completeOrder" 
            class="btn-secondary"
            :disabled="actionLoading"
            >
            Mark as Done
            </button>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
        
        <!-- Left Column: Image & Timeline (20%) -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Image -->
            <div class="card p-0 overflow-hidden bg-white border border-gray-200 shadow-sm rounded-xl">
                <div 
                    class="aspect-square w-full bg-gray-50 flex items-center justify-center relative group cursor-pointer"
                    @click="openImage(mo.product?.image_url, mo.product?.name)"
                >
                    <img 
                      v-if="mo.product?.image_url" 
                      :src="getImageUrl(mo.product.image_url)" 
                      class="w-full h-full object-contain p-4 transition-transform duration-300 group-hover:scale-105" 
                      :alt="mo.product?.name" 
                    />
                    <Icon v-else name="heroicons:cube" class="w-16 h-16 text-gray-300" />
                </div>
            </div>

            <!-- Timeline -->
            <div class="card bg-white border border-gray-200 shadow-sm rounded-xl p-4">
               <h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2 text-sm">
                <Icon name="heroicons:clock" class="w-4 h-4 text-gray-400" />
                Timeline
               </h3>
               <div class="space-y-3">
                 <div class="flex justify-between items-center py-1 border-b border-gray-50 last:border-0">
                   <span class="text-xs text-gray-500">Created</span>
                   <span class="text-xs font-medium text-gray-900">{{ formatDate(mo.created_at) }}</span>
                 </div>
                 <div class="flex justify-between items-center py-1 border-b border-gray-50 last:border-0">
                   <span class="text-xs text-gray-500">Sched. Start</span>
                   <span class="text-xs font-medium text-gray-900">{{ formatDate(mo.scheduled_start) }}</span>
                 </div>
                 <div class="flex justify-between items-center py-1 border-b border-gray-50 last:border-0">
                   <span class="text-xs text-gray-500">Sched. End</span>
                   <span class="text-xs font-medium text-gray-900">{{ formatDate(mo.scheduled_end) }}</span>
                 </div>
                  <div class="flex justify-between items-center py-1 border-b border-gray-50 last:border-0">
                   <span class="text-xs text-gray-500">Act. Start</span>
                   <span class="text-xs font-medium text-gray-900">{{ formatDate(mo.actual_start) }}</span>
                 </div>
                 <div class="flex justify-between items-center py-1 border-b border-gray-50 last:border-0">
                   <span class="text-xs text-gray-500">Act. End</span>
                   <span class="text-xs font-medium text-gray-900">{{ formatDate(mo.actual_end) }}</span>
                 </div>
               </div>
            </div>
        </div>

        <!-- Right Column: Progress & Details (80%) -->
        <div class="lg:col-span-4 space-y-6">
            
            <!-- Top Row: Progress & Key Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Compact Progress -->
                <div class="md:col-span-2 card bg-white border border-gray-200 shadow-sm rounded-xl p-4 flex flex-col justify-center">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-gray-700">Production Progress</span>
                        <span class="text-lg font-bold text-primary-600">{{ Math.round(progressPercent) }}%</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden mb-2">
                        <div 
                            class="bg-primary-600 h-2 rounded-full transition-all duration-500" 
                            :style="{ width: `${progressPercent}%` }"
                        ></div>
                    </div>
                    <div class="text-xs text-gray-500 flex justify-between">
                        <span>Produced: <strong class="text-gray-900">{{ Number(mo.qty_produced) }}</strong></span>
                        <span>Target: <strong class="text-gray-900">{{ Number(mo.qty_to_produce) }}</strong></span>
                    </div>
                </div>

                <!-- Metrics -->
                <div class="card bg-white border border-gray-200 shadow-sm rounded-xl p-3 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-medium text-gray-500 uppercase block">Priority</span>
                        <UiPriorityBadge :priority="mo.priority || 'normal'" class="mt-1" />
                    </div>
                    <div class="h-8 w-8 rounded-full bg-gray-50 flex items-center justify-center">
                        <Icon name="heroicons:flag" class="w-4 h-4 text-gray-400" />
                    </div>
                </div>

                 <div class="card bg-white border border-gray-200 shadow-sm rounded-xl p-3 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-medium text-gray-500 uppercase block">Target Lot</span>
                        <span class="font-mono text-sm font-medium text-gray-700 bg-gray-50 px-2 py-0.5 rounded mt-1 inline-block">{{ mo.lot?.name || 'N/A' }}</span>
                    </div>
                     <div class="h-8 w-8 rounded-full bg-gray-50 flex items-center justify-center">
                        <Icon name="heroicons:tag" class="w-4 h-4 text-gray-400" />
                    </div>
                </div>
            </div>

    <!-- Tabs (Full Width) -->
    <div class="card bg-white border border-gray-200 shadow-sm rounded-xl overflow-hidden min-h-[400px]">
      <div class="flex border-b border-gray-200 overflow-x-auto scrollbar-hide">
        <button 
          v-for="tab in ['work_orders', 'components', 'cost_analysis', 'miscellaneous']" 
          :key="tab"
          @click="activeTab = tab"
          class="px-6 py-4 text-sm font-medium border-b-2 -mb-px capitalize transition-colors whitespace-nowrap focus:outline-none"
          :class="activeTab === tab ? 'border-primary-600 text-primary-600 bg-primary-50/50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
        >
          {{ tab.replace('_', ' ') }}
          <span 
            v-if="tab !== 'miscellaneous' && tab !== 'cost_analysis'"
            :class="[
              'ml-2 text-xs px-2 py-0.5 rounded-full', 
              activeTab === tab ? 'bg-primary-100 text-primary-700' : 'bg-gray-100 text-gray-600'
            ]"
          >
            {{ tab === 'work_orders' ? (mo.work_orders?.length || 0) : (tab === 'components' ? (mo.consumptions?.length || 0) : '') }}
          </span>
        </button>
      </div>
      
      <div class="p-6">
        <!-- Work Orders Tab -->
        <div v-if="activeTab === 'work_orders'" class="space-y-4">
          <div class="table-responsive">
          <table class="table w-full">
            <thead>
              <tr>
                <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Operation</th>
                <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Work Center</th>
                <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Scheduled</th>
                <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actual</th>
                <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                 <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Instruction</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="wo in paginatedWorkOrders" :key="wo.id" class="hover:bg-gray-50">
                <td class="font-medium text-gray-900 py-3">{{ wo.operation?.name }}</td>
                <td class="text-gray-600 py-3">{{ wo.work_center?.name }}</td>
                <td class="font-mono text-xs text-gray-500">{{ formatDuration(wo.duration_expected) }}</td>
                <td class="font-mono text-xs text-gray-500">{{ formatDuration(wo.duration_actual) }}</td>
                <td><UiStatusBadge :status="wo.status" /></td>
                <td>
                    <UiFilePreview 
                        v-if="wo.operation?.instruction_file_url"
                        :url="wo.operation.instruction_file_url" 
                    />
                    <span v-else class="text-gray-300">-</span>
                </td>
              </tr>
              <tr v-if="!mo.work_orders?.length">
                <td colspan="6" class="text-center py-8 text-gray-400 italic">No work orders generated yet.</td>
              </tr>
            </tbody>
          </table>
          </div>
          <!-- Pagination -->
          <UiPagination
             v-if="(mo.work_orders?.length || 0) > pageSize"
             v-model="woPage"
             :total-items="mo.work_orders?.length || 0"
             :page-size="pageSize"
          />
        </div>

        <!-- Components Tab -->
        <div v-if="activeTab === 'components'" class="space-y-4">
           <div class="table-responsive">
           <table class="table w-full">
            <thead>
              <tr>
                <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lot/Serial</th>
                <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">To Consume</th>
                <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Consumed</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="comp in paginatedComponents" :key="comp.id" class="hover:bg-gray-50">
                <td class="font-medium text-gray-900 py-3">{{ comp.product?.name }}</td>
                <td class="font-mono text-xs text-gray-500">{{ comp.lot?.name || '-' }}</td>
                <td class="text-gray-900">{{ Number(comp.qty_planned) }}</td>
                <td class="text-gray-900">{{ Number(comp.qty_consumed) }}</td>
              </tr>
               <tr v-if="!mo.consumptions?.length">
                <td colspan="4" class="text-center py-8 text-gray-400 italic">No components info.</td>
              </tr>
          </tbody>
          </table>
           </div>
           <!-- Pagination -->
          <UiPagination
             v-if="(mo.consumptions?.length || 0) > pageSize"
             v-model="compPage"
             :total-items="mo.consumptions?.length || 0"
             :page-size="pageSize"
          />
        </div>
        
        <!-- Cost Analysis Tab -->
        <div v-if="activeTab === 'cost_analysis'" class="space-y-6">
            <!-- Summary Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="p-4 bg-blue-50 rounded-lg border border-blue-100">
                    <span class="text-xs font-medium text-blue-600 uppercase">Material</span>
                    <div class="text-xl font-bold text-blue-900 mt-1">${{ costSummary.material?.toFixed(2) || '0.00' }}</div>
                </div>
                <div class="p-4 bg-green-50 rounded-lg border border-green-100">
                    <span class="text-xs font-medium text-green-600 uppercase">Labor</span>
                    <div class="text-xl font-bold text-green-900 mt-1">${{ costSummary.labor?.toFixed(2) || '0.00' }}</div>
                </div>
                <div class="p-4 bg-yellow-50 rounded-lg border border-yellow-100">
                    <span class="text-xs font-medium text-yellow-600 uppercase">Overhead</span>
                    <div class="text-xl font-bold text-yellow-900 mt-1">${{ costSummary.overhead?.toFixed(2) || '0.00' }}</div>
                </div>
                 <div class="p-4 bg-orange-50 rounded-lg border border-orange-100">
                    <span class="text-xs font-medium text-orange-600 uppercase">Losses</span>
                    <div class="text-xl font-bold text-orange-900 mt-1">${{ costSummary.material_variance?.toFixed(2) || '0.00' }}</div>
                </div>
                 <div class="p-4 bg-purple-50 rounded-lg border border-purple-100">
                    <span class="text-xs font-medium text-purple-600 uppercase">Total Cost</span>
                    <div class="text-xl font-bold text-purple-900 mt-1">${{ costSummary.total?.toFixed(2) || '0.00' }}</div>
                </div>
            </div>

            <!-- Cost Details Table -->
            <div class="table-responsive">
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th class="text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                            <th class="text-right text-xs font-medium text-gray-500 uppercase">Qty</th>
                            <th class="text-right text-xs font-medium text-gray-500 uppercase">Unit Cost</th>
                            <th class="text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="entry in paginatedCostDetails" :key="entry.id" class="hover:bg-gray-50">
                            <td class="py-3">
                                <span :class="costTypeClass(entry.cost_type)" class="px-2 py-0.5 rounded-full text-xs font-medium capitalize">
                                    {{ entry.cost_type.replace('_', ' ') }}
                                </span>
                            </td>
                            <td class="text-gray-900 font-medium">{{ entry.notes || entry.product?.name || '-' }}</td>
                            <td class="text-right text-gray-600">{{ Number(entry.quantity).toFixed(2) }}</td>
                            <td class="text-right text-gray-600">${{ Number(entry.unit_cost).toFixed(2) }}</td>
                            <td class="text-right font-medium text-gray-900">${{ Number(entry.total_cost).toFixed(2) }}</td>
                        </tr>
                        <tr v-if="!costDetails.length">
                            <td colspan="5" class="text-center py-8 text-gray-400 italic">No cost entries recorded yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <UiPagination
               v-if="costDetails.length > pageSize"
               v-model="costPage"
               :total-items="costDetails.length"
               :page-size="pageSize"
            />
        </div>

         <!-- Miscellaneous -->
         <div v-if="activeTab === 'miscellaneous'" class="grid grid-cols-1 sm:grid-cols-2 gap-6">
           <div class="p-4 bg-gray-50 rounded-lg">
            <span class="text-xs font-medium text-gray-500 uppercase block mb-1">Product Details</span>
            <span class="font-medium text-gray-900 block">{{ mo.product?.name }}</span>
            <span class="text-sm text-gray-500 block mt-1">{{ mo.product?.code }}</span>
           </div>
           <div class="p-4 bg-gray-50 rounded-lg">
            <span class="text-xs font-medium text-gray-500 uppercase block mb-1">BOM Reference</span>
            <span class="font-medium text-gray-900 block">BOM #{{ mo.bom_id }}</span>
           </div>
           <div class="p-4 bg-gray-50 rounded-lg">
            <span class="text-xs font-medium text-gray-500 uppercase block mb-1">Schedule</span>
            <div class="flex justify-between items-center text-sm">
                <span class="text-gray-500">Starts:</span>
                <span class="font-medium text-gray-900">{{ formatDate(mo.scheduled_start) }}</span>
            </div>
             <div class="flex justify-between items-center text-sm mt-1">
                <span class="text-gray-500">Ends:</span>
                <span class="font-medium text-gray-900">{{ formatDate(mo.scheduled_end) }}</span>
            </div>
           </div>
        </div>
      </div>
    </div>
        </div>
    </div>
  </div>
  <div v-else class="flex justify-center py-12">
     <Icon name="svg-spinners:180-ring" class="w-8 h-8 text-primary-600" />
  </div>


  <UiImagePreview v-model="showImagePreview" :src="previewImage.src" :alt="previewImage.alt" />
  
  <UiConfirmModal
    v-model="showResetModal"
    title="Reset to Draft"
    message="Are you sure you want to reset this order to Draft? This will remove any generated work orders and release stock reservations. Schedule dates will be preserved."
    confirm-text="Reset Order"
    variant="danger"
    :loading="resetting"
    @confirm="resetOrder"
  />
</template>

<script setup lang="ts">
import type { ManufacturingOrder, CostEntry } from '~/types/models'

const props = defineProps<{
  orderId: number
}>()

const emit = defineEmits(['updated'])

const { $api } = useApi()
const toast = useToast()
const config = useRuntimeConfig()
const { getImageUrl, formatDate, formatDuration } = useUtils()

const mo = ref<ManufacturingOrder | null>(null)
const activeTab = ref('work_orders')
const costSummary = ref<any>({})
const costDetails = ref<CostEntry[]>([])
const costLoading = ref(false)

// Pagination State
const woPage = ref(1)
const compPage = ref(1)
const costPage = ref(1)
const pageSize = 10

const paginatedWorkOrders = computed(() => {
  if (!mo.value?.work_orders) return []
  const start = (woPage.value - 1) * pageSize
  return mo.value.work_orders.slice(start, start + pageSize)
})

const paginatedComponents = computed(() => {
  if (!mo.value?.consumptions) return []
  const start = (compPage.value - 1) * pageSize
  return mo.value.consumptions.slice(start, start + pageSize)
})

const paginatedCostDetails = computed(() => {
  const start = (costPage.value - 1) * pageSize
  return costDetails.value.slice(start, start + pageSize)
})

const actionLoading = ref(false)

// Image Preview
const showImagePreview = ref(false)
const previewImage = ref({ src: '', alt: '' })

// Reset Confirmation
const showResetModal = ref(false)
const resetting = ref(false)

function openImage(src?: string, alt?: string) {
  if (!src) return
  previewImage.value = { src: getImageUrl(src), alt: alt || 'Product Image' }
  showImagePreview.value = true
}

const progressPercent = computed(() => {
  if (!mo.value || !mo.value.qty_to_produce) return 0
  return (mo.value.qty_produced / mo.value.qty_to_produce) * 100
})



async function fetchMo() {
    if (!props.orderId) return
    try {
        const res = await $api<{ data: ManufacturingOrder }>(`/manufacturing-orders/${props.orderId}`)
        mo.value = res.data
    } catch (e) {
        toast.error('Failed to load order')
    }
}

watch(() => props.orderId, fetchMo, { immediate: true })



async function confirmOrder() {
  if (!mo.value) return
  actionLoading.value = true
  try {
    await $api(`/manufacturing-orders/${mo.value.id}/confirm`, { method: 'POST' })
    toast.success('Order confirmed')
    await fetchMo()
    emit('updated')
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to confirm')
  } finally {
    actionLoading.value = false
  }
}

async function startOrder() {
  if (!mo.value) return
  actionLoading.value = true
  try {
    await $api(`/manufacturing-orders/${mo.value.id}/start`, { method: 'POST' })
    toast.success('Production started')
    await fetchMo()
    emit('updated')
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to start')
  } finally {
    actionLoading.value = false
  }
}

async function completeOrder() {
  if (!mo.value) return
  actionLoading.value = true
  try {
    await $api(`/manufacturing-orders/${mo.value.id}/complete`, { method: 'POST' })
    toast.success('Order completed')
    await fetchMo()
    emit('updated')
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to complete')
  } finally {
    actionLoading.value = false
  }
}

function confirmReset() {
    showResetModal.value = true
}

async function resetOrder() {
  if (!mo.value) return
  
  resetting.value = true
  try {
    await $api(`/manufacturing-orders/${mo.value.id}/reset`, { method: 'POST' })
    toast.success('Order reset to draft')
    await fetchMo()
    emit('updated')
    showResetModal.value = false
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to reset')
  } finally {
    resetting.value = false
  }
}

async function fetchCostAnalysis() {
    if (!mo.value) return
    costLoading.value = true
    try {
        const res = await $api<any>(`/reporting/cost/${mo.value.id}`)
        costSummary.value = res.summary
        costDetails.value = res.details
    } catch (e) {
        console.error('Failed to fetch cost analysis', e)
    } finally {
        costLoading.value = false
    }
}

watch(activeTab, (newTab) => {
    if (newTab === 'cost_analysis') {
        fetchCostAnalysis()
    }
})

function costTypeClass(type: string) {
    const classes: Record<string, string> = {
        material: 'bg-blue-100 text-blue-700',
        labor: 'bg-green-100 text-green-700',
        overhead: 'bg-yellow-100 text-yellow-700',
        scrap: 'bg-red-100 text-red-700',
        material_variance: 'bg-orange-100 text-orange-700'
    }
    return classes[type] || 'bg-gray-100 text-gray-700'
}
</script>
