<template>
  <Teleport to="body">
    <Transition name="slideover" :duration="500">
      <div v-if="modelValue" class="fixed inset-0 z-50 overflow-hidden">
        <!-- Backdrop -->
        <div class="backdrop fixed inset-0 bg-black/50" @click="$emit('update:modelValue', false)"></div>

        <!-- Slide-over container -->
        <div class="fixed inset-y-0 right-0 flex max-w-full pl-0 sm:pl-10 pointer-events-none">
          <div 
            class="panel pointer-events-auto w-screen bg-white shadow-xl flex flex-col h-full"
            :class="[width || 'max-w-md sm:max-w-none sm:w-[50vw]']"
            @click.stop
          >
            <!-- Header -->
            <div class="flex items-center justify-between px-10 pt-6 pb-0 bg-white z-10">
              <h3 class="text-xl font-semibold text-gray-800">{{ title }}</h3>
              <button 
                @click="$emit('update:modelValue', false)"
                class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 focus:outline-none"
              >
                <Icon name="heroicons:x-mark" class="w-6 h-6" />
              </button>
            </div>

            <!-- Content (Scrollable) -->
            <div class="flex-1 overflow-y-auto px-10 pt-4 pb-8">
              <slot />
            </div>

            <!-- Footer -->
            <div v-if="$slots.footer" class="px-10 py-4 border-t border-gray-100 bg-white z-10">
                <slot name="footer" />
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
defineProps<{
  modelValue: boolean
  title: string
  width?: string
}>()

defineEmits<{
  'update:modelValue': [value: boolean]
}>()
</script>

<style scoped>
/* Wrapper Transition - Keeps the element mounted during children animations */
.slideover-enter-active,
.slideover-leave-active {
  transition: visibility 0.5s;
}

/* Backdrop Fade */
.backdrop {
  transition: opacity 0.3s ease-out;
}
.slideover-enter-from .backdrop,
.slideover-leave-to .backdrop {
  opacity: 0;
}

/* Panel Slide */
.panel {
  transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1); /* Smooth ease-out */
}
.slideover-enter-from .panel,
.slideover-leave-to .panel {
  transform: translateX(100%);
}
</style>
