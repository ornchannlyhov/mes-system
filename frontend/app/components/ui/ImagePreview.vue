<template>
  <UiModal 
    v-model="isOpen" 
    :title="alt || 'Image Preview'"
    size="lg"
  >
    <div class="flex items-center justify-center bg-gray-50 rounded-lg p-2 overflow-hidden">
      <img 
        :src="src" 
        :alt="alt" 
        class="max-w-full max-h-[70vh] object-contain rounded shadow-sm"
      />
    </div>
  </UiModal>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  modelValue: boolean
  src: string
  alt?: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})
</script>
