<template>
  <div class="card">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-gray-500">{{ title }}</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">{{ value }}</p>
      </div>
      <div :class="iconBgClass" class="w-12 h-12 rounded-xl flex items-center justify-center">
        <Icon :name="icon" :class="iconClass" class="w-6 h-6" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  title: string
  value: number | string
  icon: string
  color: 'primary' | 'success' | 'warning' | 'error' | 'info'
}>()

const colorMap = {
  primary: { bg: 'bg-primary-100', text: 'text-primary-600' },
  success: { bg: 'bg-green-100', text: 'text-green-600' },
  warning: { bg: 'bg-yellow-100', text: 'text-yellow-600' },
  error: { bg: 'bg-red-100', text: 'text-red-600' },
  info: { bg: 'bg-blue-100', text: 'text-blue-600' },
}

const iconBgClass = computed(() => colorMap[props.color].bg)
const iconClass = computed(() => colorMap[props.color].text)
</script>
