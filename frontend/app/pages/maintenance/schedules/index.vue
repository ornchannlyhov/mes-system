<template>
  <div class="space-y-6">
    <div class="page-header">
      <div>
        <h1 class="page-title">Maintenance Schedules</h1>
        <p class="text-gray-500 mt-1 hidden sm:block">Preventive maintenance planning</p>
      </div>
      <button class="btn-primary" @click="openModal()">
        <Icon name="heroicons:plus" class="w-4 h-4" />
        <span class="hidden sm:inline">Add Schedule</span>
        <span class="sm:hidden">Add</span>
      </button>
    </div>

    <div class="card p-0 overflow-hidden">
      <div class="table-responsive">
        <table class="table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Equipment</th>
            <th>Interval</th>
            <th>Last Maintenance</th>
            <th>Next Maintenance</th>
            <th>Status</th>
            <th class="w-24">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="schedule in paginatedSchedules" :key="schedule.id">
            <td class="font-medium">{{ schedule.name }}</td>
            <td>{{ schedule.equipment?.name || 'N/A' }}</td>
            <td>{{ schedule.interval_days }} days</td>
            <td>{{ formatDate(schedule.last_maintenance) }}</td>
            <td :class="isOverdue(schedule.next_maintenance) ? 'text-red-600 font-medium' : ''">
              {{ formatDate(schedule.next_maintenance) }}
            </td>
            <td>
              <span :class="schedule.is_active ? 'badge-success' : 'badge-gray'">
                {{ schedule.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="flex gap-1">
              <button 
                v-if="isDue(schedule.next_maintenance)"
                @click="completeSchedule(schedule)" 
                class="btn-primary text-xs py-1 px-2"
                :disabled="completing === schedule.id"
              >
                {{ completing === schedule.id ? '...' : 'Complete' }}
              </button>
              <UiIconButton
                @click="openModal(schedule)" 
                icon="heroicons:pencil"
                tooltip="Edit Schedule"
              />
              <UiIconButton
                @click="confirmDelete(schedule)" 
                icon="heroicons:trash"
                tooltip="Delete Schedule"
                color="text-red-400 hover:text-red-600"
              />
            </td>
          </tr>
          <tr v-if="schedules.length === 0">
            <td colspan="7">
              <UiEmptyState 
                title="No schedules found" 
                description="Set up preventive maintenance schedules for your equipment."
                icon="heroicons:calendar-days"
              >
                <button class="btn-primary" @click="openModal()">
                  <Icon name="heroicons:plus" class="w-4 h-4" />
                  Add Schedule
                </button>
              </UiEmptyState>
            </td>
          </tr>
        </tbody>
      </table>
      </div>
      <UiPagination v-model="currentPage" :total-items="schedules.length" :page-size="pageSize" />
    </div>

    <!-- SlideOver -->
    <UiSlideOver v-model="showModal" :title="editing ? 'Edit Schedule' : 'Add Schedule'">
      <form @submit.prevent="save" class="space-y-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
          <input v-model="form.name" type="text" class="input" required />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Equipment</label>
          <UiSearchableSelect
            v-model="form.equipment_id"
            :options="equipment.map(e => ({ label: e.name, value: e.id }))"
            placeholder="Select equipment..."
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Interval (days)</label>
          <input v-model.number="form.interval_days" type="number" min="1" class="input" required />
        </div>
        <div>
          <label class="flex items-center gap-2">
            <input v-model="form.is_active" type="checkbox" class="rounded text-primary-600" />
            <span class="text-sm">Active</span>
          </label>
        </div>
        <div class="flex justify-end gap-3 mt-6">
          <button type="button" @click="showModal = false" class="btn-ghost">Cancel</button>
          <button type="submit" class="btn-primary">Save</button>
        </div>
      </form>
    </UiSlideOver>

    <!-- Delete Confirmation Modal -->
    <UiConfirmModal
      v-model="showDeleteModal"
      title="Delete Schedule"
      :message="`Are you sure you want to delete the schedule '${deletingItem?.name}'? This action cannot be undone.`"
      confirm-text="Delete"
      variant="danger"
      :loading="deleting"
      @confirm="deleteSchedule"
    />
  </div>
</template>

<script setup lang="ts">
import type { Equipment, MaintenanceSchedule as Schedule } from '~/types/models'

const { $api } = useApi()
const toast = useToast()
const { formatDate } = useUtils()

const schedules = ref<Schedule[]>([])
const equipment = ref<Equipment[]>([])
const showModal = ref(false)
const editing = ref<Schedule | null>(null)
const saving = ref(false)
const completing = ref<number | null>(null)
const deleting = ref(false)
const showDeleteModal = ref(false)
const deletingItem = ref<Schedule | null>(null)
const currentPage = ref(1)
const pageSize = 10
const form = ref({ name: '', equipment_id: null as number | null, interval_days: 30, is_active: true })

function isOverdue(date?: string) {
  if (!date) return false
  return new Date(date) < new Date()
}

function isDue(date?: string) {
  if (!date) return true  // No date means it's pending/due
  const scheduleDate = new Date(date)
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  return scheduleDate <= today
}

async function fetchData() {
  try {
    const [schedRes, eqRes] = await Promise.all([
      $api<{ data: Schedule[] }>('/maintenance/schedules'),
      $api<{ data: Equipment[] }>('/equipment'),
    ])
    schedules.value = schedRes.data || []
    equipment.value = eqRes.data || []
  } catch (e) {
    toast.error('Failed to fetch data')
  }
}

function openModal(schedule?: Schedule) {
  if (schedule) {
    editing.value = schedule
    form.value = { name: schedule.name, equipment_id: schedule.equipment_id, interval_days: schedule.interval_days, is_active: schedule.is_active }
  } else {
    editing.value = null
    form.value = { name: '', equipment_id: null, interval_days: 30, is_active: true }
  }
  showModal.value = true
}

async function save() {
  saving.value = true
  try {
    if (editing.value) {
      await $api(`/maintenance/schedules/${editing.value.id}`, { method: 'PUT', body: form.value })
      toast.success('Schedule updated successfully')
    } else {
      await $api('/maintenance/schedules', { method: 'POST', body: form.value })
      toast.success('Schedule created successfully')
    }
    showModal.value = false
    await fetchData()
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to save schedule')
  } finally {
    saving.value = false
  }
}

async function completeSchedule(schedule: Schedule) {
  completing.value = schedule.id
  try {
    await $api(`/maintenance/schedules/${schedule.id}/complete`, { method: 'POST' })
    toast.success('Maintenance completed - next maintenance scheduled')
    await fetchData()
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to complete maintenance')
  } finally {
    completing.value = null
  }
}

function confirmDelete(schedule: Schedule) {
  deletingItem.value = schedule
  showDeleteModal.value = true
}

async function deleteSchedule() {
  if (!deletingItem.value) return
  
  deleting.value = true
  try {
    await $api(`/maintenance/schedules/${deletingItem.value.id}`, { method: 'DELETE' })
    toast.success('Schedule deleted')
    showDeleteModal.value = false
    deletingItem.value = null
    await fetchData()
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to delete schedule')
  } finally {
    deleting.value = false
  }
}

const paginatedSchedules = computed(() => {
  const start = (currentPage.value - 1) * pageSize
  return schedules.value.slice(start, start + pageSize)
})

onMounted(fetchData)
</script>
