<template>
  <div class="flex flex-col items-center justify-center py-12 text-center">
    <div v-if="icon" class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mb-3">
      <Icon :name="icon" class="w-6 h-6 text-gray-400" />
    </div>
    <h3 class="text-sm font-medium text-gray-900">{{ title }}</h3>
    <p v-if="description" class="text-sm text-gray-500 mt-1 max-w-sm">{{ description }}</p>
    <div v-if="$slots.default" class="mt-4">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  title: string
  description?: string
  icon?: string
}>()
</script>
