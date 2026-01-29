<template>
  <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6">
    <!-- Breadcrumb / Title -->
    <div class="flex items-center gap-4">
      <h1 class="text-lg font-semibold text-gray-800">{{ pageTitle }}</h1>
    </div>

    <!-- Right side -->
    <div class="flex items-center gap-4">
      <!-- Notifications -->
      <button class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
        <Icon name="heroicons:bell" class="w-5 h-5" />
      </button>

      <!-- User Menu -->
      <div class="relative" ref="userMenuRef">
        <button 
          @click="showUserMenu = !showUserMenu"
          class="flex items-center gap-3 p-2 hover:bg-gray-100 rounded-lg transition-colors"
        >
          <div class="w-8 h-8 bg-primary-100 text-primary-600 rounded-full flex items-center justify-center">
            <span class="text-sm font-medium">{{ userInitials }}</span>
          </div>
          <span class="text-sm font-medium text-gray-700">{{ user?.name }}</span>
          <Icon name="heroicons:chevron-down" class="w-4 h-4 text-gray-400" />
        </button>

        <!-- Dropdown -->
        <Transition
          enter-active-class="transition ease-out duration-100"
          enter-from-class="transform opacity-0 scale-95"
          enter-to-class="transform opacity-100 scale-100"
          leave-active-class="transition ease-in duration-75"
          leave-from-class="transform opacity-100 scale-100"
          leave-to-class="transform opacity-0 scale-95"
        >
          <div 
            v-if="showUserMenu" 
            class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-50"
          >
            <NuxtLink 
              to="/profile" 
              class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
              @click="showUserMenu = false"
            >
              <Icon name="heroicons:user" class="w-4 h-4" />
              Profile
            </NuxtLink>
            <button 
              @click="showLogoutModal = true; showUserMenu = false"
              class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50"
            >
              <Icon name="heroicons:arrow-right-on-rectangle" class="w-4 h-4" />
              Logout
            </button>
          </div>
        </Transition>
      </div>
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
  </header>
</template>

<script setup lang="ts">
import { onClickOutside } from '@vueuse/core'

const { user, logout } = useAuth()
const route = useRoute()
const toast = useToast()

const showUserMenu = ref(false)
const showLogoutModal = ref(false)
const loggingOut = ref(false)
const userMenuRef = ref<HTMLElement>()

// Close menu on click outside
onClickOutside(userMenuRef, () => {
  showUserMenu.value = false
})

// Get page title from route meta or path
const pageTitle = computed(() => {
  const path = route.path
  if (path === '/') return 'Dashboard'
  
  // Convert path to title: /engineering/products -> Products
  const segments = path.split('/').filter(Boolean)
  const lastSegment = segments[segments.length - 1] || 'Dashboard'
  return lastSegment.charAt(0).toUpperCase() + lastSegment.slice(1).replace(/-/g, ' ')
})

// Get user initials
const userInitials = computed(() => {
  if (!user.value?.name) return '?'
  return user.value.name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
})

// Handle logout
async function handleLogout() {
  loggingOut.value = true
  try {
    await logout()
    showLogoutModal.value = false
    toast.success('Logged out successfully')
    navigateTo('/login')
  } catch (e) {
    toast.error('Failed to logout')
  } finally {
    loggingOut.value = false
  }
}
</script>
