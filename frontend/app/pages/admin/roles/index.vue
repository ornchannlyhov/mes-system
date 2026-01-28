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
          <tr v-if="roles.length === 0">
            <td colspan="4">
              <UiEmptyState 
                title="No roles found" 
                description="Create roles to manage user permissions."
                icon="heroicons:shield-check"
              />
            </td>
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
      <form @submit.prevent="save" class="space-y-6">
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
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider border-b pb-1 mt-4">{{ groupName }}</h4>
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

        <div class="flex justify-end gap-3 mt-6 sticky bottom-0 bg-white py-4 border-t">
          <button type="button" @click="showModal = false" class="btn-ghost">Cancel</button>
          <button type="submit" class="btn-primary" :disabled="saving">
            {{ saving ? 'Saving...' : (isEditing ? 'Save Changes' : 'Create Role') }}
          </button>
        </div>
      </form>
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

const { $api } = useApi()
const toast = useToast()

const roles = ref<Role[]>([])
const allPermissions = ref<Permission[]>([])
const showModal = ref(false)
const showDeleteModal = ref(false)
const saving = ref(false)
const deleting = ref(false)
const selectedRole = ref<Role | null>(null)
const roleToDelete = ref<Role | null>(null)

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

async function fetchData() {
  try {
    const [rolesRes, customPermissionsRes] = await Promise.all([
       $api<Role[]>('/roles'),
       $api<Permission[]>('/permissions'),
    ])
    roles.value = rolesRes || []
    allPermissions.value = customPermissionsRes || []
  } catch (e) {
    toast.error('Failed to fetch data')
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
        await fetchData()
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
    await fetchData()
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to save role')
  } finally {
    saving.value = false
  }
}

onMounted(fetchData)
</script>
