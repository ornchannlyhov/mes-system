<template>
  <span :class="badgeClass">{{ label }}</span>
</template>

<script setup lang="ts">
const props = defineProps<{
  priority: string
}>()

const priorityConfig: Record<string, { class: string; label: string }> = {
  low: { class: 'badge-gray', label: 'Low' },
  normal: { class: 'badge-info', label: 'Normal' },
  high: { class: 'badge-warning', label: 'High' },
  urgent: { class: 'badge-error', label: 'Urgent' },
  critical: { class: 'badge-error', label: 'Critical' },
}

const badgeClass = computed(() => priorityConfig[props.priority]?.class || 'badge-gray')
const label = computed(() => priorityConfig[props.priority]?.label || props.priority)
</script>
