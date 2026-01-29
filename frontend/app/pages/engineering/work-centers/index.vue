<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Work Centers</h1>
        <p class="text-gray-500 mt-1">Manage production work centers and capacity</p>
      </div>
      <button class="btn-primary" @click="openModal()">
        <Icon name="heroicons:plus" class="w-4 h-4" />
        Add Work Center
      </button>
    </div>

    <!-- Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
      <div v-if="loading" v-for="i in 8" :key="`skel-${i}`" class="card p-4 animate-pulse">
        <div class="flex items-center justify-between mb-3">
          <div class="flex items-center gap-2">
            <div class="w-9 h-9 bg-gray-200 rounded-lg"></div>
            <div>
              <div class="h-4 bg-gray-200 rounded w-24 mb-1"></div>
              <div class="h-3 bg-gray-200 rounded w-16"></div>
            </div>
          </div>
        </div>
        <div class="flex justify-between mt-4">
             <div class="h-3 bg-gray-200 rounded w-20"></div>
             <div class="h-3 bg-gray-200 rounded w-16"></div>
        </div>
      </div>

      <div v-for="wc in paginatedWorkCenters" :key="wc.id" class="card p-4 hover:shadow-soft transition-shadow">
        <div class="flex items-center justify-between mb-3">
          <div class="flex items-center gap-2">
            <div class="w-9 h-9 bg-primary-100 rounded-lg flex items-center justify-center">
              <Icon name="heroicons:cog-6-tooth" class="w-5 h-5 text-primary-600" />
            </div>
            <div>
              <h3 class="font-semibold text-gray-800 text-sm">{{ wc.name }}</h3>
              <p class="text-xs text-gray-500 font-mono">{{ wc.code }}</p>
            </div>
          </div>
          <div class="flex gap-0.5">
            <UiIconButton
              @click="openModal(wc)"
              icon="heroicons:pencil"
              tooltip="Edit Work Center"
              class="hover:bg-gray-100"
            />
            <UiIconButton
              @click="confirmDelete(wc)"
              icon="heroicons:trash"
              tooltip="Delete Work Center"
              color="text-red-400 hover:text-red-600"
              class="hover:bg-gray-100"
            />
          </div>
        </div>
        
        <div class="flex items-center justify-between text-xs text-gray-500 mb-2">
          <div class="flex items-center gap-1">
            <Icon name="heroicons:map-pin" class="w-3 h-3" />
            <span>{{ wc.location || 'No location' }}</span>
          </div>
          <div class="flex items-center gap-1">
            <span :class="statusDotClass(wc.status)" class="w-1.5 h-1.5 rounded-full"></span>
            <span class="capitalize">{{ wc.status || 'active' }}</span>
          </div>
        </div>
        
        <div class="flex justify-between text-xs pt-2 border-t border-gray-100">
          <div>
            <span class="text-gray-400">Eff:</span>
            <span class="font-medium text-gray-700 ml-1">{{ wc.efficiency_percent || 100 }}%</span>
          </div>
          <div>
            <span class="text-gray-400">Cost:</span>
            <span class="font-medium text-gray-700 ml-1">${{ Number(wc.cost_per_hour || 0).toFixed(0) }}/hr</span>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-if="workCenters.length === 0 && !loading" class="col-span-full">
        <UiEmptyState 
          title="No work centers found" 
          description="Define work centers to track capacity and cost."
          icon="heroicons:building-office"
        >
          <button class="btn-primary" @click="openModal()">
            <Icon name="heroicons:plus" class="w-4 h-4" />
            Add Work Center
          </button>
        </UiEmptyState>
      </div>
    </div>

    <!-- Pagination -->
    <UiPagination
      v-if="totalPages > 1"
      v-model="currentPage"
      :total-items="workCenters.length"
      :page-size="pageSize"
    />

    <!-- SlideOver -->
    <UiSlideOver v-model="showModal" :title="editing ? 'Edit Work Center' : 'Add Work Center'">
      <form @submit.prevent="save" class="space-y-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Code *</label>
          <input v-model="form.code" type="text" class="input" placeholder="WC-001" required />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
          <input v-model="form.name" type="text" class="input" placeholder="Assembly Line" required />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
          <input v-model="form.location" type="text" class="input" placeholder="e.g. Production Floor A" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
          <select v-model="form.status" class="input">
            <option value="active">Active</option>
            <option value="maintenance">Maintenance</option>
            <option value="down">Down</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Efficiency (%)</label>
          <input v-model.number="form.efficiency_percent" type="number" min="0" max="200" class="input" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Cost per Hour ($) *</label>
          <input v-model.number="form.cost_per_hour" type="number" step="0.01" min="0" class="input" required />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Overhead Rate ($/hr) *</label>
          <input v-model.number="form.overhead_per_hour" type="number" step="0.01" min="0" class="input" required />
        </div>
        
        <div class="flex justify-end gap-3 result mt-6">
          <button type="button" @click="showModal = false" class="btn-ghost">Cancel</button>
          <button type="submit" class="btn-primary" :disabled="saving">
            {{ saving ? 'Saving...' : (editing ? 'Update' : 'Create') }}
          </button>
        </div>
      </form>
    </UiSlideOver>

    <!-- Delete Confirmation Modal -->
    <UiConfirmModal
      v-model="showDeleteModal"
      title="Delete Work Center"
      :message="`Are you sure you want to delete '${deletingItem?.name}'? This action cannot be undone.`"
      confirm-text="Delete"
      variant="danger"
      :loading="deleting"
      @confirm="deleteWorkCenter"
    />
  </div>
</template>

<script setup lang="ts">
import type { WorkCenter } from '~/types/models'

const { $api } = useApi()
const toast = useToast()

const workCenters = ref<WorkCenter[]>([])
const showModal = ref(false)
const editing = ref<WorkCenter | null>(null)
const saving = ref(false)
const loading = ref(true)

// Pagination
const currentPage = ref(1)
const pageSize = 10

const totalPages = computed(() => Math.ceil(workCenters.value.length / pageSize))
const paginatedWorkCenters = computed(() => {
  const start = (currentPage.value - 1) * pageSize
  return workCenters.value.slice(start, start + pageSize)
})

// Delete state
const showDeleteModal = ref(false)
const deletingItem = ref<WorkCenter | null>(null)
const deleting = ref(false)

const form = ref({
  code: '',
  name: '',
  location: '',
  status: 'active' as 'active' | 'maintenance' | 'down',
  efficiency_percent: 100,
  cost_per_hour: 0,
  overhead_per_hour: 0,
})

function statusDotClass(status: string) {
  const classes: Record<string, string> = {
    active: 'bg-green-500',
    maintenance: 'bg-amber-500',
    down: 'bg-red-500',
  }
  return classes[status] || 'bg-green-500'
}

async function fetchData() {
  loading.value = true
  try {
    const res = await $api<{ data: WorkCenter[] }>('/work-centers')
    workCenters.value = res.data || []
  } catch (e) {
    toast.error('Failed to fetch data')
  } finally {
    loading.value = false
  }
}

function openModal(wc?: WorkCenter) {
  if (wc) {
    editing.value = wc
    form.value = { 
      code: wc.code, 
      name: wc.name, 
      location: wc.location || '',
      status: wc.status || 'active',
      efficiency_percent: wc.efficiency_percent || 100,
      cost_per_hour: wc.cost_per_hour || 0,
      overhead_per_hour: wc.overhead_per_hour || 0,
    }
  } else {
    editing.value = null
    form.value = { code: '', name: '', location: '', status: 'active', efficiency_percent: 100, cost_per_hour: 0, overhead_per_hour: 0 }
  }
  showModal.value = true
}

async function save() {
  saving.value = true
  try {
    if (editing.value) {
      await $api(`/work-centers/${editing.value.id}`, { method: 'PUT', body: form.value })
      toast.success('Work center updated')
    } else {
      await $api('/work-centers', { method: 'POST', body: form.value })
      toast.success('Work center created')
    }
    showModal.value = false
    await fetchData()
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to save')
  } finally {
    saving.value = false
  }
}

function confirmDelete(wc: WorkCenter) {
  deletingItem.value = wc
  showDeleteModal.value = true
}

async function deleteWorkCenter() {
  if (!deletingItem.value) return
  deleting.value = true
  try {
    await $api(`/work-centers/${deletingItem.value.id}`, { method: 'DELETE' })
    toast.success('Work center deleted')
    showDeleteModal.value = false
    await fetchData()
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to delete')
  } finally {
    deleting.value = false
  }
}

onMounted(fetchData)
</script>
