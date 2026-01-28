<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Work Orders</h1>
        <p class="text-gray-500 mt-1 hidden sm:block">Track and execute production operations</p>
      </div>
    </div>

    <!-- Tabs & Search -->
    <div class="flex flex-col gap-4">
      <!-- Tabs -->
      <div class="border-b border-gray-200 overflow-x-auto scrollbar-hide -mx-4 px-4 md:mx-0 md:px-0">
        <nav class="-mb-px flex gap-2">
          <button 
            v-for="status in ['all', 'ready', 'in_progress', 'paused', 'pending', 'done']"
            :key="status"
            @click="filterStatus = status === 'all' ? '' : status"
            :class="[
              (filterStatus === status || (status === 'all' && filterStatus === ''))
                ? 'border-primary-500 text-primary-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
              'whitespace-nowrap px-4 py-2 border-b-2 font-medium text-sm transition-colors capitalize'
            ]"
          >
            {{ status.replace('_', ' ') }}
            <span class="ml-2 py-0.5 px-2 rounded-full text-xs" 
              :class="[
                (filterStatus === status || (status === 'all' && filterStatus === '')) 
                  ? (status === 'all' ? 'bg-gray-100 text-gray-800' : 
                     status === 'ready' ? 'bg-blue-100 text-blue-700' :
                     status === 'in_progress' ? 'bg-primary-100 text-primary-700' :
                     status === 'paused' ? 'bg-amber-100 text-amber-700' :
                     status === 'done' ? 'bg-green-100 text-green-700' :
                     'bg-gray-100 text-gray-600')
                  : (status === 'ready' ? 'bg-blue-50 text-blue-600' :
                     status === 'in_progress' ? 'bg-primary-50 text-primary-600' :
                     status === 'paused' ? 'bg-amber-50 text-amber-600' :
                     status === 'done' ? 'bg-green-50 text-green-600' :
                     'bg-gray-100 text-gray-600')
              ]">
              {{ countByStatus(status === 'all' ? '' : status) }}
            </span>
          </button>
        </nav>
      </div>

      <!-- Search -->
      <div class="flex">
        <input v-model="search" type="text" placeholder="Search by MO or work center..." class="input max-w-xs" />
      </div>
    </div>

    <!-- Work Order Cards (Kanban-like) -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
      <div v-for="wo in paginatedWorkOrders" :key="wo.id" class="card">
        <!-- Header -->
        <div class="flex items-start justify-between mb-3">
          <div>
            <div class="flex items-center gap-2 mb-1">
              <span class="text-sm font-mono text-gray-500">#{{ wo.id }}</span>
              <UiStatusBadge :status="wo.status" />
            </div>
            <h3 class="font-medium text-gray-800">{{ wo.operation?.name || 'Operation' }}</h3>
          </div>
          <span class="text-xs bg-gray-100 px-2 py-1 rounded font-mono">
            {{ wo.manufacturing_order?.name || `MO #${wo.manufacturing_order_id}` }}
          </span>
        </div>

        <!-- Work Center -->
        <div class="flex items-center gap-2 text-sm text-gray-600 mb-2">
          <Icon name="heroicons:cog-6-tooth" class="w-4 h-4 text-gray-400" />
          <span>{{ wo.work_center?.name || 'N/A' }}</span>
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
                    :disabled="loading === wo.id"
                    class="btn-xs bg-green-50 text-green-700 hover:bg-green-100 border border-green-200 flex items-center gap-1 rounded px-2 py-1"
                >
                    <Icon name="heroicons:check" class="w-3 h-3" /> Pass
                </button>
                 <button 
                    @click="openQaModal(wo, 'fail')" 
                    :disabled="loading === wo.id"
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
          <UiIconButton
            v-if="wo.operation?.instruction_file_url"
            tag="a"
            :href="getImageUrl(wo.operation.instruction_file_url)"
            target="_blank"
            icon="heroicons:document-text"
            tooltip="View Instructions"
            class="flex-none border border-primary-100"
            color="text-primary-600 hover:text-primary-800"
          />
          
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
            :disabled="loading === wo.id"
            class="btn-outline text-xs flex-1 text-primary-600 border-primary-200 bg-primary-50 hover:bg-primary-100 justify-center"
          >
            <Icon v-if="loading === wo.id" name="heroicons:arrow-path" class="w-3 h-3 animate-spin" />
            <Icon v-else name="heroicons:play" class="w-3 h-3" />
            Start
          </button>
          
           <button
            v-if="wo.status === 'in_progress'"
            @click="pause(wo)"
            :disabled="loading === wo.id"
            class="btn-outline text-xs flex-1 text-amber-600 border-amber-200 bg-amber-50 hover:bg-amber-100 justify-center"
          >
            <Icon v-if="loading === wo.id" name="heroicons:arrow-path" class="w-3 h-3 animate-spin" />
            <Icon v-else name="heroicons:pause" class="w-3 h-3" />
            Pause
          </button>
          
           <button
            v-if="wo.status === 'in_progress'"
            @click="openFinishModal(wo)"
            :disabled="loading === wo.id"
            class="btn-outline text-xs flex-1 text-green-600 border-green-200 bg-green-50 hover:bg-green-100 justify-center"
          >
            <Icon v-if="loading === wo.id" name="heroicons:arrow-path" class="w-3 h-3 animate-spin" />
            <Icon v-else name="heroicons:check" class="w-3 h-3" />
            Done
          </button>
          
           <button
            v-if="wo.status === 'paused'"
            @click="resume(wo)"
            :disabled="loading === wo.id"
            class="btn-outline text-xs flex-1 text-primary-600 border-primary-200 bg-primary-50 hover:bg-primary-100 justify-center"
          >
            <Icon v-if="loading === wo.id" name="heroicons:arrow-path" class="w-3 h-3 animate-spin" />
            <Icon v-else name="heroicons:play" class="w-3 h-3" />
            Resume
          </button>
           <button
            v-if="wo.status === 'paused'"
            @click="openFinishModal(wo)"
            :disabled="loading === wo.id"
            class="btn-outline text-xs flex-1 text-green-600 border-green-200 bg-green-50 hover:bg-green-100 justify-center"
          >
            <Icon v-if="loading === wo.id" name="heroicons:arrow-path" class="w-3 h-3 animate-spin" />
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
      v-if="filteredWorkOrders.length === 0"
      title="No work orders found" 
      description="Work orders are created when you confirm a Manufacturing Order"
      icon="heroicons:clipboard-document-list"
    />

    <!-- Pagination -->
    <UiPagination
      v-if="totalPages > 1"
      v-model="currentPage"
      :total-items="filteredWorkOrders.length"
      :page-size="pageSize"
    />
  </div>

  <!-- Finish Modal (Refactored to SlideOver for consistency) -->
  <UiSlideOver v-model="showFinishModal" title="Complete Work Order" width="sm:w-[400px]">
    <form @submit.prevent="confirmFinish" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Quantity Produced</label>
        <input v-model.number="finishForm.quantity" type="number" step="0.0001" class="input" required min="0" />
        <p class="text-xs text-gray-500 mt-1">Enter the actual quantity produced in this step.</p>
      </div>
      <div class="flex justify-end gap-3 pt-4">
        <button type="button" @click="showFinishModal = false" class="btn-ghost">Cancel</button>
        <button type="submit" class="btn-primary" :disabled="loading === finishingWo?.id">
          Complete
        </button>
      </div>
    </form>
  </UiSlideOver>

  <!-- QA Modal -->
  <UiSlideOver v-model="showQaModal" title="Quality Check" width="sm:w-[400px]">
      <form @submit.prevent="submitQa" class="space-y-6">
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

          <div class="flex justify-end gap-3 pt-4 border-t">
               <button type="button" @click="showQaModal = false" class="btn-ghost">Cancel</button>
                <button type="submit" class="btn-primary">
                  Save Check
                </button>
          </div>
      </form>
  </UiSlideOver>



</template>

<script setup lang="ts">
import type { WorkOrder } from '~/types/models'

const { $api } = useApi()
const toast = useToast()
const { getImageUrl } = useUtils()

const workOrders = ref<WorkOrder[]>([])
const search = ref('')
const filterStatus = ref('')
const loading = ref<number | null>(null)
const currentPage = ref(1)
const pageSize = 12



const filteredWorkOrders = computed(() => {
  let filtered = workOrders.value.filter(wo => {
    const matchesSearch = !search.value || 
      wo.work_center?.name?.toLowerCase().includes(search.value.toLowerCase()) ||
      wo.manufacturing_order?.name?.toLowerCase().includes(search.value.toLowerCase())
    const matchesStatus = !filterStatus.value || wo.status === filterStatus.value
    return matchesSearch && matchesStatus
  })
  const statusOrder: Record<string, number> = { in_progress: 0, ready: 1, paused: 2, pending: 3, done: 4 }
  return filtered.sort((a, b) => (statusOrder[a.status] ?? 5) - (statusOrder[b.status] ?? 5))
})

function countByStatus(status: string) {
  if (!status) return workOrders.value.length
  return workOrders.value.filter(wo => wo.status === status).length
}

const totalPages = computed(() => Math.ceil(filteredWorkOrders.value.length / pageSize))
const paginatedWorkOrders = computed(() => {
  const start = (currentPage.value - 1) * pageSize
  return filteredWorkOrders.value.slice(start, start + pageSize)
})

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

async function fetchWorkOrders() {
  try {
    const res = await $api<{ data: WorkOrder[] }>('/work-orders')
    workOrders.value = res.data || []
  } catch (e) {
    toast.error('Failed to fetch work orders')
  }
}

const now = useNow()

// Live duration calculation
function getLiveDuration(wo: WorkOrder) {
  if (wo.status !== 'in_progress' || !wo.started_at) return wo.duration_actual
  
  // Parse started_at (assuming UTC/ISO)
  const start = new Date(wo.started_at).getTime()
  if (isNaN(start)) return Number(wo.duration_actual) || 0

  const current = now.value.getTime()
  const diffMinutes = Math.max(0, (current - start) / 1000 / 60)
  
  return (Number(wo.duration_actual) || 0) + diffMinutes
}

const showFinishModal = ref(false)
const finishingWo = ref<WorkOrder | null>(null)
const finishForm = ref({ quantity: 0 })

function openFinishModal(wo: WorkOrder) {
  finishingWo.value = wo
  // Default to expected qty?
  // We don't have expected qty on WO directly here easily (it might be calc from MO qty)
  // Just default to 1 or 0
  finishForm.value = { quantity: 0 }
  showFinishModal.value = true
}

async function start(wo: WorkOrder) {
  loading.value = wo.id
  try {
    const res = await $api<{data: WorkOrder}>(`/work-orders/${wo.id}/start`, { method: 'POST' })
    // Update local state immediately
    const index = workOrders.value.findIndex(w => w.id === wo.id)
    if (index !== -1 && res) {
       // Just reload everything to be safe or update local
       await fetchWorkOrders() 
    }
    toast.success('Work order started')
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to start')
  } finally {
    loading.value = null
  }
}

async function pause(wo: WorkOrder) {
  loading.value = wo.id
  try {
    await $api(`/work-orders/${wo.id}/pause`, { method: 'POST' })
    toast.success('Work order paused')
    await fetchWorkOrders()
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to pause')
  } finally {
    loading.value = null
  }
}

async function resume(wo: WorkOrder) {
  loading.value = wo.id
  try {
    await $api(`/work-orders/${wo.id}/resume`, { method: 'POST' })
    toast.success('Work order resumed')
    await fetchWorkOrders()
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to resume')
  } finally {
    loading.value = null
  }
}

async function confirmFinish() {
  if (!finishingWo.value) return
  loading.value = finishingWo.value.id
  try {
    await $api(`/work-orders/${finishingWo.value.id}/finish`, { 
      method: 'POST',
      body: { quantity_produced: finishForm.value.quantity }
    })
    toast.success('Work order completed')
    showFinishModal.value = false
    await fetchWorkOrders()
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to finish')
  } finally {
    loading.value = null
  }
}

// QA Handling
const showQaModal = ref(false)
const qaTargetWo = ref<WorkOrder | null>(null)
const qaForm = ref({ status: 'pass' as 'pass' | 'fail', comments: '' })

function openQaModal(wo: WorkOrder, status: 'pass' | 'fail' = 'pass') {
  qaTargetWo.value = wo
  qaForm.value = { 
      status, 
      comments: wo.qa_comments || '' 
  }
  showQaModal.value = true
}

async function submitQa() {
    if (!qaTargetWo.value) return
    loading.value = qaTargetWo.value.id
    
    try {
        if (qaForm.value.status === 'fail' && !qaForm.value.comments) {
            toast.error('Reason is required for failure')
            return
        }

        await $api(`/work-orders/${qaTargetWo.value.id}`, {
            method: 'PUT',
            body: {
                qa_status: qaForm.value.status,
                qa_comments: qaForm.value.comments
            }
        })
        toast.success(`QA Marked as ${qaForm.value.status.toUpperCase()}`)
        showQaModal.value = false
        await fetchWorkOrders()
    } catch(e) {
        toast.error('Failed to update QA status')
    } finally {
        loading.value = null
    }
}

onMounted(fetchWorkOrders)
</script>
