<template>
  <UiModal v-model="isOpen" :title="title">
    <div class="space-y-4">
      <p class="text-gray-600">{{ message }}</p>
      
      <div class="flex justify-end gap-3 pt-4">
        <button 
          type="button" 
          class="btn-ghost" 
          @click="isOpen = false"
        >
          {{ cancelText }}
        </button>
        <button 
          type="button" 
          :class="confirmButtonClass" 
          @click="handleConfirm"
        >
          <Icon v-if="loading" name="heroicons:arrow-path" class="w-4 h-4 animate-spin" />
          {{ confirmText }}
        </button>
      </div>
    </div>
  </UiModal>
</template>

<script setup lang="ts">
const props = withDefaults(defineProps<{
  modelValue: boolean
  title: string
  message: string
  confirmText?: string
  cancelText?: string
  variant?: 'danger' | 'warning' | 'primary'
  loading?: boolean
}>(), {
  confirmText: 'Confirm',
  cancelText: 'Cancel',
  variant: 'primary',
  loading: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'confirm': []
}>()

// Use computed to properly handle v-model on child component
const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const confirmButtonClass = computed(() => {
  switch (props.variant) {
    case 'danger':
      return 'btn-primary bg-red-600 hover:bg-red-700 focus:ring-red-500'
    case 'warning':
      return 'btn-primary bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-500'
    default:
      return 'btn-primary'
  }
})

function handleConfirm() {
  emit('confirm')
}
</script>
