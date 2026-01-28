<template>
  <button
    ref="buttonRef"
    type="button"
    :class="[
      'group relative p-1.5 rounded transition-colors',
      color || 'text-gray-400 hover:text-primary-600',
      (disabled || loading) ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'
    ]"
    :disabled="disabled || loading"
    @mouseenter="showTooltip"
    @mouseleave="hideTooltip"
  >
    <Icon 
      v-if="loading" 
      name="heroicons:arrow-path" 
      class="w-4 h-4 animate-spin" 
    />
    <Icon 
      v-else 
      :name="icon" 
      class="w-4 h-4" 
    />
    
    <!-- Teleported Tooltip -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition duration-100 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-75 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
      >
        <div 
          v-if="tooltip && isHovered"
          class="fixed z-[9999] px-2 py-1 text-xs font-medium text-white bg-gray-900 rounded pointer-events-none whitespace-nowrap shadow-sm"
          :style="tooltipStyle"
        >
          {{ tooltip }}
        </div>
      </Transition>
    </Teleport>
  </button>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useElementBounding } from '@vueuse/core'

defineProps<{
  icon: string
  tooltip?: string
  color?: string
  disabled?: boolean
  loading?: boolean
}>()

const buttonRef = ref<HTMLElement | null>(null)
const isHovered = ref(false)

// Use VueUse for easy bounding box calculation
const { top, left, width, height } = useElementBounding(buttonRef)

const tooltipStyle = computed(() => {
  // Position above the button horizontally centered
  return {
    top: `${top.value - 30}px`, // 30px offset roughly accounts for tooltip height + gap
    left: `${left.value + (width.value / 2)}px`,
    transform: 'translateX(-50%)'
  }
})

function showTooltip() {
  isHovered.value = true
}

function hideTooltip() {
  isHovered.value = false
}
</script>
