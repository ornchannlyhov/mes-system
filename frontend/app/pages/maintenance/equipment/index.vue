<template>
  <div class="space-y-6">
    <div class="page-header">
      <div>
        <h1 class="page-title">Equipment</h1>
        <p class="text-gray-500 mt-1 hidden sm:block">Manage production equipment and machines</p>
      </div>
      <button class="btn-primary" @click="openModal()">
        <Icon name="heroicons:plus" class="w-4 h-4" />
        <span class="hidden sm:inline">Add Equipment</span>
        <span class="sm:hidden">Add</span>
      </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
      <div v-for="eq in paginatedEquipment" :key="eq.id" class="card">
        <div class="flex items-start justify-between">
          <div class="flex items-center gap-3">
            <div :class="statusIconClass(eq.status)" class="w-12 h-12 rounded-xl flex items-center justify-center">
              <Icon name="heroicons:cog-6-tooth" class="w-6 h-6" />
            </div>
            <div>
              <h3 class="font-semibold text-gray-800">{{ eq.name }}</h3>
              <p class="text-sm text-gray-500 font-mono">{{ eq.code }}</p>
            </div>
          </div>
          <UiStatusBadge :status="eq.status" />
        </div>
        
        <div class="mt-4 pt-4 border-t border-gray-100 space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-gray-500">Last Maintenance</span>
            <span>{{ formatDate(eq.last_maintenance, 'Not scheduled') }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500">Next Maintenance</span>
            <span>{{ formatDate(eq.next_maintenance, 'Not scheduled') }}</span>
          </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
          <button 
            v-if="eq.status === 'operational'"
            @click="openScheduleModal(eq)" 
            class="btn-outline text-xs flex-1"
          >
            <Icon name="heroicons:calendar" class="w-3 h-3" />
            Schedule
          </button>
          <button 
            v-if="eq.status === 'operational'"
            @click="confirmReportBroken(eq)" 
            class="btn-outline text-xs flex-1 text-red-600 border-red-300 hover:bg-red-50"
          >
            <Icon name="heroicons:exclamation-triangle" class="w-3 h-3" />
            Report Issue
          </button>
          <button 
            v-if="eq.status === 'maintenance' || eq.status === 'broken'"
            @click="markMaintenanceDone(eq)" 
            class="btn-primary text-xs flex-1"
          >
            <Icon name="heroicons:check" class="w-3 h-3" />
            Mark Done
          </button>
          <UiIconButton
            @click="openModal(eq)"
            icon="heroicons:pencil"
            tooltip="Edit Equipment"
          />
          <UiIconButton
            @click="confirmDelete(eq)"
            icon="heroicons:trash"
            tooltip="Delete Equipment"
            color="text-red-400 hover:text-red-600"
          />
        </div>
      </div>

      <div v-if="loading" v-for="i in 6" :key="`skel-${i}`" class="card animate-pulse">
        <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-gray-200 rounded-xl"></div>
                <div>
                    <div class="h-4 bg-gray-200 rounded w-32 mb-1"></div>
                    <div class="h-3 bg-gray-200 rounded w-20"></div>
                </div>
            </div>
            <div class="h-6 bg-gray-200 rounded w-16"></div>
        </div>
        <div class="mt-4 pt-4 border-t border-gray-100 space-y-2">
             <div class="flex justify-between"><div class="h-3 bg-gray-200 rounded w-24"></div><div class="h-3 bg-gray-200 rounded w-24"></div></div>
             <div class="flex justify-between"><div class="h-3 bg-gray-200 rounded w-24"></div><div class="h-3 bg-gray-200 rounded w-24"></div></div>
        </div>
        <div class="mt-4 flex gap-2">
             <div class="h-8 bg-gray-200 rounded flex-1"></div>
             <div class="h-8 bg-gray-200 rounded flex-1"></div>
        </div>
      </div>

      <div v-if="equipment.length === 0 && !loading" class="col-span-full">
        <UiEmptyState 
          title="No equipment found" 
          description="Add equipment to track maintenance and report issues."
          icon="heroicons:cog"
        >
          <button class="btn-primary" @click="openModal()">
            <Icon name="heroicons:plus" class="w-4 h-4" />
            Add Equipment
          </button>
        </UiEmptyState>
      </div>
    </div>

    <!-- Pagination -->
    <UiPagination
      v-if="equipment.length > pageSize"
      v-model="currentPage"
      :total-items="equipment.length"
      :page-size="pageSize"
    />

    <UiSlideOver v-model="showModal" :title="editing ? 'Edit Equipment' : 'Add Equipment'">
      <form @submit.prevent="save" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Code</label>
            <input v-model="form.code" type="text" class="input" required />
          </div>
          <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
            <input v-model="form.name" type="text" class="input" required />
          </div>
          <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select v-model="form.status" class="input">
              <option value="operational">Operational</option>
              <option value="maintenance">Maintenance</option>
              <option value="broken">Broken</option>
            </select>
          </div>
        </div>
        <div class="flex justify-end gap-3 mt-6">
          <button type="button" @click="showModal = false" class="btn-ghost">Cancel</button>
          <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Saving...' : 'Save' }}</button>
        </div>
      </form>
    </UiSlideOver>

    <!-- Report Broken SlideOver -->
    <UiSlideOver v-model="showReportModal" title="Report Equipment Issue">
      <form @submit.prevent="reportBroken" class="space-y-4">
        <div class="bg-yellow-50 p-4 rounded-lg flex gap-3 text-yellow-700 text-sm mb-4">
          <Icon name="heroicons:exclamation-triangle" class="w-5 h-5 shrink-0" />
          <p>
            You are reporting <strong>{{ reportingItem?.name }}</strong> as broken. 
            This will mark the equipment status as 'broken' and create a corrective maintenance request.
          </p>
        </div>

        <div>
           <label class="block text-sm font-medium text-gray-700 mb-1">Issue Description</label>
           <textarea v-model="reportForm.description" rows="3" class="input" placeholder="Describe the issue..." required></textarea>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
          <select v-model="reportForm.priority" class="input">
            <option value="low">Low</option>
            <option value="normal">Normal</option>
            <option value="high">High</option>
            <option value="urgent">Urgent</option>
          </select>
        </div>

        <div class="flex justify-end gap-3 result mt-6">
          <button type="button" @click="showReportModal = false" class="btn-ghost">Cancel</button>
          <button type="submit" class="btn-primary bg-red-600 hover:bg-red-700 border-red-600" :disabled="reporting">
            {{ reporting ? 'Reporting...' : 'Report Broken' }}
          </button>
        </div>
      </form>
    </UiSlideOver>

    <!-- Schedule Maintenance SlideOver -->
    <UiSlideOver v-model="showScheduleModal" title="Schedule Maintenance">
      <form @submit.prevent="scheduleMaintenance" class="space-y-6">
        <div class="p-3 bg-gray-50 rounded-lg">
          <p class="text-sm text-gray-600">Equipment: <span class="font-medium">{{ schedulingItem?.name }}</span></p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Schedule Name</label>
          <input v-model="scheduleForm.name" type="text" class="input" placeholder="e.g. Monthly Inspection" required />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Next Maintenance Date</label>
          <input v-model="scheduleForm.next_maintenance" type="date" class="input" required />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Repeat Interval (days)</label>
          <input v-model.number="scheduleForm.interval_days" type="number" min="1" class="input" placeholder="e.g. 30" required />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Instructions</label>
          <textarea v-model="scheduleForm.instructions" rows="3" class="input" placeholder="Describe the maintenance work..."></textarea>
        </div>

        <div class="flex justify-end gap-3 mt-6">
          <button type="button" @click="showScheduleModal = false" class="btn-ghost">Cancel</button>
          <button type="submit" class="btn-primary" :disabled="scheduling">
            {{ scheduling ? 'Scheduling...' : 'Create Schedule' }}
          </button>
        </div>
      </form>
    </UiSlideOver>

    <!-- Delete Confirmation Modal -->
    <UiConfirmModal
      v-model="showDeleteModal"
      title="Delete Equipment"
      :message="`Are you sure you want to delete this equipment? This action cannot be undone.`"
      confirm-text="Delete"
      variant="danger"
      :loading="deleting"
      @confirm="handleDelete"
    />
  </div>
</template>

<script setup lang="ts">
import type { Equipment } from '~/types/models'

const { $api } = useApi()
const toast = useToast()
const { formatDate } = useUtils()

const equipment = ref<Equipment[]>([])
const showModal = ref(false)
const editing = ref<Equipment | null>(null)
const currentPage = ref(1)
const pageSize = 10
const saving = ref(false)
const loading = ref(true)

const paginatedEquipment = computed(() => {
  const start = (currentPage.value - 1) * pageSize
  return equipment.value.slice(start, start + pageSize)
})

// Report broken state
const showReportModal = ref(false)
const reportingItem = ref<Equipment | null>(null)
const reporting = ref(false)

// Schedule maintenance state
const showScheduleModal = ref(false)
const schedulingItem = ref<Equipment | null>(null)
const scheduling = ref(false)
const scheduleForm = ref({
  name: '',
  next_maintenance: '',
  interval_days: 30,
  instructions: ''
})

const form = ref({ code: '', name: '', status: 'operational' as 'operational' | 'maintenance' | 'broken' })

function statusIconClass(status: string) {
  return {
    operational: 'bg-green-100 text-green-600',
    maintenance: 'bg-yellow-100 text-yellow-600',
    broken: 'bg-red-100 text-red-600',
  }[status] || 'bg-gray-100 text-gray-600'
}

async function fetchEquipment() {
  loading.value = true
  try {
    const res = await $api<{ data: Equipment[] }>('/equipment')
    equipment.value = res.data || []
  } catch (e) {
    toast.error('Failed to fetch equipment')
  } finally {
    loading.value = false
  }
}

function openModal(eq?: Equipment) {
  if (eq) {
    editing.value = eq
    form.value = { code: eq.code, name: eq.name, status: eq.status }
  } else {
    editing.value = null
    form.value = { code: '', name: '', status: 'operational' }
  }
  showModal.value = true
}

async function save() {
  saving.value = true
  try {
    if (editing.value) {
      await $api(`/equipment/${editing.value.id}`, { method: 'PUT', body: form.value })
      toast.success('Equipment updated successfully')
    } else {
      await $api('/equipment', { method: 'POST', body: form.value })
      toast.success('Equipment created successfully')
    }
    showModal.value = false
    await fetchEquipment()
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to save equipment')
  } finally {
    saving.value = false
  }
}

const reportForm = ref({ description: '', priority: 'normal' })

function confirmReportBroken(eq: Equipment) {
  reportingItem.value = eq
  reportForm.value = { description: '', priority: 'normal' }
  showReportModal.value = true
}

async function reportBroken() {
  if (!reportingItem.value) return
  reporting.value = true
  try {
    await $api(`/equipment/${reportingItem.value.id}/report-broken`, { 
      method: 'POST',
      body: {
        description: reportForm.value.description,
        priority: reportForm.value.priority
      } 
    })
    toast.success('Equipment reported as broken')
    showReportModal.value = false
    await fetchEquipment()
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to report equipment')
  } finally {
    reporting.value = false
  }
}

function openScheduleModal(eq: Equipment) {
  schedulingItem.value = eq
  scheduleForm.value = { 
    name: '',
    next_maintenance: '', 
    interval_days: 30, 
    instructions: '' 
  }
  showScheduleModal.value = true
}

async function scheduleMaintenance() {
  if (!schedulingItem.value) return
  scheduling.value = true
  try {
    const today = new Date().toISOString().split('T')[0]
    const isToday = scheduleForm.value.next_maintenance === today
    
    // Create a maintenance schedule for this equipment
    await $api('/maintenance/schedules', {
      method: 'POST',
      body: {
        equipment_id: schedulingItem.value.id,
        name: scheduleForm.value.name,
        trigger_type: 'time',
        interval_days: scheduleForm.value.interval_days,
        next_maintenance: scheduleForm.value.next_maintenance,
        instructions: scheduleForm.value.instructions,
        is_active: true
      }
    })
    
    // Update equipment's next_maintenance date and status if scheduled for today
    await $api(`/equipment/${schedulingItem.value.id}`, { 
      method: 'PUT',
      body: {
        ...schedulingItem.value,
        next_maintenance: scheduleForm.value.next_maintenance,
        status: isToday ? 'maintenance' : schedulingItem.value.status
      }
    })
    
    toast.success(isToday ? 'Maintenance started' : 'Maintenance schedule created')
    showScheduleModal.value = false
    await fetchEquipment()
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to create schedule')
  } finally {
    scheduling.value = false
  }
}

async function markMaintenanceDone(eq: Equipment) {
  try {
    const today = new Date().toISOString().split('T')[0]
    
    // Update equipment status to operational
    await $api(`/equipment/${eq.id}`, { 
      method: 'PUT',
      body: {
        ...eq,
        status: 'operational',
        last_maintenance: today
      }
    })
    
    // Update any active schedules for this equipment - set last_maintenance
    try {
      const schedRes = await $api<{ data: Array<{ id: number, equipment_id: number, interval_days: number }> }>('/maintenance/schedules')
      const equipmentSchedules = (schedRes.data || []).filter(s => s.equipment_id === eq.id)
      
      for (const schedule of equipmentSchedules) {
        // Calculate next maintenance date based on interval
        const nextDate = new Date()
        nextDate.setDate(nextDate.getDate() + schedule.interval_days)
        
        await $api(`/maintenance/schedules/${schedule.id}`, {
          method: 'PUT',
          body: {
            last_maintenance: today,
            next_maintenance: nextDate.toISOString().split('T')[0]
          }
        })
      }
    } catch (schedError) {
      // Don't fail if schedule update fails
      console.error('Failed to update schedules:', schedError)
    }
    
    // Also complete any pending maintenance requests for this equipment
    try {
      const reqRes = await $api<{ data: Array<{ id: number, equipment_id: number, status: string }> }>('/maintenance/requests')
      const pendingRequests = (reqRes.data || []).filter(r => r.equipment_id === eq.id && r.status !== 'done')
      
      for (const req of pendingRequests) {
        await $api(`/maintenance/requests/${req.id}`, {
          method: 'PUT',
          body: { status: 'done' }
        })
      }
    } catch (reqError) {
      // Don't fail if request update fails
      console.error('Failed to update requests:', reqError)
    }
    
    // Create a maintenance log entry
    try {
      await $api('/maintenance-logs', {
        method: 'POST',
        body: {
          equipment_id: eq.id,
          type: 'preventive', // Defaulting to preventive for scheduled maintenance completion
          description: 'Maintenance marked as done',
          actions_taken: 'Standard maintenance procedure',
          cost: 0 // Optional, handled on backend if 0
        }
      })
    } catch (logError) {
      console.error('Failed to create maintenance log:', logError)
    }

    toast.success('Maintenance completed')
    await fetchEquipment()
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to update equipment')
  }
}

const deleting = ref(false)
const deletingItem = ref<Equipment | null>(null)
const showDeleteModal = ref(false)

function confirmDelete(eq: Equipment) {
  deletingItem.value = eq
  showDeleteModal.value = true
}

async function handleDelete() {
  if (!deletingItem.value) return
  deleting.value = true
  try {
    await $api(`/equipment/${deletingItem.value.id}`, { method: 'DELETE' })
    toast.success('Equipment deleted')
    showDeleteModal.value = false
    await fetchEquipment()
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to delete equipment')
  } finally {
    deleting.value = false
    deletingItem.value = null
  }
}



onMounted(fetchEquipment)
</script>
