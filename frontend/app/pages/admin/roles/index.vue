```html
<template>
  <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="page-title">Roles & Permissions</h1>
          <p class="text-gray-500 mt-1 hidden sm:block">Manage roles and their access levels</p>
        </div>
        <button class="btn-primary" @click="createRole">
          <Icon name="heroicons:plus" class="w-5 h-5" />
          Create Role
        </button>
      </div>

    <div class="card p-0 overflow-hidden">
      <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Role Name</th>
            <th>Label</th>
            <th>Permissions</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="role in paginatedRoles" :key="role.id">
            <td class="font-medium text-gray-900 capitalize">{{ role.name }}</td>
            <td class="text-gray-500">{{ role.label }}</td>
            <td>
              <span class="text-xs text-gray-500">
                {{ role.permissions?.length || 0 }} permissions assigned
              </span>
            </td>
            <td>
              <div class="flex items-center gap-2">
                <UiIconButton
                  @click="editRole(role)"
                  icon="heroicons:pencil"
                  tooltip="Edit Role"
                />
                <UiIconButton
                  v-if="!['admin', 'manager', 'operator'].includes(role.name)"
                  @click="confirmDelete(role)"
                  icon="heroicons:trash"
                  tooltip="Delete Role"
                  color="text-red-400 hover:text-red-600"
                />
              </div>
            </td>
          </tr>
          <tr v-if="roles.length === 0 && !loading">
            <td colspan="4">
              <UiEmptyState 
                title="No roles found" 
                description="Create roles to manage user permissions."
                icon="heroicons:shield-check"
              />
            </td>
          </tr>
        </tbody>
        <tbody v-if="loading">
             <tr v-for="i in 5" :key="`skel-${i}`" class="animate-pulse">
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-24"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-20"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-16"></div></td>
                <td class="px-6 py-4"><div class="h-8 bg-gray-200 rounded w-24"></div></td>
             </tr>
        </tbody>
      </table>
      </div>
      <UiPagination
        v-if="roles.length > pageSize"
        v-model="currentPage"
        :total-items="roles.length"
        :page-size="pageSize"
      />
    </div>

    <!-- SlideOver -->
    <UiSlideOver v-model="showModal" :title="isEditing ? 'Edit Role Permissions' : 'Create New Role'">
      <form id="role-form" @submit.prevent="save" class="space-y-6">
        <div v-if="!isEditing" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Role Name (Key)</label>
                <input v-model="form.name" type="text" class="input mt-1" placeholder="e.g. supervisor" required />
                <p class="text-xs text-gray-500 mt-1">Unique identifier, lowercase.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Label</label>
                <input v-model="form.label" type="text" class="input mt-1" placeholder="e.g. Supervisor" required />
            </div>
        </div>
        <div v-else>
           <label class="block text-sm font-medium text-gray-700">Role</label>
           <p class="mt-1 text-sm text-gray-900">{{ selectedRole?.label }} ({{ selectedRole?.name }})</p>
        </div>

        <div class="space-y-4">
          <label class="block text-sm font-medium text-gray-700">Permissions</label>
          
          <div v-for="(group, groupName) in groupedPermissions" :key="groupName" class="space-y-2">
            <div class="flex items-center justify-between border-b pb-1 mt-4">
                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ groupName }}</h4>
                <label class="flex items-center gap-1 cursor-pointer">
                    <input 
                        type="checkbox" 
                        :checked="isGroupSelected(group)"
                        @change="(e) => toggleGroup(group, (e.target as HTMLInputElement).checked)"
                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 w-4 h-4"
                    />
                    <span class="text-xs text-gray-500">Select All</span>
                </label>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
              <label v-for="permission in group" :key="permission.id" class="flex items-start gap-2 p-2 rounded hover:bg-gray-50 cursor-pointer">
                <input 
                  type="checkbox" 
                  :value="permission.id" 
                  v-model="form.permissions" 
                  class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                />
                <div>
                  <div class="text-sm font-medium text-gray-700">{{ permission.label || permission.name }}</div>
                  <div class="text-xs text-gray-500 break-words">{{ permission.name }}</div>
                </div>
              </label>
            </div>
          </div>
        </div>
      </form>
      <template #footer>
        <div class="flex justify-end gap-3">
          <button type="button" @click="showModal = false" class="btn-ghost">Cancel</button>
          <button type="submit" form="role-form" class="btn-primary" :disabled="saving">
            {{ saving ? 'Saving...' : (isEditing ? 'Save Changes' : 'Create Role') }}
          </button>
        </div>
      </template>
    </UiSlideOver>

    <!-- Delete Confirmation -->
    <UiConfirmModal
        v-model="showDeleteModal"
        title="Delete Role"
        message="Are you sure you want to delete this role? This action cannot be undone."
        confirm-text="Delete"
        variant="danger"
        :loading="deleting"
        @confirm="handleDelete"
    />
  </div>
</template>

<script setup lang="ts">
import type { Role, Permission } from '~/types/models'
import { useAdminStore } from '~/stores/admin'

const { $api } = useApi()
const toast = useToast()
const adminStore = useAdminStore()

const roles = computed(() => adminStore.roles as Role[])
const allPermissions = computed(() => adminStore.permissions as Permission[])

const showModal = ref(false)
const showDeleteModal = ref(false)
const saving = ref(false)
const deleting = ref(false)
const selectedRole = ref<Role | null>(null)
const roleToDelete = ref<Role | null>(null)
const loading = ref(true)

// Pagination
const currentPage = ref(1)
const pageSize = 10

const paginatedRoles = computed(() => {
  const start = (currentPage.value - 1) * pageSize
  return roles.value.slice(start, start + pageSize)
})

const isEditing = computed(() => !!selectedRole.value)

const form = ref({ 
    name: '',
    label: '',
    permissions: [] as number[] 
})

async function fetchData(force = false) {
  loading.value = true
  try {
    await Promise.all([
       adminStore.fetchRoles(force),
       adminStore.fetchPermissions(force),
    ])
  } catch (e) {
    toast.error('Failed to fetch data')
  } finally {
    loading.value = false
  }
}

// Group permissions by prefix (e.g. "inventory:", "manufacturing:")
const groupedPermissions = computed(() => {
    const groups: Record<string, Permission[]> = {}
    allPermissions.value.forEach(p => {
        const parts = p.name.split(':')
        const prefix = parts[0]
        const group = (parts.length > 1 && prefix) ? prefix : 'General'
        if (!groups[group]) groups[group] = []
        groups[group].push(p)
    })
    return groups
})

function isGroupSelected(group: Permission[]) {
    return group.every(p => form.value.permissions.includes(p.id))
}

function toggleGroup(group: Permission[], selected: boolean) {
    const groupIds = group.map(p => p.id)
    if (selected) {
        // Add all distinct IDs
        form.value.permissions = [...new Set([...form.value.permissions, ...groupIds])]
    } else {
        // Remove all IDs in group
        form.value.permissions = form.value.permissions.filter(id => !groupIds.includes(id))
    }
}

function createRole() {
    selectedRole.value = null
    form.value = { name: '', label: '', permissions: [] }
    showModal.value = true
}

function editRole(role: Role) {
  selectedRole.value = role
  form.value = {
      name: role.name,
      label: role.label,
      permissions: (role.permissions || []).map(p => p.id) 
  }
  showModal.value = true
}

function confirmDelete(role: Role) {
    roleToDelete.value = role
    showDeleteModal.value = true
}

async function handleDelete() {
    if (!roleToDelete.value) return
    deleting.value = true
    try {
        await $api(`/roles/${roleToDelete.value.id}`, { method: 'DELETE' })
        toast.success('Role deleted')
        showDeleteModal.value = false
        await fetchData(true)
    } catch (e) {
        toast.error('Failed to delete role')
    } finally {
        deleting.value = false
        roleToDelete.value = null
    }
}

async function save() {
  saving.value = true
  try {
    if (isEditing.value && selectedRole.value) {
        await $api(`/roles/${selectedRole.value.id}`, { 
            method: 'PUT', 
            body: { permissions: form.value.permissions } 
        })
        toast.success('Role permissions updated')
    } else {
        await $api('/roles', { 
            method: 'POST', 
            body: form.value
        })
        toast.success('Role created')
    }
    showModal.value = false
    await fetchData(true)
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to save role')
  } finally {
    saving.value = false
  }
}

onMounted(fetchData)
</script>
