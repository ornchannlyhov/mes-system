<template>
  <div class="space-y-6 animate-fade-in">
    <!-- Header -->
    <div>
      <h1 class="page-title">Profile</h1>
      <p class="text-gray-500 mt-1 hidden sm:block">Manage your account settings</p>
    </div>

    <!-- Profile Card -->
    <div class="card">
      <div class="flex flex-col sm:flex-row items-center gap-4 sm:gap-6 mb-6 text-center sm:text-left">
        <div class="relative group">
            <div class="w-20 h-20 rounded-full flex items-center justify-center overflow-hidden border border-gray-200 bg-gray-50">
               <img v-if="user?.avatar_url" :src="getImageUrl(user.avatar_url)" class="w-full h-full object-cover" />
               <Icon v-else name="heroicons:user" class="w-10 h-10 text-gray-400" />
            </div>
            <label class="absolute bottom-0 right-0 p-1 bg-white rounded-full border shadow-sm cursor-pointer hover:bg-gray-50 text-gray-500 hover:text-primary-600 transition-colors">
                <Icon name="heroicons:camera" class="w-4 h-4" />
                <input type="file" class="hidden" accept="image/*" @change="handleAvatarUpload" />
            </label>
        </div>
        
        <div>
          <h2 class="text-xl font-semibold text-gray-800">{{ user?.name || 'User' }}</h2>
          <p class="text-gray-500">{{ user?.email }}</p>
          <p class="text-sm text-gray-400 mt-1">
            Member since {{ formatDate(user?.created_at || '', 'N/A') }}
          </p>
        </div>
      </div>

      <div class="border-t border-gray-200 pt-6">
        <h3 class="text-lg font-medium text-gray-800 mb-4">Account Information</h3>
        
        <form @submit.prevent="updateProfile" class="space-y-4 max-w-md">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
            <input 
              v-model="form.name" 
              type="text" 
              class="input" 
              placeholder="Your name"
              required 
            />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input 
              v-model="form.email" 
              type="email" 
              class="input" 
              placeholder="your@email.com"
              required 
            />
          </div>

          <div class="pt-4">
            <button type="submit" class="btn-primary" :disabled="saving">
              <Icon v-if="saving" name="heroicons:arrow-path" class="w-4 h-4 animate-spin" />
              <Icon v-else name="heroicons:check" class="w-4 h-4" />
              {{ saving ? 'Saving...' : 'Save Changes' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Role & Permissions Card -->
    <div class="card">
      <h3 class="text-lg font-medium text-gray-800 mb-4">Role & Permissions</h3>
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-500 mb-1">Assigned Role</label>
          <div class="flex items-center gap-2">
             <span class="badge badge-primary text-base px-3 py-1">
                {{ user?.role?.label || 'No Role Assigned' }}
             </span>
             <span v-if="user?.role?.name" class="text-xs text-gray-400 font-mono">({{ user.role.name }})</span>
          </div>
        </div>

        <div v-if="user?.role?.permissions?.length">
          <label class="block text-sm font-medium text-gray-500 mb-2">Permissions</label>
          <div class="bg-gray-50 rounded-lg p-4 max-h-60 overflow-y-auto border border-gray-100">
             <div class="flex flex-wrap gap-2">
                <span 
                  v-for="perm in user.role.permissions" 
                  :key="perm.id"
                  class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-white border border-gray-200 text-gray-700 shadow-sm"
                  :title="perm.name"
                >
                  {{ perm.label }}
                </span>
             </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Change Password Card -->
    <div class="card">
      <h3 class="text-lg font-medium text-gray-800 mb-4">Change Password</h3>
      
      <form @submit.prevent="changePassword" class="space-y-4 max-w-md">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
          <div class="relative">
            <input 
                v-model="passwordForm.current_password" 
                :type="showPassword.current ? 'text' : 'password'" 
                class="input pr-10" 
                required 
            />
            <button 
                type="button" 
                @click="showPassword.current = !showPassword.current" 
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none"
            >
                <Icon :name="showPassword.current ? 'heroicons:eye-slash' : 'heroicons:eye'" class="w-5 h-5" />
            </button>
          </div>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
           <div class="relative">
            <input 
                v-model="passwordForm.new_password" 
                :type="showPassword.new ? 'text' : 'password'" 
                class="input pr-10" 
                required 
            />
            <button 
                type="button" 
                @click="showPassword.new = !showPassword.new" 
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none"
            >
                <Icon :name="showPassword.new ? 'heroicons:eye-slash' : 'heroicons:eye'" class="w-5 h-5" />
            </button>
           </div>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
          <div class="relative">
            <input 
                v-model="passwordForm.new_password_confirmation" 
                :type="showPassword.confirm ? 'text' : 'password'" 
                class="input pr-10" 
                required 
            />
             <button 
                type="button" 
                @click="showPassword.confirm = !showPassword.confirm" 
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none"
            >
                <Icon :name="showPassword.confirm ? 'heroicons:eye-slash' : 'heroicons:eye'" class="w-5 h-5" />
            </button>
          </div>
        </div>

        <div class="pt-4">
          <button type="submit" class="btn-primary" :disabled="changingPassword">
            <Icon v-if="changingPassword" name="heroicons:arrow-path" class="w-4 h-4 animate-spin" />
            <Icon v-else name="heroicons:key" class="w-4 h-4" />
            {{ changingPassword ? 'Updating...' : 'Update Password' }}
          </button>
        </div>
      </form>
    </div>

    <!-- Danger Zone -->
    <div class="card border-red-200 bg-red-50">
      <h3 class="text-lg font-medium text-red-800 mb-2">Danger Zone</h3>
      <p class="text-sm text-red-600 mb-4">
        Once you log out, you will need to sign in again to access your account.
      </p>
      <button @click="showLogoutModal = true" class="btn-ghost text-red-600 hover:bg-red-100">
        <Icon name="heroicons:arrow-right-on-rectangle" class="w-4 h-4" />
        Logout
      </button>
    </div>

    <!-- Logout Confirmation Modal -->
    <UiConfirmModal
      v-model="showLogoutModal"
      title="Confirm Logout"
      message="Are you sure you want to log out? You will need to sign in again to access your account."
      confirm-text="Logout"
      variant="danger"
      :loading="loggingOut"
      @confirm="handleLogout"
    />
  </div>
</template>

<script setup lang="ts">
import type { User } from '~/types/models'

const { user, logout } = useAuth()
const { $api } = useApi()
const router = useRouter()
const toast = useToast()
const { formatDate, getImageUrl } = useUtils()

// Profile form
const form = ref({
  name: user.value?.name || '',
  email: user.value?.email || '',
})

async function handleAvatarUpload(event: Event) {
    const target = event.target as HTMLInputElement
    if (!target.files || target.files.length === 0) return
    
    const file = target.files[0] as File
    const formData = new FormData()
    formData.append('avatar', file)
    
    try {
        formData.append('_method', 'PUT')
        
        const res = await $api<{ user: User }>('/auth/profile', {
            method: 'POST', 
            body: formData,
        })
        
        if (res.user) {
             // Force refresh to update avatar
             location.reload()
        }
        
        toast.success('Avatar updated!')
    } catch (e: any) {
        toast.error('Failed to upload avatar')
    }
}

// Password form
const passwordForm = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: '',
})

const showPassword = ref({
    current: false,
    new: false,
    confirm: false
})

const saving = ref(false)
const changingPassword = ref(false)
const showLogoutModal = ref(false)
const loggingOut = ref(false)

// Watch for user changes to update form
watch(user, (newUser) => {
  if (newUser) {
    form.value.name = newUser.name
    form.value.email = newUser.email
  }
}, { immediate: true })



async function updateProfile() {
  saving.value = true
  try {
    await $api('/auth/profile', {
      method: 'PUT',
      body: form.value,
    })
    toast.success('Profile updated successfully!')
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to update profile')
  } finally {
    saving.value = false
  }
}

async function changePassword() {
  if (passwordForm.value.new_password !== passwordForm.value.new_password_confirmation) {
    toast.error('Passwords do not match')
    return
  }
  
  changingPassword.value = true
  try {
    await $api('/auth/password', {
      method: 'PUT',
      body: passwordForm.value,
    })
    toast.success('Password changed successfully!')
    passwordForm.value = { current_password: '', new_password: '', new_password_confirmation: '' }
  } catch (e: any) {
    toast.error(e.data?.message || 'Failed to change password')
  } finally {
    changingPassword.value = false
  }
}

async function handleLogout() {
  loggingOut.value = true
  try {
    await logout()
    showLogoutModal.value = false
    router.push('/login')
  } catch (e) {
    toast.error('Failed to logout')
  } finally {
    loggingOut.value = false
  }
}
</script>
