<template>
  <Transition
    enter-active-class="transition-transform ease-out duration-300"
    enter-from-class="-translate-x-full"
    enter-to-class="translate-x-0"
    leave-active-class="transition-transform ease-in duration-200"
    leave-from-class="translate-x-0"
    leave-to-class="-translate-x-full"
  >
    <aside 
      v-show="isVisible"
      :class="[
        isCollapsed ? 'w-20' : 'w-64',
        'bg-white border-r border-gray-200 flex flex-col',
        'fixed lg:sticky lg:top-0 inset-y-0 left-0 z-50 lg:z-auto h-screen',
        'lg:translate-x-0 transition-all duration-300 relative'
      ]"
    >
      <!-- Toggle button (desktop) attached to sidebar -->
      <button 
        v-if="isDesktop" 
        @click="toggleCollapse"
        class="hidden lg:flex absolute -right-3 top-20 bg-white border border-gray-200 rounded-full p-1 shadow-sm text-gray-500 hover:text-primary-600 transition-transform duration-300 z-50 cursor-pointer"
        :class="{ 'rotate-180': isCollapsed }"
      >
        <Icon name="heroicons:chevron-left" class="w-4 h-4" />
      </button>
      <div class="h-16 flex items-center px-4 border-b border-gray-100 transition-all duration-300 justify-center">
        <div class="overflow-hidden transition-all duration-300">
          <div 
            :class="[
              isCollapsed ? 'w-10 h-10' : 'w-32 h-8',
              'rounded-lg flex items-center justify-center shrink-0 transition-all duration-300',
              logoError ? 'bg-primary-500' : 'bg-transparent'
            ]"
          >
             <img v-show="!logoError" :src="logoSrc" alt="CamSME Logo" class="w-full h-full object-contain" @error="logoError = true" />
             <Icon v-if="logoError" name="heroicons:cube" :class="isCollapsed ? 'w-6 h-6' : 'w-6 h-6'" class="text-white" />
          </div>
        </div>


        <!-- Close button (mobile/tablet only) -->
        <button 
          @click="$emit('close')"
          class="lg:hidden p-2 -mr-2 hover:bg-gray-100 rounded-lg"
        >
          <Icon name="heroicons:x-mark" class="w-5 h-5 text-gray-500" />
        </button>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <!-- Dashboard -->
        <NuxtLink 
          to="/" 
          class="nav-item" 
          active-class="nav-item-active"
          :class="{ 'justify-center': isCollapsed }"
        >
          <Icon name="heroicons:home" class="w-5 h-5 shrink-0" />
          <span v-if="!isCollapsed" class="whitespace-nowrap">Dashboard</span>
        </NuxtLink>

        <!-- Execution -->
        <LayoutNavGroup 
          v-if="hasPermission('manufacturing:read')"
          label="Execution" 
          icon="heroicons:play"
          :collapsed="isCollapsed"
          @expand="isCollapsed = false"
          :items="[
            { to: '/execution/manufacturing-orders', label: 'Manufacturing Orders', icon: 'heroicons:clipboard-document-list' },
            { to: '/execution/work-orders', label: 'Work Orders', icon: 'heroicons:wrench-screwdriver' },
            { to: '/execution/quality', label: 'Quality Assurance', icon: 'heroicons:clipboard-document-check' },
            { to: '/execution/problems', label: 'Problems', icon: 'heroicons:exclamation-triangle' },
          ]"
        />



        <!-- Engineering -->
        <LayoutNavGroup 
          v-if="hasPermission('products:manage') || hasPermission('boms:manage')"
          label="Engineering" 
          icon="heroicons:wrench-screwdriver"
          :collapsed="isCollapsed"
          @expand="isCollapsed = false"
          :items="[
            { to: '/engineering/products', label: 'Products', icon: 'heroicons:cube' },
            { to: '/engineering/boms', label: 'Bill of Materials', icon: 'heroicons:list-bullet' },
            { to: '/engineering/work-centers', label: 'Work Centers', icon: 'heroicons:building-office' },
          ]"
        />

        <!-- Inventory -->
        <LayoutNavGroup 
          v-if="hasPermission('inventory:read')"
          label="Inventory" 
          icon="heroicons:archive-box"
          :collapsed="isCollapsed"
          @expand="isCollapsed = false"
          :items="[
            { to: '/inventory/stock', label: 'Stock', icon: 'heroicons:archive-box' },
            { to: '/inventory/adjustments', label: 'Adjustments', icon: 'heroicons:arrows-up-down' },
            { to: '/inventory/locations', label: 'Locations', icon: 'heroicons:map-pin' },
            { to: '/inventory/traceability', label: 'Traceability', icon: 'heroicons:clipboard-document-list' },
          ]"
        />

        <!-- Maintenance -->
        <LayoutNavGroup 
          v-if="hasPermission('maintenance:read')"
          label="Maintenance" 
          icon="heroicons:cog-6-tooth"
          :collapsed="isCollapsed"
          @expand="isCollapsed = false"
          :items="[
            { to: '/maintenance/equipment', label: 'Equipment', icon: 'heroicons:cog-6-tooth' },
            { to: '/maintenance/requests', label: 'Requests', icon: 'heroicons:ticket' },
            { to: '/maintenance/schedules', label: 'Schedules', icon: 'heroicons:calendar-days' },
          ]"
        />

        <!-- Admin -->
        <LayoutNavGroup 
          v-if="hasPermission('users:manage') || hasPermission('roles:assign')"
          label="System" 
          icon="heroicons:user-group"
          :collapsed="isCollapsed"
          @expand="isCollapsed = false"
          :items="[
            { to: '/admin/users', label: 'Users', icon: 'heroicons:users' },
            { to: '/admin/roles', label: 'Roles & Permissions', icon: 'heroicons:shield-check' },
          ]"
        />
      </nav>

      <!-- User Profile -->
      <div class="p-4 border-t border-gray-200">
        <div class="relative" ref="userMenuRef">
          <button 
            @click="showUserMenu = !showUserMenu"
            class="w-full flex items-center gap-3 p-2 hover:bg-gray-100 rounded-lg transition-colors"
          >
            <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 overflow-hidden border border-gray-200" :class="!user?.avatar_url ? 'bg-primary-100 text-primary-600' : ''">
              <img v-if="user?.avatar_url" :src="getImageUrl(user.avatar_url)" class="w-full h-full object-cover" />
              <span v-else class="text-sm font-medium">{{ userInitials }}</span>
            </div>
            <div class="flex-1 text-left overflow-hidden" v-if="!isCollapsed">
              <p class="text-sm font-medium text-gray-700 truncate">{{ user?.name }}</p>
              <p class="text-xs text-gray-500 truncate">{{ user?.role?.label || 'User' }}</p>
            </div>
            <Icon v-if="!isCollapsed" name="heroicons:chevron-up" class="w-4 h-4 text-gray-400" />
          </button>

          <!-- Dropdown (Upwards) -->
          <Transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="transform opacity-0 scale-95 translate-y-2"
            enter-to-class="transform opacity-100 scale-100 translate-y-0"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100 translate-y-0"
            leave-to-class="transform opacity-0 scale-95 translate-y-2"
          >
            <div 
              v-if="showUserMenu" 
              class="absolute bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-50 transition-all duration-200"
              :class="[
                isCollapsed 
                  ? 'left-full bottom-0 ml-2 w-48' 
                  : 'bottom-full left-0 w-full mb-2'
              ]"
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
        message="Are you sure you want to log out?"
        confirm-text="Logout"
        variant="danger"
        :loading="loggingOut"
        @confirm="handleLogout"
      />
    </aside>
  </Transition>
</template>

<script setup lang="ts">
import { onClickOutside, useStorage } from '@vueuse/core'

const props = defineProps<{
  mobileOpen?: boolean
}>()

const emit = defineEmits<{
  close: []
}>()

const { user, logout } = useAuth()
const { hasPermission } = usePermissions()
const toast = useToast()
const { getImageUrl } = useUtils()

const showUserMenu = ref(false)
const showLogoutModal = ref(false)
const loggingOut = ref(false)
const userMenuRef = ref<HTMLElement>()
const logoError = ref(false)
const logoSrc = '/images/logo.png'

// Sidebar visibility - always visible on desktop (>=1024px), controlled on mobile/tablet
const isDesktop = ref(true)
const uiStore = useUiStore()
const isCollapsed = computed({
  get: () => uiStore.sidebarCollapsed,
  set: (val) => uiStore.setSidebarCollapsed(val)
})

function toggleCollapse() {
  uiStore.toggleSidebar()
}

onMounted(() => {
  isDesktop.value = window.innerWidth >= 1024
  window.addEventListener('resize', () => {
    isDesktop.value = window.innerWidth >= 1024
  })
})

const isVisible = computed(() => {
  return isDesktop.value || props.mobileOpen
})

// Close menu on click outside
onClickOutside(userMenuRef, () => {
  showUserMenu.value = false
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
    navigateTo('/auth/login')
  } catch (e) {
    toast.error('Failed to logout')
  } finally {
    loggingOut.value = false
  }
}
</script>

<style scoped>
.nav-item {
  @apply flex items-center gap-3 px-3 py-2.5 text-gray-600 rounded-lg
         hover:bg-gray-100 hover:text-gray-800 transition-colors text-sm;
}

.nav-item-active {
  @apply bg-primary-50 text-primary-600 font-medium;
}
</style>
