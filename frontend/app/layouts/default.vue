<template>
  <div class="min-h-screen flex">
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
        v-if="sidebarOpen" 
        class="fixed inset-0 bg-black/50 z-40 lg:hidden"
        @click="sidebarOpen = false"
      />
    </Transition>

    <!-- Sidebar -->
    <LayoutSidebar 
      :mobile-open="sidebarOpen"
      @close="sidebarOpen = false"
    />

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-h-screen min-w-0 overflow-x-hidden">
      <!-- Mobile/Tablet Header -->
      <header class="lg:hidden sticky top-0 z-30 bg-white border-b border-gray-200 px-4 py-3 flex items-center gap-3">
        <button @click="sidebarOpen = true" class="p-2 -ml-2 hover:bg-gray-100 rounded-lg">
          <Icon name="heroicons:bars-3" class="w-6 h-6 text-gray-600" />
        </button>
        <div class="flex items-center gap-2">
          <div class="w-7 h-7 bg-primary-500 rounded-lg flex items-center justify-center">
            <Icon name="heroicons:cube" class="w-4 h-4 text-white" />
          </div>
          <span class="text-lg font-bold text-gray-800">MES</span>
        </div>
      </header>

      <!-- Page Content -->
      <main class="flex-1 p-4 lg:p-6 bg-gray-50">
        <slot />
      </main>
    </div>

    <!-- Global Toast Notifications -->
    <UiToast />
    
    <!-- Loading Overlay -->
    <div v-if="!initialized" class="fixed inset-0 bg-white z-50 flex items-center justify-center">
        <div class="flex flex-col items-center gap-3">
            <div class="w-10 h-10 border-4 border-primary-200 border-t-primary-600 rounded-full animate-spin"></div>
            <p class="text-gray-500 font-medium">Loading...</p>
        </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// Protect route - redirect to login if not authenticated
const { isAuthenticated, checkAuth } = useAuth()

// Mobile sidebar state
const sidebarOpen = ref(false)
const initialized = ref(false)

// Close sidebar on route change
const route = useRoute()
watch(() => route.path, () => {
  sidebarOpen.value = false
})

onMounted(async () => {
    try {
        await checkAuth()
    } finally {
        initialized.value = true
    }
})

watchEffect(() => {
    if (initialized.value && !isAuthenticated.value && route.path !== '/auth/login' && !route.path.startsWith('/auth/')) {
        navigateTo('/auth/login')
    }
})
</script>
