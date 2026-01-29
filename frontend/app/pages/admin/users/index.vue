<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="page-title">User Management</h1>
        <p class="text-gray-500 mt-1 hidden sm:block">Manage system users and their roles</p>
      </div>
      <button class="btn-primary" @click="createUser">
        <Icon name="heroicons:plus" class="w-5 h-5" />
        Create User
      </button>
    </div>

    <div class="card p-0 overflow-hidden">
      <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th class="w-16">Profile</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in paginatedUsers" :key="user.id">
            <td>
                <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center overflow-hidden border border-gray-200">
                    <img v-if="user.avatar_url" :src="getImageUrl(user.avatar_url)" class="w-full h-full object-cover" />
                    <span v-else class="text-xs font-semibold text-gray-500">{{ user.name.charAt(0).toUpperCase() }}</span>
                </div>
            </td>
            <td>
                <button @click="viewUser(user)" class="font-medium text-gray-900 hover:text-primary-600 hover:underline text-left">
                    {{ user.name }}
                </button>
            </td>
            <td class="text-gray-500">{{ user.email }}</td>
            <td>
              <span class="badge badge-primary">{{ user.role?.label || user.role?.name || 'No Role' }}</span>
            </td>
            <td>
              <span class="badge" :class="user.is_active ? 'badge-success' : 'badge-gray'">
                {{ user.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td>
              <div class="flex items-center gap-2">
                <UiIconButton
                  @click="viewUser(user)"
                  icon="heroicons:eye"
                  tooltip="View Details"
                  color="text-gray-500 hover:text-primary-600"
                />
                <UiIconButton
                  @click="editUser(user)"
                  icon="heroicons:pencil"
                  tooltip="Edit User"
                />
                <UiIconButton
                  @click="confirmDelete(user)"
                  icon="heroicons:trash"
                  tooltip="Delete User"
                  color="text-red-400 hover:text-red-600"
                />
              </div>
            </td>
          </tr>
             <tr v-if="loading" v-for="i in 5" :key="`skel-${i}`" class="animate-pulse">
                <td class="px-6 py-4"><div class="w-10 h-10 bg-gray-200 rounded-full"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-32"></div></td>
                <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-48"></div></td>
                <td class="px-6 py-4"><div class="h-5 bg-gray-200 rounded w-20"></div></td>
                <td class="px-6 py-4"><div class="h-5 bg-gray-200 rounded w-16"></div></td>
                <td class="px-6 py-4"><div class="h-8 bg-gray-200 rounded w-24"></div></td>
             </tr>
          <tr v-if="users.length === 0 && !loading">
            <td colspan="5">
              <UiEmptyState 
                title="No users found" 
                description="Invite users to collaborate on your organization."
                icon="heroicons:users"
              >
                <button class="btn-primary" @click="createUser">
                  <Icon name="heroicons:plus" class="w-4 h-4" />
                  Create User
                </button>
              </UiEmptyState>
            </td>
          </tr>
        </tbody>
      </table>
      </div>
      <UiPagination
        v-if="users.length > pageSize"
        v-model="currentPage"
        :total-items="users.length"
        :page-size="pageSize"
      />
    </div>

     <!-- SlideOver -->
    <UiSlideOver v-model="showModal" :title="isEditing ? 'Edit User' : 'Create New User'">
      <form @submit.prevent="save" class="space-y-6">
        <div class="flex flex-col items-center gap-4 mb-6">
            <div class="relative group">
                <div class="w-24 h-24 rounded-full flex items-center justify-center overflow-hidden border border-gray-200 bg-gray-50">
                    <img v-if="previewUrl || (isEditing && form.avatar_url)" :src="previewUrl || getImageUrl(form.avatar_url)" class="w-full h-full object-cover" />
                    <span v-else class="text-2xl font-semibold text-gray-500">{{ form.name ? form.name.charAt(0).toUpperCase() : '?' }}</span>
                </div>
                <label class="absolute bottom-0 right-0 p-1.5 bg-white rounded-full border shadow-sm cursor-pointer hover:bg-gray-50 text-gray-500 hover:text-primary-600 transition-colors">
                    <Icon name="heroicons:camera" class="w-4 h-4" />
                    <input type="file" class="hidden" accept="image/*" @change="handleFileChange" />
                </label>
            </div>
        </div>

        <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
              <input v-model="form.name" type="text" class="input" required />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
              <input v-model="form.email" type="email" class="input" required />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                  {{ isEditing ? 'Password (leave blank to keep current)' : 'Password' }}
              </label>
              <div class="relative">
                <input 
                  v-model="form.password" 
                  :type="showPassword ? 'text' : 'password'" 
                  class="input pr-10" 
                  :required="!isEditing"
                />
                <button 
                  type="button"
                  @click="showPassword = !showPassword"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none flex items-center justify-center cursor-pointer"
                >
                  <Icon :name="showPassword ? 'heroicons:eye-slash' : 'heroicons:eye'" class="w-5 h-5" />
                </button>
              </div>
            </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
          <select v-model="form.role_id" class="input">
            <option :value="null">Select a role...</option>
            <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.label }}</option>
          </select>
        </div>

        <div v-if="selectedRolePermissions.length > 0" class="bg-gray-50 p-3 rounded-lg border border-gray-100">
             <label class="block text-xs font-semibold text-gray-500 mb-2 uppercase tracking-wider">Role Permissions</label>
             <div class="flex flex-wrap gap-1.5">
                <span v-for="perm in selectedRolePermissions" :key="perm.id" class="px-2 py-0.5 rounded text-xs bg-white border border-gray-200 text-gray-600 shadow-sm">
                    {{ perm.label }}
                </span>
             </div>
        </div>

        <div class="flex justify-end gap-3 result mt-6">
          <button type="button" @click="showModal = false" class="btn-ghost">Cancel</button>
          <button type="submit" class="btn-primary" :disabled="saving">
            {{ saving ? 'Saving...' : (isEditing ? 'Save Changes' : 'Create User') }}
          </button>
        </div>
      </form>
    </UiSlideOver>
    
    <!-- Detail SlideOver -->
    <UiSlideOver v-model="showDetailModal" title="User Details" wide>
      <div v-if="selectedUser" class="flex flex-col sm:flex-row gap-8">
        <!-- Left Column: Avatar (40%) -->
        <div class="sm:w-2/5 flex flex-col gap-4">
           <div class="aspect-square w-full rounded-2xl bg-gray-50 border border-gray-200 overflow-hidden shadow-sm relative group">
              <img 
                v-if="selectedUser.avatar_url" 
                :src="getImageUrl(selectedUser.avatar_url)" 
                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" 
                alt="User Avatar"
              />
              <div v-else class="w-full h-full flex items-center justify-center bg-gray-100 text-gray-400">
                  <span class="text-6xl font-bold">{{ selectedUser.name.charAt(0).toUpperCase() }}</span>
              </div>
           </div>
           
           <!-- Status Badge Below Image -->
           <div class="flex justify-center">
             <span 
                class="px-4 py-1.5 rounded-full text-sm font-semibold shadow-sm border" 
                :class="selectedUser.is_active ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-100 text-gray-600 border-gray-200'"
             >
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full" :class="selectedUser.is_active ? 'bg-green-500' : 'bg-gray-400'"></span>
                    {{ selectedUser.is_active ? 'Active Account' : 'Inactive Account' }}
                </div>
             </span>
           </div>
        </div>

        <!-- Right Column: Details (60%) -->
        <div class="sm:w-3/5 space-y-6">
            <!-- Header Info -->
            <div>
                 <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ selectedUser.name }}</h2>
                 <p class="text-gray-500 text-lg mt-1 font-medium">{{ selectedUser.email }}</p>
                 
                 <div class="mt-4">
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-md bg-primary-50 text-primary-700 border border-primary-100 text-sm font-bold uppercase tracking-wide">
                        <Icon name="heroicons:shield-check" class="w-5 h-5" />
                        {{ selectedUser.role?.label || 'No Role Assigned' }}
                    </span>
                 </div>
            </div>

            <div class="border-t border-gray-100 my-4"></div>

            <!-- Permissions -->
            <div>
                <h4 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                    <Icon name="heroicons:key" class="w-4 h-4 text-gray-500" />
                    Role Permissions
                </h4>
                
                <div v-if="detailUserPermissions.length > 0" class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                     <div class="flex flex-wrap gap-2">
                        <span v-for="perm in detailUserPermissions" :key="perm.id" class="px-2.5 py-1 rounded-md text-xs font-medium bg-white border border-gray-200 text-gray-600 shadow-sm flex items-center gap-1">
                            <Icon name="heroicons:check-circle" class="w-3 h-3 text-green-500" />
                            {{ perm.label }}
                        </span>
                     </div>
                </div>
                 <p v-else class="text-sm text-gray-500 italic pl-1">No specific permissions assigned to this role.</p>
            </div>
            
            <!-- Meta Info Grid -->
            <div>
                <h4 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                    <Icon name="heroicons:information-circle" class="w-4 h-4 text-gray-500" />
                    System Information
                </h4>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Joined On</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900">{{ formatDate(selectedUser.created_at) }}</dd>
                    </div>
                </div>
            </div>
        </div>
      </div>
      
      <template #footer>
        <div class="flex justify-end">
            <button type="button" class="btn-primary w-full sm:w-auto shadow-md hover:shadow-lg transition-shadow" @click="switchToEditMode">
                <Icon name="heroicons:pencil-square" class="w-5 h-5 mr-2" />
                Edit Profile
            </button>
        </div>
      </template>
    </UiSlideOver>

    <!-- Delete Confirmation -->
    <UiConfirmModal
        v-model="showDeleteModal"
        title="Delete User"
        message="Are you sure you want to delete this user? This action cannot be undone."
        confirm-text="Delete"
        variant="danger"
        :loading="deleting"
        @confirm="handleDelete"
    />
  </div>
</template>

<script setup lang="ts">
import type { User, Role } from '~/types/models'

const { $api } = useApi()
const toast = useToast()
const { getImageUrl, formatDate } = useUtils()

const users = ref<User[]>([])
const roles = ref<Role[]>([])
const showModal = ref(false)
const showDetailModal = ref(false)
const showDeleteModal = ref(false)
const saving = ref(false)
const deleting = ref(false)
const selectedUser = ref<User | null>(null)
const userToDelete = ref<User | null>(null)
const loading = ref(true)

// Pagination
const currentPage = ref(1)
const pageSize = 10

const paginatedUsers = computed(() => {
  const start = (currentPage.value - 1) * pageSize
  return users.value.slice(start, start + pageSize)
})

const isEditing = computed(() => !!selectedUser.value)

const form = ref({
    name: '',
    email: '',
    password: '',
    role_id: null as number | null,
    avatar_url: '',
    avatar: null as File | null
})
const showPassword = ref(false)
const previewUrl = ref('')

const selectedRolePermissions = computed(() => {
    if (!form.value.role_id) return []
    const role = roles.value.find(r => r.id === form.value.role_id)
    return role?.permissions || []
})

const detailUserPermissions = computed(() => {
    if (!selectedUser.value || !selectedUser.value.role_id) return []
    const role = roles.value.find(r => r.id === selectedUser.value!.role_id)
    return role?.permissions || []
})

async function fetchData() {
  loading.value = true
  try {
    const [usersRes, rolesRes] = await Promise.all([
      $api<User[]>('/users'),
      $api<{ data: Role[] }>('/roles'),
    ])
    users.value = usersRes || []
    roles.value = rolesRes.data || []
  } catch (e) {
    toast.error('Failed to fetch data')
  } finally {
    loading.value = false
  }
}

function createUser() {
    selectedUser.value = null
    form.value = { name: '', email: '', password: '', role_id: null, avatar_url: '', avatar: null }
    previewUrl.value = ''
    showModal.value = true
}

function viewUser(user: User) {
    selectedUser.value = user
    showDetailModal.value = true
}

function editUser(user: User) {
  selectedUser.value = user
  form.value = {
      name: user.name,
      email: user.email,
      password: '', // Don't show password
      role_id: user.role_id || (user.role?.id) || null,
      avatar_url: user.avatar_url || '',
      avatar: null
  }
  previewUrl.value = ''
  showModal.value = true
}

function switchToEditMode() {
    if (!selectedUser.value) return
    showDetailModal.value = false
    editUser(selectedUser.value)
}

function handleFileChange(event: Event) {
    const target = event.target as HTMLInputElement
    if (target.files && target.files[0]) {
        const file = target.files[0]
        form.value.avatar = file
        previewUrl.value = URL.createObjectURL(file)
    }
}

function confirmDelete(user: User) {
    userToDelete.value = user
    showDeleteModal.value = true
}

async function handleDelete() {
    if (!userToDelete.value) return
    deleting.value = true
    try {
        await $api(`/users/${userToDelete.value.id}`, { method: 'DELETE' })
        toast.success('User deleted')
        showDeleteModal.value = false
        await fetchData()
    } catch (e: any) {
        toast.error(e.data?.message || 'Failed to delete user')
    } finally {
        deleting.value = false
        userToDelete.value = null
    }
}

async function save() {
  saving.value = true
  
  const formData = new FormData()
  formData.append('name', form.value.name)
  formData.append('email', form.value.email)
  if (form.value.role_id) formData.append('role_id', String(form.value.role_id))
  if (form.value.password) formData.append('password', form.value.password)
  if (form.value.avatar) formData.append('avatar', form.value.avatar)
  
  try {
    if (isEditing.value && selectedUser.value) {
        // Edit Mode
        formData.append('_method', 'PUT') // For Laravel to handle PUT with files
        await $api(`/users/${selectedUser.value.id}`, { 
            method: 'POST', 
            body: formData
        })
        toast.success('User updated')
    } else {
        // Create Mode
        await $api('/users', { 
            method: 'POST', 
            body: formData
        })
        toast.success('User created')
    }
    showModal.value = false
    await fetchData()
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to save user')
  } finally {
    saving.value = false
  }}

onMounted(fetchData)
</script>
