<template>
  <div class="space-y-6">
    <div class="page-header">
      <div>
        <h1 class="page-title">Maintenance Requests</h1>
        <p class="text-gray-500 mt-1">Track equipment maintenance work orders</p>
      </div>
      <button class="btn-primary" @click="openModal()">
        <Icon name="heroicons:plus" class="w-4 h-4" />
        <span class="hidden sm:inline">New Request</span>
        <span class="sm:hidden">New</span>
      </button>
    </div>

    <div class="card p-0 overflow-hidden">
      <div class="table-responsive">
        <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Reference</th>
            <th>Type</th>
            <th>Description</th>
            <th>Equipment</th>
            <th>Scheduled</th>
            <th>Priority</th>
            <th>Status</th>
            <th class="w-20">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="req in paginatedRequests" :key="req.id">
            <td class="font-mono text-sm">#{{ req.id }}</td>
            <td class="font-medium">{{ req.name }}</td>
            <td>
              <span :class="req.request_type === 'preventive' ? 'badge-blue' : 'badge-orange'">
                {{ req.request_type }}
              </span>
            </td>
            <td class="text-sm text-gray-600 truncate max-w-xs">{{ req.description || '-' }}</td>
            <td>{{ req.equipment?.name || 'N/A' }}</td>
            <td class="text-sm">{{ formatDate(req.scheduled_date) }}</td>
            <td><UiPriorityBadge :priority="req.priority" /></td>
            <td><UiStatusBadge :status="req.status" /></td>
            <td>
              <div class="flex items-center gap-2">
                <UiIconButton
                  v-if="req.status !== 'done'"
                  @click="complete(req)"
                  icon="heroicons:check"
                  tooltip="Complete Request"
                  color="text-green-600 hover:text-green-800"
                />
                <UiIconButton
                  @click="openModal(req)"
                  icon="heroicons:pencil"
                  tooltip="Edit Request"
                />
                <UiIconButton
                  @click="confirmDelete(req)"
                  icon="heroicons:trash"
                  tooltip="Delete Request"
                  color="text-red-400 hover:text-red-600"
                />
              </div>
            </td>
          </tr>
          <tr v-if="requests.length === 0">
            <td colspan="9">
              <UiEmptyState 
                title="No maintenance requests" 
                description="Create requests to track corrective and preventive maintenance."
                icon="heroicons:wrench-screwdriver"
              >
                <button class="btn-primary" @click="openModal()">
                  <Icon name="heroicons:plus" class="w-4 h-4" />
                  New Request
                </button>
              </UiEmptyState>
            </td>
          </tr>
        </tbody>
      </table>
      </div>
      <UiPagination v-model="currentPage" :total-items="requests.length" :page-size="pageSize" />
    </div>

    <!-- SlideOver -->
    <UiSlideOver v-model="showModal" title="New Maintenance Request">
      <form @submit.prevent="save" class="space-y-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
          <textarea v-model="form.description" rows="3" class="input" required></textarea>
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
          <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
          <select v-model="form.priority" class="input">
            <option value="low">Low</option>
            <option value="normal">Normal</option>
            <option value="high">High</option>
            <option value="critical">Critical</option>
          </select>
        </div>
        <div class="flex justify-end gap-3 mt-6">
          <button type="button" @click="showModal = false" class="btn-ghost">Cancel</button>
          <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Saving...' : 'Submit' }}</button>
        </div>
      </form>
    </UiSlideOver>
    <!-- Delete Confirmation Modal -->
    <UiConfirmModal
      v-model="showDeleteModal"
      title="Delete Request"
      :message="`Are you sure you want to delete this maintenance request? This action cannot be undone.`"
      confirm-text="Delete"
      variant="danger"
      :loading="deleting"
      @confirm="handleDelete"
    />
  </div>
</template>

<script setup lang="ts">
import type { Equipment, MaintenanceRequest } from '~/types/models'

const { $api } = useApi()
const toast = useToast()
const { formatDate } = useUtils()

const requests = ref<MaintenanceRequest[]>([])
const equipment = ref<Equipment[]>([])
const showModal = ref(false)
const showDeleteModal = ref(false)
const saving = ref(false)
const deleting = ref(false)
const currentPage = ref(1)
const pageSize = 10

const editingRequest = ref<MaintenanceRequest | null>(null)
const deletingRequest = ref<MaintenanceRequest | null>(null)

const form = ref({ description: '', equipment_id: null as number | null, priority: 'normal', request_type: 'corrective' })

async function fetchData() {
  try {
    const [reqRes, eqRes] = await Promise.all([
      $api<{ data: MaintenanceRequest[] }>('/maintenance/requests'),
      $api<{ data: Equipment[] }>('/equipment'),
    ])
    requests.value = reqRes.data || []
    equipment.value = eqRes.data || []
  } catch (e) {
    toast.error('Failed to fetch data')
  }
}

function openModal(req?: MaintenanceRequest) {
  if (req) {
    editingRequest.value = req
    form.value = {
        description: req.description || '',
        equipment_id: req.equipment_id,
        priority: req.priority,
        request_type: req.request_type
    }
  } else {
    editingRequest.value = null
    form.value = { description: '', equipment_id: null, priority: 'normal', request_type: 'corrective' }
  }
  showModal.value = true
}

async function save() {
  saving.value = true
  try {
    if (editingRequest.value) {
        await $api(`/maintenance/requests/${editingRequest.value.id}`, { method: 'PUT', body: form.value })
        toast.success('Request updated successfully')
    } else {
        await $api('/maintenance/requests', { method: 'POST', body: form.value })
        toast.success('Request submitted successfully')
    }
    showModal.value = false
    await fetchData()
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to submit request')
  } finally {
    saving.value = false
  }
}

async function complete(req: MaintenanceRequest) {
  try {
    await $api(`/maintenance/requests/${req.id}`, { method: 'PATCH', body: { status: 'done' } })
    toast.success('Request completed')
    await fetchData()
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to complete request')
  }
}

function confirmDelete(req: MaintenanceRequest) {
    deletingRequest.value = req
    showDeleteModal.value = true
}

async function handleDelete() {
    if (!deletingRequest.value) return
    deleting.value = true
    try {
        await $api(`/maintenance/requests/${deletingRequest.value.id}`, { method: 'DELETE' })
        toast.success('Request deleted')
        showDeleteModal.value = false
        await fetchData()
    } catch (e: any) {
        toast.error(e.data?.message || 'Failed to delete request')
    } finally {
        deleting.value = false
        deletingRequest.value = null
    }
}

const paginatedRequests = computed(() => {
  const start = (currentPage.value - 1) * pageSize
  return requests.value.slice(start, start + pageSize)
})

onMounted(fetchData)
</script>
