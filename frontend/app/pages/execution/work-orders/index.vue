<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Work Orders</h1>
        <p class="text-gray-500 mt-1 hidden sm:block">Track and execute production operations</p>
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
        v-for="status in ['confirmed', 'in_progress', 'done', 'scheduled']"
        :key="status"
        @click="filters.status = status" 
        :class="['px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors capitalize whitespace-nowrap', filters.status === status ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700']"
      >
        {{ status.replace('_', ' ') }} 
        <span 
          :class="[
            'ml-1 text-xs px-1.5 py-0.5 rounded-full', 
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
    <div class="flex gap-4">
      <input v-model="search" type="text" placeholder="Search by MO name or product..." class="input max-w-xs" />
    </div>

    <!-- MO Table -->
    <div class="card p-0 overflow-hidden">
      <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Order</th>
            <th>Product</th>
            <th>Progress</th>
            <th>Status</th>
            <th>Priority</th>
            <th>Work Orders</th>
            <th>Start</th>
            <th>End</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="mo in tableOrders" :key="mo.id" class="hover:bg-gray-50 cursor-pointer transition-colors" @click="openMoDetail(mo)">
            <td class="font-medium text-primary-600">
              {{ mo.name }}
            </td>
            <td>
              <div class="flex items-center gap-2">
                <div 
                  class="w-8 h-8 rounded overflow-hidden bg-gray-100 flex items-center justify-center"
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
            <td>
              <span class="text-sm text-gray-600">{{ getWoCountForMo(mo.id) }} orders</span>
            </td>
            <td class="text-sm text-gray-500">{{ formatDate(mo.scheduled_start) }}</td>
            <td class="text-sm text-gray-500">{{ formatDate(mo.scheduled_end) }}</td>
          </tr>
          <tr v-if="tableOrders.length === 0 && !loading">
            <td colspan="8">
              <UiEmptyState 
                title="No manufacturing orders found" 
                description="Work orders are created when you confirm a Manufacturing Order."
                icon="heroicons:clipboard-document-list"
              />
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
            <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-16"></div></td>
            <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-24"></div></td>
            <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-24"></div></td>
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

    <!-- MO Detail SlideOver (Work Orders for selected MO) -->
    <UiSlideOver v-model="showDetailModal" :title="selectedMo ? `Work Orders — ${selectedMo.name}` : 'Work Orders'" width="sm:w-[75vw]">
      <div v-if="selectedMo" class="space-y-6">
        <!-- MO Summary -->
        <div class="bg-gray-50 rounded-lg p-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
              <h3 class="font-semibold text-gray-900">{{ selectedMo.name }}</h3>
              <p class="text-sm text-gray-500">{{ selectedMo.product?.name || 'N/A' }}</p>
            </div>
            <div class="flex items-center gap-3">
              <UiStatusBadge :status="selectedMo.status" />
              <UiPriorityBadge :priority="selectedMo.priority" />
            </div>
          </div>
          <div v-if="selectedMo" class="mt-3 flex items-center gap-4 text-sm">
            <div class="flex items-center gap-2">
              <span class="text-gray-500">Progress:</span>
              <div class="w-24 h-2 bg-gray-200 rounded-full overflow-hidden">
                <div 
                  class="h-full rounded-full transition-all" 
                  :class="selectedMo ? progressClass(selectedMo) : ''"
                  :style="{ width: selectedMo ? `${progressPercent(selectedMo)}%` : '0%' }"
                ></div>
              </div>
              <span class="font-medium">{{ Number(selectedMo?.qty_produced || 0) }}/{{ Number(selectedMo?.qty_to_produce || 0) }}</span>
            </div>
          </div>
        </div>

        <!-- Loading Skeleton -->
        <div v-if="loadingWos" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="i in 6" :key="`skel-${i}`" class="card p-4 animate-pulse">
            <div class="flex justify-between mb-4">
              <div class="flex gap-2">
                <div>
                  <div class="h-4 bg-gray-200 rounded w-24 mb-1"></div>
                  <div class="h-3 bg-gray-200 rounded w-16"></div>
                </div>
              </div>
              <div class="h-5 bg-gray-200 rounded w-16"></div>
            </div>
            <div class="h-3 bg-gray-200 rounded w-full mb-2"></div>
            <div class="h-8 bg-gray-200 rounded w-full mt-4"></div>
          </div>
        </div>

        <!-- Work Order Cards -->
        <div v-if="!loadingWos && moWorkOrders.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="wo in moWorkOrders" :key="wo.id" class="card">
            <!-- Header -->
            <div class="flex items-start justify-between mb-3">
              <div>
                <div class="flex items-center gap-2 mb-1">
                  <span class="text-xs font-mono text-gray-400">Step {{ wo.operation?.sequence || wo.sequence }}</span>
                  <UiStatusBadge :status="wo.status" />
                </div>
                <h3 class="font-medium text-gray-800">{{ wo.operation?.name || 'Operation' }}</h3>
              </div>
              <div class="text-right">
                <span class="text-xs bg-gray-100 px-2 py-1 rounded font-mono">
                  WO #{{ wo.id }}
                </span>
              </div>
            </div>

            <!-- Work Center -->
            <div class="flex items-center gap-2 text-sm text-gray-600 mb-2">
              <Icon name="heroicons:cog-6-tooth" class="w-4 h-4 text-gray-400" />
              <span>{{ wo.work_center?.name || 'N/A' }}</span>
            </div>

            <!-- Quantity to Produce -->
            <div class="flex items-center gap-2 text-sm text-gray-600 mb-2">
              <Icon name="heroicons:clipboard-document-list" class="w-4 h-4 text-gray-400" />
              <span>
                Qty: {{ wo.quantity_expected || 0 }}
                <span v-if="wo.operation?.produces_bom_line?.product">
                  {{ wo.operation.produces_bom_line.product.uom || 'units' }} of {{ wo.operation.produces_bom_line.product.name }}
                </span>
                <span v-else>
                  {{ selectedMo?.product?.uom || 'units' }}
                </span>
              </span>
            </div>

            <!-- QA Required Badge -->
            <div v-if="wo.operation?.needs_quality_check" class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-2 text-sm">
                <span class="badge bg-yellow-100 text-yellow-800 flex items-center gap-1">
                  <Icon name="heroicons:clipboard-document-check" class="w-3.5 h-3.5" />
                  QA Required
                </span>
              </div>
              
              <!-- Auto QA Buttons or Status -->
              <div v-if="wo.status === 'done'">
                 <div v-if="wo.qa_status && wo.qa_status !== 'pending'" class="flex items-center gap-1 text-sm font-medium">
                    <span 
                        v-if="wo.qa_status === 'pass'" 
                        class="text-green-700 bg-green-50 px-2 py-1 rounded flex items-center gap-1 cursor-help"
                        :title="`Passed by ${wo.qa_user?.name || 'Unknown'} at ${wo.qa_at || 'N/A'}`"
                    >
                       <Icon name="heroicons:check-badge" class="w-4 h-4" />
                       QA Passed
                    </span>
                     <span 
                        v-else 
                        class="text-red-700 bg-red-50 px-2 py-1 rounded flex items-center gap-1 cursor-help" 
                        :title="`Failed by ${wo.qa_user?.name || 'Unknown'} at ${wo.qa_at || 'N/A'}. Reason: ${wo.qa_comments}`"
                    >
                       <Icon name="heroicons:x-circle" class="w-4 h-4" />
                       QA Failed
                    </span>
                    <!-- View Details Button -->
                     <button @click="openQaModal(wo, wo.qa_status)" class="text-xs text-gray-400 hover:text-gray-600 ml-1">
                        <Icon name="heroicons:information-circle" class="w-4 h-4" />
                     </button>
                 </div>
                 <div v-else class="flex gap-2">
                    <button 
                        @click="openQaModal(wo, 'pass')" 
                        :disabled="processingId === wo.id"
                        class="btn-xs bg-green-50 text-green-700 hover:bg-green-100 border border-green-200 flex items-center gap-1 rounded px-2 py-1"
                    >
                        <Icon name="heroicons:check" class="w-3 h-3" /> Pass
                    </button>
                     <button 
                        @click="openQaModal(wo, 'fail')" 
                        :disabled="processingId === wo.id"
                        class="btn-xs bg-red-50 text-red-700 hover:bg-red-100 border border-red-200 flex items-center gap-1 rounded px-2 py-1"
                    >
                        <Icon name="heroicons:x-mark" class="w-3 h-3" /> Fail
                    </button>
                 </div>
              </div>
            </div>

            <div v-else class="mb-3"></div>

            <!-- Time Progress -->
            <div class="mb-4">
              <div class="flex justify-between text-xs text-gray-500 mb-1">
                <span>Duration</span>
                <span>{{ formatDuration(getLiveDuration(wo)) }} / {{ formatDuration(wo.duration_expected) }}</span>
              </div>
              <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                <div 
                  class="h-full rounded-full transition-all"
                  :class="durationClass(wo)"
                  :style="{ width: `${Math.min(durationPercent(wo), 100)}%` }"
                ></div>
              </div>
            </div>

            <div class="flex gap-2 pt-3 border-t">
              <a
                v-if="wo.operation?.instruction_file_url"
                :href="getImageUrl(wo.operation.instruction_file_url)"
                target="_blank"
                class="flex-none"
              >
                <UiIconButton
                  icon="heroicons:document-text"
                  tooltip="View Instructions"
                  class="border border-primary-100"
                  color="text-primary-600 hover:text-primary-800"
                />
              </a>
              
              <button
                v-if="wo.status === 'pending'"
                disabled
                class="btn-outline text-xs flex-1 opacity-50 cursor-not-allowed justify-center"
              >
                <Icon name="heroicons:clock" class="w-3 h-3" />
                Pending
              </button>
              
              <button
                v-if="wo.status === 'ready'"
                @click="start(wo)"
                :disabled="processingId === wo.id"
                class="btn-outline text-xs flex-1 text-primary-600 border-primary-200 bg-primary-50 hover:bg-primary-100 justify-center"
              >
                <Icon v-if="processingId === wo.id" name="heroicons:arrow-path" class="w-3 h-3 animate-spin" />
                <Icon v-else name="heroicons:play" class="w-3 h-3" />
                Start
              </button>
              
               <button
                v-if="wo.status === 'in_progress'"
                @click="pause(wo)"
                :disabled="processingId === wo.id"
                class="btn-outline text-xs flex-1 text-amber-600 border-amber-200 bg-amber-50 hover:bg-amber-100 justify-center"
              >
                <Icon v-if="processingId === wo.id" name="heroicons:arrow-path" class="w-3 h-3 animate-spin" />
                <Icon v-else name="heroicons:pause" class="w-3 h-3" />
                Pause
              </button>
              
               <button
                v-if="wo.status === 'in_progress'"
                @click="openFinishModal(wo)"
                :disabled="processingId === wo.id"
                class="btn-outline text-xs flex-1 text-green-600 border-green-200 bg-green-50 hover:bg-green-100 justify-center"
              >
                <Icon v-if="processingId === wo.id" name="heroicons:arrow-path" class="w-3 h-3 animate-spin" />
                <Icon v-else name="heroicons:check" class="w-3 h-3" />
                Done
              </button>
              
               <button
                v-if="wo.status === 'paused'"
                @click="resume(wo)"
                :disabled="processingId === wo.id"
                class="btn-outline text-xs flex-1 text-primary-600 border-primary-200 bg-primary-50 hover:bg-primary-100 justify-center"
              >
                <Icon v-if="processingId === wo.id" name="heroicons:arrow-path" class="w-3 h-3 animate-spin" />
                <Icon v-else name="heroicons:play" class="w-3 h-3" />
                Resume
              </button>
               <button
                v-if="wo.status === 'paused'"
                @click="openFinishModal(wo)"
                :disabled="processingId === wo.id"
                class="btn-outline text-xs flex-1 text-green-600 border-green-200 bg-green-50 hover:bg-green-100 justify-center"
              >
                <Icon v-if="processingId === wo.id" name="heroicons:arrow-path" class="w-3 h-3 animate-spin" />
                <Icon v-else name="heroicons:check" class="w-3 h-3" />
                Done
              </button>
              
              <div v-if="wo.status === 'done'" class="flex-1 text-center text-sm text-green-600 font-medium py-2">
                <Icon name="heroicons:check-circle" class="w-4 h-4 inline" />
                Completed
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <UiEmptyState 
          v-if="!loadingWos && moWorkOrders.length === 0"
          title="No work orders found" 
          description="This manufacturing order has no work orders yet. Work orders are created when you confirm the MO."
          icon="heroicons:clipboard-document-list"
        />
      </div>
    </UiSlideOver>

    <!-- Finish Modal -->
    <UiSlideOver v-model="showFinishModal" title="Complete Work Order" width="sm:w-[400px]">
      <form id="finish-form" @submit.prevent="confirmFinish" class="space-y-4">
        <div v-if="finishingWo" class="bg-gray-50 rounded-lg p-3 text-sm text-gray-600">
          <span class="font-medium text-gray-800">{{ finishingWo.operation?.name }}</span>
          <span class="mx-2 text-gray-400">·</span>
          Expected: <strong>{{ Number(finishingWo.quantity_expected) }}</strong>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Actual Quantity Produced</label>
          <input v-model.number="finishForm.quantity" type="number" step="0.0001" class="input" required min="0" />
          <p class="text-xs text-gray-400 mt-1">Pre-filled from expected qty. Change only if actual differs.</p>
        </div>
      </form>
      <template #footer>
        <div class="flex justify-end gap-3">
          <button type="button" @click="showFinishModal = false" class="btn-ghost">Cancel</button>
          <button type="submit" form="finish-form" class="btn-primary" :disabled="processingId === finishingWo?.id">Complete</button>
        </div>
      </template>
    </UiSlideOver>

    <!-- QA Modal -->
    <UiSlideOver v-model="showQaModal" title="Quality Check" width="sm:w-[400px]">
      <form id="qa-form" @submit.prevent="submitQa" class="space-y-6">
          <!-- Status Selection -->
          <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Result</label>
              <div class="flex gap-4">
                  <label class="flex-1 cursor-pointer">
                      <input type="radio" v-model="qaForm.status" value="pass" class="peer sr-only" />
                      <div class="border rounded-lg p-3 text-center transition-all peer-checked:border-green-500 peer-checked:bg-green-50 peer-checked:text-green-700 border-gray-200 hover:bg-gray-50">
                          <Icon name="heroicons:check-circle" class="w-6 h-6 mx-auto mb-1" />
                          <span class="font-medium">Pass</span>
                      </div>
                  </label>
                   <label class="flex-1 cursor-pointer">
                      <input type="radio" v-model="qaForm.status" value="fail" class="peer sr-only" />
                      <div class="border rounded-lg p-3 text-center transition-all peer-checked:border-red-500 peer-checked:bg-red-50 peer-checked:text-red-700 border-gray-200 hover:bg-gray-50">
                          <Icon name="heroicons:x-circle" class="w-6 h-6 mx-auto mb-1" />
                          <span class="font-medium">Fail</span>
                      </div>
                  </label>
              </div>
          </div>

          <!-- Comments/Reason -->
          <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                  {{ qaForm.status === 'fail' ? 'Failure Reason (Required)' : 'Comments (Optional)' }}
              </label>
              <textarea 
                  v-model="qaForm.comments" 
                  class="input min-h-[100px]" 
                  :placeholder="qaForm.status === 'fail' ? 'Describe the defect...' : 'Any observations...'"
                  :required="qaForm.status === 'fail'"
              ></textarea>
          </div>

      </form>
      <template #footer>
        <div class="flex justify-end gap-3">
          <button type="button" @click="showQaModal = false" class="btn-ghost">Cancel</button>
          <button type="submit" form="qa-form" class="btn-primary">Save Check</button>
        </div>
      </template>
    </UiSlideOver>
  </div>
</template>

<script setup lang="ts">
import { useExecutionStore } from '~/stores/execution'
import type { ManufacturingOrder, WorkOrder } from '~/types/models'

const executionStore = useExecutionStore()

const { $api } = useApi()
const toast = useToast()
const { getImageUrl, formatDate } = useUtils()

// Data Table (Client-side) — MO-based
const loading = ref(true)
const search = ref('')
const filters = ref({ status: '' })
const page = ref(1)
const perPage = ref(10)

const mos = computed(() => executionStore.manufacturingOrders as ManufacturingOrder[])
const allWorkOrders = computed(() => executionStore.workOrders as WorkOrder[])

// Client-side Counts (MO status counts)
const counts = computed<Record<string, number>>(() => {
  const list = mos.value.filter(m => m.status !== 'draft')
  return {
    all: list.length,
    confirmed: list.filter(m => m.status === 'confirmed').length,
    in_progress: list.filter(m => m.status === 'in_progress').length,
    done: list.filter(m => m.status === 'done').length,
    scheduled: list.filter(m => m.status === 'scheduled').length,
  }
})

// Filtered & Sorted Items
const filteredItems = computed(() => {
  // Work orders only exist on confirmed/in_progress/done/scheduled MOs — exclude draft
  let result = mos.value.filter(m => m.status !== 'draft')

  // Status Filter
  if (filters.value.status) {
    result = result.filter(m => m.status === filters.value.status)
  }

  // Search Filter
  if (search.value) {
    const q = search.value.toLowerCase()
    result = result.filter(m => 
      m.name?.toLowerCase().includes(q) || 
      m.product?.name?.toLowerCase().includes(q) ||
      m.product?.code?.toLowerCase().includes(q)
    )
  }

  // Sort by created_at desc
  return result.sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
})

const total = computed(() => filteredItems.value.length)

const tableOrders = computed(() => {
  const start = (page.value - 1) * perPage.value
  return filteredItems.value.slice(start, start + perPage.value)
})

function getWoCountForMo(moId: number): number {
  return allWorkOrders.value.filter(w => w.manufacturing_order_id === moId).length
}

async function refresh(force = false) {
  loading.value = true
  try {
    await Promise.all([
      executionStore.fetchManufacturingOrders(force),
      executionStore.fetchWorkOrders(force),
    ])
  } finally {
    loading.value = false
  }
}

onMounted(() => refresh())

// MO Detail SlideOver
const showDetailModal = ref(false)
const selectedMo = ref<ManufacturingOrder | null>(null)
const moWorkOrders = ref<WorkOrder[]>([])
const loadingWos = ref(false)

async function openMoDetail(mo: ManufacturingOrder) {
  selectedMo.value = mo
  showDetailModal.value = true
  loadingWos.value = true

  try {
    moWorkOrders.value = allWorkOrders.value
      .filter(w => w.manufacturing_order_id === mo.id)
      .sort((a, b) => (a.operation?.sequence || a.sequence || 0) - (b.operation?.sequence || b.sequence || 0))
  } finally {
    loadingWos.value = false
  }
}

// Progress helpers
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

// Live duration calculation
const now = useNow()

function getLiveDuration(wo: WorkOrder) {
  if (wo.status !== 'in_progress' || !wo.started_at) return wo.duration_actual
  const start = new Date(wo.started_at).getTime()
  if (isNaN(start)) return Number(wo.duration_actual) || 0
  const current = now.value.getTime()
  const diffMinutes = Math.max(0, (current - start) / 1000 / 60)
  return (Number(wo.duration_actual) || 0) + diffMinutes
}

function formatDuration(minutes: number) {
  if (isNaN(minutes) || !minutes) return '00:00'
  const seconds = Math.floor(minutes * 60)
  const h = Math.floor(seconds / 3600)
  const m = Math.floor((seconds % 3600) / 60)
  const s = seconds % 60
  
  const hStr = h > 0 ? `${h}:` : ''
  const mStr = h > 0 ? m.toString().padStart(2, '0') : m.toString()
  const sStr = s.toString().padStart(2, '0')
  return h > 0 ? `${hStr}${mStr}:${sStr}` : `${mStr}:${sStr}`
}

function durationPercent(wo: WorkOrder) {
  if (!wo.duration_expected) return 0
  return (wo.duration_actual / wo.duration_expected) * 100
}

function durationClass(wo: WorkOrder) {
  const percent = durationPercent(wo)
  if (wo.status === 'done') return 'bg-green-500'
  if (percent > 100) return 'bg-red-500'
  if (percent > 80) return 'bg-amber-500'
  return 'bg-primary-500'
}

// Actions State
const processingId = ref<number | null>(null)

// Finish Modal
const showFinishModal = ref(false)
const finishingWo = ref<WorkOrder | null>(null)
const finishForm = ref({ quantity: 0 })

async function openFinishModal(wo: WorkOrder) {
  finishingWo.value = wo
  // Default quantity to target if not already set
  finishForm.value = { quantity: Number(wo.quantity_expected || wo.manufacturing_order?.qty_to_produce || 0) }
  
  if (wo.status === 'in_progress') {
    processingId.value = wo.id
    try {
      await $api(`/work-orders/${wo.id}/pause`, { method: 'POST' })
      toast.info('Timer paused for completion')
      await refresh(true)
      
      // Update local reference to the now-paused WO
      if (selectedMo.value) {
        moWorkOrders.value = allWorkOrders.value
          .filter(w => w.manufacturing_order_id === selectedMo.value!.id)
          .sort((a, b) => (a.sequence || 0) - (b.sequence || 0))
        const current = moWorkOrders.value.find(w => w.id === wo.id)
        if (current) finishingWo.value = current
      }
    } catch (e) {
      console.error('Failed to pause WO', e)
    } finally {
      processingId.value = null
    }
  }
  
  showFinishModal.value = true
}

// Action Handlers
async function handleAction(wo: WorkOrder, action: () => Promise<void>, successMsg: string) {
    processingId.value = wo.id
    try {
        await action()
        toast.success(successMsg)
        await refresh(true)
        // Re-open the detail with updated data
        if (selectedMo.value) {
          moWorkOrders.value = allWorkOrders.value
            .filter(w => w.manufacturing_order_id === selectedMo.value!.id)
            .sort((a, b) => (a.sequence || 0) - (b.sequence || 0))
          // Update selected MO with latest data
          const updatedMo = mos.value.find(m => m.id === selectedMo.value!.id)
          if (updatedMo) selectedMo.value = updatedMo
        }
    } catch (e: any) {
        toast.error(e.data?.message || 'Action failed')
    } finally {
        processingId.value = null
    }
}

function start(wo: WorkOrder) {
    handleAction(wo, () => $api(`/work-orders/${wo.id}/start`, { method: 'POST' }), 'Work order started')
}

function pause(wo: WorkOrder) {
    handleAction(wo, () => $api(`/work-orders/${wo.id}/pause`, { method: 'POST' }), 'Work order paused')
}

function resume(wo: WorkOrder) {
    handleAction(wo, () => $api(`/work-orders/${wo.id}/resume`, { method: 'POST' }), 'Work order resumed')
}

async function confirmFinish() {
  if (!finishingWo.value) return
  processingId.value = finishingWo.value.id
  try {
    await $api(`/work-orders/${finishingWo.value.id}/finish`, { 
      method: 'POST',
      body: { quantity_produced: finishForm.value.quantity }
    })
    toast.success('Work order completed')
    showFinishModal.value = false
    await refresh(true)
    // Re-open the detail with updated data
    if (selectedMo.value) {
      moWorkOrders.value = allWorkOrders.value
        .filter(w => w.manufacturing_order_id === selectedMo.value!.id)
        .sort((a, b) => (a.sequence || 0) - (b.sequence || 0))
      const updatedMo = mos.value.find(m => m.id === selectedMo.value!.id)
      if (updatedMo) selectedMo.value = updatedMo
    }
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to finish')
  } finally {
    processingId.value = null
  }
}

// QA Handling
const showQaModal = ref(false)
const qaTargetWo = ref<WorkOrder | null>(null)
const qaForm = ref({ status: 'pass' as 'pass' | 'fail', comments: '' })

function openQaModal(wo: WorkOrder, status: 'pass' | 'fail' = 'pass') {
  qaTargetWo.value = wo
  qaForm.value = { status, comments: wo.qa_comments || '' }
  showQaModal.value = true
}

async function submitQa() {
    if (!qaTargetWo.value) return
    processingId.value = qaTargetWo.value.id
    try {
        if (qaForm.value.status === 'fail' && !qaForm.value.comments) {
            toast.error('Reason is required for failure')
            return
        }
        await $api(`/work-orders/${qaTargetWo.value.id}`, {
            method: 'PUT',
            body: { qa_status: qaForm.value.status, qa_comments: qaForm.value.comments }
        })
        toast.success(`QA Marked as ${qaForm.value.status.toUpperCase()}`)
        showQaModal.value = false
        await refresh(true)
        if (selectedMo.value) {
          moWorkOrders.value = allWorkOrders.value
            .filter(w => w.manufacturing_order_id === selectedMo.value!.id)
            .sort((a, b) => (a.sequence || 0) - (b.sequence || 0))
        }
    } catch(e) {
        toast.error('Failed to update QA status')
    } finally {
        processingId.value = null
    }
}
</script>
