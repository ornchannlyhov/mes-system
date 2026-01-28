<template>
  <div v-if="totalPages > 1" class="flex items-center justify-between px-6 py-4 border-t border-gray-100">
    <p class="text-sm text-gray-500">
      Showing {{ (modelValue - 1) * pageSize + 1 }} to {{ Math.min(modelValue * pageSize, totalItems) }} of {{ totalItems }} items
    </p>
    <div class="flex gap-1">
      <button 
        @click="$emit('update:modelValue', modelValue - 1)" 
        :disabled="modelValue === 1"
        class="px-3 py-1 text-sm rounded-lg border border-gray-200 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        Previous
      </button>
      <button 
        v-for="page in visiblePages" 
        :key="page"
        @click="$emit('update:modelValue', page)"
        :class="[
          'px-3 py-1 text-sm rounded-lg border',
          modelValue === page ? 'bg-primary-600 text-white border-primary-600' : 'border-gray-200 hover:bg-gray-50'
        ]"
      >
        {{ page }}
      </button>
      <button 
        @click="$emit('update:modelValue', modelValue + 1)" 
        :disabled="modelValue === totalPages"
        class="px-3 py-1 text-sm rounded-lg border border-gray-200 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        Next
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps({
  modelValue: {
    type: Number,
    required: true
  },
  totalItems: {
    type: Number,
    required: true
  },
  pageSize: {
    type: Number,
    default: 10
  }
})

defineEmits(['update:modelValue'])

const totalPages = computed(() => Math.ceil(props.totalItems / props.pageSize))

const visiblePages = computed(() => {
  const pages = []
  const total = totalPages.value
  const current = props.modelValue
  const maxVisible = 5
  
  let start = Math.max(1, current - Math.floor(maxVisible / 2))
  let end = Math.min(total, start + maxVisible - 1)
  
  if (end - start + 1 < maxVisible) {
    start = Math.max(1, end - maxVisible + 1)
  }
  
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  return pages
})
</script>
