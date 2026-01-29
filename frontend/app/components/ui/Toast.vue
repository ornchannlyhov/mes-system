<template>
  <Teleport to="body">
    <div class="fixed top-4 right-4 z-[100] space-y-2 max-w-sm">
      <TransitionGroup
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0 translate-x-8"
        enter-to-class="opacity-100 translate-x-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100 translate-x-0"
        leave-to-class="opacity-0 translate-x-8"
      >
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg"
          :class="toastClass(toast.type)"
        >
          <Icon :name="toastIcon(toast.type)" class="w-5 h-5 flex-shrink-0" />
          <p class="text-sm font-medium flex-1">{{ toast.message }}</p>
          <button 
            @click="remove(toast.id)" 
            class="p-1 rounded hover:bg-black/10 transition-colors"
          >
            <Icon name="heroicons:x-mark" class="w-4 h-4" />
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
const { toasts, remove } = useToast()

function toastClass(type: string) {
  switch (type) {
    case 'success':
      return 'bg-green-500 text-white'
    case 'error':
      return 'bg-red-500 text-white'
    case 'warning':
      return 'bg-yellow-500 text-white'
    default:
      return 'bg-gray-800 text-white'
  }
}

function toastIcon(type: string) {
  switch (type) {
    case 'success':
      return 'heroicons:check-circle'
    case 'error':
      return 'heroicons:x-circle'
    case 'warning':
      return 'heroicons:exclamation-triangle'
    default:
      return 'heroicons:information-circle'
  }
}
</script>
