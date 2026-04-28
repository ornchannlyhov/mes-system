<template>
  <div class="min-h-screen flex bg-gray-50">
    <!-- Mobile Overlay -->
    <Transition
      enter-active-class="transition-opacity ease-out duration-300"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity ease-in duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div 
        v-if="mobileMenuOpen" 
        class="fixed inset-0 bg-black/50 z-40 lg:hidden"
        @click="mobileMenuOpen = false"
      />
    </Transition>

    <!-- Sidebar Navigation -->
    <aside 
      :class="[
        'fixed lg:static inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 transform transition-transform duration-300 ease-in-out',
        mobileMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
      ]"
    >
      <!-- Logo -->
      <div class="h-16 flex items-center px-6 border-b border-gray-200">
        <Icon name="heroicons:shield-check" class="w-8 h-8 text-primary-600 mr-3" />
        <span class="font-bold text-gray-900">System Admin</span>
      </div>

      <!-- Navigation -->
      <nav class="p-4 space-y-1">
        <NuxtLink 
          to="/sysadmin" 
          class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-colors"
          :class="$route.path === '/sysadmin' ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
          @click="mobileMenuOpen = false"
        >
          <Icon name="heroicons:home" class="w-5 h-5" />
          Dashboard
        </NuxtLink>
        <NuxtLink 
          to="/sysadmin/pending-approvals" 
          class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-colors"
          :class="$route.path === '/sysadmin/pending-approvals' ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
          @click="mobileMenuOpen = false"
        >
          <Icon name="heroicons:user-plus" class="w-5 h-5" />
          Approvals
        </NuxtLink>
        <NuxtLink 
          to="/sysadmin/organizations" 
          class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-colors"
          :class="$route.path === '/sysadmin/organizations' ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
          @click="mobileMenuOpen = false"
        >
          <Icon name="heroicons:building-office" class="w-5 h-5" />
          Organizations
        </NuxtLink>
      </nav>

      <!-- User & Logout -->
      <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3 min-w-0">
            <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center flex-shrink-0">
              <Icon name="heroicons:user" class="w-5 h-5 text-primary-600" />
            </div>
            <div class="min-w-0">
              <p class="text-sm font-medium text-gray-900 truncate">{{ user?.name || 'Superadmin' }}</p>
              <p class="text-xs text-gray-500 truncate">{{ user?.email }}</p>
            </div>
          </div>
          <UiIconButton
            @click="showLogoutConfirm = true"
            icon="heroicons:arrow-right-on-rectangle"
            tooltip="Logout"
            color="text-gray-400 hover:text-red-600"
          />
        </div>
      </div>
    </aside>

    <!-- Logout Confirm Modal -->
    <UiConfirmModal
      v-model="showLogoutConfirm"
      title="Confirm Logout"
      message="Are you sure you want to logout?"
      confirm-text="Logout"
      cancel-text="Cancel"
      variant="danger"
      @confirm="handleLogout"
    />

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-h-screen min-w-0 overflow-x-hidden">
      <!-- Mobile Header -->
      <header class="lg:hidden sticky top-0 z-30 bg-white border-b border-gray-200 px-4 py-3 flex items-center gap-3">
        <button @click="mobileMenuOpen = true" class="p-2 -ml-2 hover:bg-gray-100 rounded-lg">
          <Icon name="heroicons:bars-3" class="w-6 h-6 text-gray-600" />
        </button>
        <div class="flex items-center gap-2">
          <Icon name="heroicons:shield-check" class="w-6 h-6 text-primary-600" />
          <span class="font-semibold text-gray-900">System Admin</span>
        </div>
      </header>

      <!-- Page Content -->
      <main class="flex-1 p-4 lg:p-6">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
const { user, logout } = useSuperadminAuth()
const router = useRouter()
const mobileMenuOpen = ref(false)
const showLogoutConfirm = ref(false)

async function handleLogout() {
  await logout()
  router.push('/sysadmin/login')
}
</script>
