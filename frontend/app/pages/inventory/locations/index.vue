<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Locations</h1>
        <p class="text-gray-500 mt-1 hidden sm:block">Manage warehouses, production areas, and storage locations</p>
      </div>
      <button class="btn-primary" @click="openModal()">
        <Icon name="heroicons:plus" class="w-4 h-4" />
        <span class="hidden sm:inline">Add Location</span>
        <span class="sm:hidden">Add</span>
      </button>
    </div>

    <!-- Table -->
    <div class="card p-0 overflow-hidden">
      <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Code</th>
            <th>Name</th>
            <th>Type</th>
            <th class="w-20">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="loc in paginatedLocations" :key="loc.id">
            <td class="font-mono text-sm">{{ loc.code }}</td>
            <td class="font-medium">{{ loc.name }}</td>
            <td>
              <span :class="typeClass(loc.type)">{{ loc.type }}</span>
            </td>
            <td>
              <div class="flex gap-2">
                <UiIconButton
                  @click="openModal(loc)"
                  icon="heroicons:pencil"
                  tooltip="Edit Location"
                />
                <UiIconButton
                  @click="confirmDelete(loc)"
                  icon="heroicons:trash"
                  tooltip="Delete Location"
                  color="text-red-400 hover:text-red-600"
                />
              </div>
            </td>
          </tr>
          <tr v-if="locations.length === 0">
            <td colspan="4">
              <UiEmptyState 
                title="No locations found" 
                description="Create locations to track inventory and production areas."
                icon="heroicons:map-pin"
              >
                <button class="btn-primary" @click="openModal()">
                  <Icon name="heroicons:plus" class="w-4 h-4" />
                  Add Location
                </button>
              </UiEmptyState>
            </td>
          </tr>
        </tbody>
      </table>
      </div>
      
      <!-- Pagination -->
      <UiPagination
        v-model="currentPage"
        :total-items="locations.length"
        :page-size="pageSize"
      />
    </div>

    <!-- SlideOver -->
    <UiSlideOver v-model="showModal" :title="editing ? 'Edit Location' : 'Add Location'">
      <form @submit.prevent="save" class="space-y-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Code</label>
          <input v-model="form.code" type="text" class="input" required />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
          <input v-model="form.name" type="text" class="input" required />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
          <select v-model="form.type" class="input" required>
            <option value="warehouse">Warehouse</option>
            <option value="production">Production</option>
            <option value="scrap">Scrap</option>
          </select>
        </div>
        <div class="flex justify-end gap-3 mt-6">
          <button type="button" @click="showModal = false" class="btn-ghost">Cancel</button>
          <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Saving...' : 'Save' }}</button>
        </div>
      </form>
    </UiSlideOver>

    <!-- Delete Confirmation Modal -->
    <UiConfirmModal
      v-model="showDeleteModal"
      title="Delete Location"
      :message="`Are you sure you want to delete '${deletingItem?.name}'? This action cannot be undone.`"
      confirm-text="Delete"
      variant="danger"
      :loading="deleting"
      @confirm="deleteLocation"
    />
  </div>
</template>

<script setup lang="ts">
import type { Location } from '~/types/models'

const { $api } = useApi()
const toast = useToast()

const locations = ref<Location[]>([])
const currentPage = ref(1)
const pageSize = 10
const showModal = ref(false)
const editing = ref<Location | null>(null)
const saving = ref(false)

// Delete state
const showDeleteModal = ref(false)
const deletingItem = ref<Location | null>(null)
const deleting = ref(false)

const form = ref({
  code: '',
  name: '',
  type: 'warehouse' as 'warehouse' | 'production' | 'scrap',
})

function typeClass(type: string) {
  return {
    warehouse: 'badge bg-blue-100 text-blue-800',
    production: 'badge bg-green-100 text-green-800',
    scrap: 'badge bg-red-100 text-red-800',
  }[type] || 'badge-gray'
}

async function fetchLocations() {
  try {
    const response = await $api<{ data: Location[] }>('/locations')
    locations.value = response.data || []
  } catch (e) {
    toast.error('Failed to fetch locations')
  }
}

function openModal(loc?: Location) {
  if (loc) {
    editing.value = loc
    form.value = { ...loc }
  } else {
    editing.value = null
    form.value = { code: '', name: '', type: 'warehouse' }
  }
  showModal.value = true
}

async function save() {
  saving.value = true
  try {
    if (editing.value) {
      await $api(`/locations/${editing.value.id}`, { method: 'PUT', body: form.value })
      toast.success('Location updated successfully')
    } else {
      await $api('/locations', { method: 'POST', body: form.value })
      toast.success('Location created successfully')
    }
    showModal.value = false
    await fetchLocations()
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to save location')
  } finally {
    saving.value = false
  }
}

function confirmDelete(loc: Location) {
  deletingItem.value = loc
  showDeleteModal.value = true
}

async function deleteLocation() {
  if (!deletingItem.value) return
  deleting.value = true
  try {
    await $api(`/locations/${deletingItem.value.id}`, { method: 'DELETE' })
    toast.success('Location deleted successfully')
    showDeleteModal.value = false
    await fetchLocations()
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to delete location')
  } finally {
    deleting.value = false
  }
}

// Pagination computed properties
const paginatedLocations = computed(() => {
  const start = (currentPage.value - 1) * pageSize
  return locations.value.slice(start, start + pageSize)
})

onMounted(fetchLocations)
</script>
